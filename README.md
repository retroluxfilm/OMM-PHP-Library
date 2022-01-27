# Open Mod Manager PHP Library
This is a PHP Web library to manage remote repositories for the
[Open Mod Manager](https://github.com/sedenion/OpenModMan). 

---
## Features:
- Generate Repository XML based on mod package sorted in a folder structure
- Ability to issue repository tasks over the command line (cron jobs etc.)
- Package and Repository object handling allowing easy extension to build it into your web frontend 


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
latest version of this package. Install it as a global library in case you only want to use if from the command line

```bash
composer global require retrolux/omm-php-library
```

## Use Examples

### Command Line

Generates the `Package Download Repository` saved into `downloads/repository.xml` scanning for mod packages in the `download/packages folder`

```bash
OMMTask generateFolderRepository "downloads/repository.xml" "Package Download Repository" "downloads/packages"
```


## How to Contribute
To keep this library up to date or to extend it with new tasks etc. 
I would be great use pull request into this repository.