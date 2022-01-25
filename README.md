# Open Mod Manager PHP Library
This is a PHP Web library to manage remote repositories for the
[Open Mod Manager](https://github.com/sedenion/OpenModMan). 

---
## Features:
- Generate Repository XML based on mod package sorted in a folder structure
- Ability to issue repository tasks over the command line 

## Requirements
- PHP 7.4+
- ImageMagick
- DOM Extension
- SimpleXML Extension
- MBString Extension
- Zip Extension
- Fileinfo Extension

## Installation

### Composer

To install with [Composer](https://getcomposer.org/), simply require the
latest version of this package.

```bash
composer global require retrolux/omm-php-library
```

If you plan to use the library in your own project, the autoload file from Composer must be loaded early in your project.
Add the following line before accessing classes part of the library

```php
require 'vendor/autoload.php';
```

## Use Examples


### Command Line

Generates the `Package Download Repository` saved into `downloads/repository.xml` scanning for mod packages in the `download/packages folder`

*(the folder for the OMMTask might vary on your webserver)*

```bash
php .config/composer/vendor/retrolux/omm-php-library/src/OMMTask generateFolderRepository "downloads/repository.xml" "Package Download Repository" "downloads/packages"
```