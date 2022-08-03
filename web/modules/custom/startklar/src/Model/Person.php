<?php

namespace Drupal\startklar\Model;

class Person {
  public string $id;
  public string $vorname;
  public string $nachname;
  public string $geburtsdatum;
  public string $geschlecht;
  public string $strasse;
  public string $plz;
  public string $ort;
  public string $telefon;
  public string $mail;
  public string $telefon_eltern;
  public string $mail_eltern;
  public string $aufsichtsperson;
  public string $essen;
  public string $essen_anmerkungen;
  public string $geschwisterkind;
  public PersonAnreise $anreise;
  public string $fuehrungszeugnis;
  public int $termin_schutzkonzept;
}
