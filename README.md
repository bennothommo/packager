# Packager

Run Composer commands within a PHP application. This library acts as a PHP wrapper for Composer commands, allowing package management through PHP.

You technically *do not* need Composer to be installed, as this library will bring in the Composer application as a dependency - however, you will need Composer to install this library itself.

## Use case

This library was written initially to provide Composer usage through PHP for [Winter CMS](https://github.com/wintercms/winter), to power
its upgrade and plugin architecture. A portion of the Winter CMS user-base prefer web-based installation methods and prefer not to (or simply can't) use the command-line Composer application.

This library therefore leverages the power of Composer in maintaining dependencies and does so in a way that can be programatically controlled and presented in a friendlier format for these users.

## Installation

Install via Composer:

```
composer require bennothommo\packager
```

## Usage

This library currently provides support for the following Composer commands:

- `version` **(--version)**
- `install`
- `update`
- `show`

You can create a `Composer` instance in your PHP script and run these commands like so, defining a working directory which contains a `composer.json` file, and a home directory in which cached packages are stored.

```php
<?php
use BennoThommo\Packager\Composer;

$composer = new Composer();
$composer
    ->setWorkDir('/home/me/project/')
    ->setHomeDir('/tmp/composer');

// Get the Composer version
$version = $composer->version();

// Run an install or update
$install = $composer->install();
$update = $composer->update();

// Show installed packages
$show = $composer->show();
```

More information on each of these commands can be found in the Wiki.

## Security Implications

Note that this library does introduce some security implications which you may need to consider before using.

- If this library is used in a web-based environment, you must make the vendor folder and files writable for the user running your web server. You **should not** make your vendor folder web-accessible, otherwise this will become an easy vector for an attack.
- Allowing scripts to run within Composer may grant the script access to your web environment or outlying PHP application. You should not run scripts unless you absolutely trust your dependencies.

## Special thanks

- [The Composer team](https://getcomposer.org)
- User "Endel" from Stack Overflow, for [this answer](https://stackoverflow.com/a/25208897) which served as the inspiration for this library.
