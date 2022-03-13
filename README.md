# startklar.bayern Backend

This is a Drupal 9 backend for the www.startklar.bayern website.

## Initial setup

First you need to [install Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The instructions below refer to the [global Composer installation](https://getcomposer.org/doc/00-intro.md#globally).
You might need to replace `composer` with `php composer.phar` (or similar)
for your setup.

1. Clone this repository.

2. Install the composer dependencies:

```
composer install
```

3. Create a MySQL database.

4. Copy the `.env.example` file to `.env` and adjust the properties in there.

5. Import the database dump:
```
cd web && ../vendor/bin/dush sql-query --file=../sql-dumps/dump.sql
```

6. Run database updates:
```
cd web && ../vendor/bin/dush updb
```

7. Run config import:
```
cd web && ../vendor/bin/dush cim
```

8. Clear cache:
```
cd web && ../vendor/bin/dush cr
```

9. Point your webservers root to the `web` directory.


## Updating the latest changes

1. Do a `git pull`
2. Run the steps 2, 6, 7, 8 from Initial Setup

## Making changes
1. Make your changes to code and config
2. Export config
```
cd web && ../vendor/bin/dush cex
```
3. Commit your changes

## Installing Drupal modules / themes / libraries
1. Require the package via composer
```
composer require drupal/module_name
```
2. Install the module
```
cd web && ../vendor/bin/drush en module_name
```
3. Export configuration
```
cd web && ../vendor/bin/drush cex
```
4. Commit changes


* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Theme (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`

## Updating Drupal Core

This project will attempt to keep all of your Drupal Core files up-to-date; the
project [drupal/core-composer-scaffold](https://github.com/drupal/core-composer-scaffold)
is used to ensure that your scaffold files are updated every time drupal/core is
updated. If you customize any of the "scaffolding" files (commonly `.htaccess`),
you may need to merge conflicts if any of your modified files are updated in a
new release of Drupal core.

Follow the steps below to update your core files.

1. Run `composer update "drupal/core-*" --with-dependencies` to update Drupal Core and its dependencies.
2. Run `git diff` to determine if any of the scaffolding files have changed.
   Review the files for any changes and restore any customizations to
  `.htaccess` or `robots.txt`.
1. Commit everything all together in a single commit, so `web` will remain in
   sync with the `core` when checking out branches or running `git bisect`.
1. In the event that there are non-trivial conflicts in step 2, you may wish
   to perform these steps on a branch, and use `git merge` to combine the
   updated core files with your customized files. This facilitates the use
   of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple;
   keeping all of your modifications at the beginning or end of the file is a
   good strategy to keep merges easy.

## FAQ

### Should I commit the contrib modules I download?

Composer recommends **no**. They provide [argumentation against but also
workrounds if a project decides to do it anyway](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md).

### Should I commit the scaffolding files?

The [Drupal Composer Scaffold](https://github.com/drupal/core-composer-scaffold)
plugin can download the scaffold files (like index.php, update.php, …) to the
web/ directory of your project. If you have not customized those files you could
choose to not check them into your version control system (e.g. git). If that is
the case for your project it might be convenient to automatically run the
drupal-scaffold plugin after every install or update of your project. You can
achieve that by registering `@composer drupal:scaffold` as post-install and
post-update command in your composer.json:

```json
"scripts": {
    "post-install-cmd": [
        "@composer drupal:scaffold",
        "..."
    ],
    "post-update-cmd": [
        "@composer drupal:scaffold",
        "..."
    ]
},
```

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull
request is often a better solution), you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra
section of composer.json:

```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL or local path to patch"
        }
    }
}
```

### How do I specify a PHP version ?

This project supports PHP 8.1 as minimum version (see [Environment requirements of Drupal 9](https://www.drupal.org/docs/understanding-drupal/how-drupal-9-was-made-and-what-is-included/environment-requirements-of)), however it's possible that a `composer update` will upgrade some package that will then require PHP 8.1+.

To prevent this you can add this code to specify the PHP version you want to use in the `config` section of `composer.json`:

```json
"config": {
    "sort-packages": true,
    "platform": {
        "php": "8.1.0"
    }
},
```
