<?php

namespace Drupal\startklar\Authentication;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\startklar\Session\AnmeldungSession;
use Drupal\startklar\Session\AnmeldungType;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AnmeldungJWTAuthenticationProvider implements AuthenticationProviderInterface {

  protected string $JWT_KEY;

  /**
   * @throws \Exception
   */
  public function __construct() {
    $jwtKey = getenv('STARTKLAR_JWT_KEY');

    if (empty($jwtKey) || strlen($jwtKey) == 0) {
      throw new \Exception("The environment variable 'STARTKLAR_JWT_KEY' is not set.");
    }

    $this->JWT_KEY = $jwtKey;
  }

  public function applies(Request $request) {
    return (bool) self::getJwtFromRequest($request);
  }

  public function authenticate(Request $request) {
    $rawJwt = self::getJwtFromRequest($request);

    try {
      $token = JWT::decode($rawJwt, new Key($this->JWT_KEY, 'HS256'));
    } catch (\Exception $e) {
      \Drupal::logger('startklar_auth')->error("Exception while decoding JWT: " . $e->getMessage(), [
        'exception' => $e
      ]);

      throw new UnauthorizedHttpException("JWT", "Invalid JWT", $e);
    }

    if ($token->iss !== $request->getHttpHost()) {
      throw new UnauthorizedHttpException("JWT", "JWT was issued by another host");
    }

    if (!property_exists($token, 'sub')) {
      throw new UnauthorizedHttpException("JWT", "JWT is missing 'sub'");
    }

    if (!property_exists($token, 'type')) {
      throw new UnauthorizedHttpException("JWT", "JWT is missing 'type'");
    }

    if (!in_array($token->type, [AnmeldungType::GROUP->value, AnmeldungType::HELPER->value])) {
      throw new UnauthorizedHttpException("JWT", "JWT has invalid 'type'");
    }

    return new AnmeldungSession([
      'anmeldungType' => AnmeldungType::from($token->type),
      'subject' => $token->sub,
    ]);
  }



  /**
   * Gets a raw JsonWebToken from the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return string|bool
   *   Raw JWT String if on request, false if not.
   */
  protected static function getJwtFromRequest(Request $request) {
    $auth_headers = [];
    $auth = $request->headers->get('Authorization');
    if ($auth) {
      $auth_headers[] = $auth;
    }
    // Check a second header used in combination with basic auth.
    $fallback = $request->headers->get('JWT-Authorization');
    if ($fallback) {
      $auth_headers[] = $fallback;
    }
    foreach ($auth_headers as $value) {
      if (preg_match('/^Bearer (.+)/', $value, $matches)) {
        return $matches[1];
      }
    }
    return FALSE;
  }

}
