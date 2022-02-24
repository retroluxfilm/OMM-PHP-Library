# Open Mod Manager PHP Library
This is a PHP Web library to manage remote repositories for the
[Open Mod Manager](https://github.com/sedenion/OpenModMan). 

---
## Features:
- Generate Repository XML based on mod package sorted in a folder structure
- Ability to issue repository tasks over the command line (cron jobs etc.)
- Package and Repository object handling allowing easy extension to build it into your web frontend 

## Requirements
- PHP 7.4+ (using PHP 8.1+ will speed up the library)
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

## Command Line options

### generateFolderRepository
Generates a networked repository from a folder structure with the contained mod archives.  

**Note:**  
Needs to be called within the folder that is accessible through the web server and located side by side the folder
it should be looked for mod archives.


*Usage Options:*

````
OMMTask generateFolderRepository <xmlName> <repositoryName> <packageRootPath> (<recursive>)
Arguments:
        xmlName                   : name and path of the repository xml
        repositoryName            : name for the repository
        packageRootPath           : root path where the mod packages are placed relative to repository xml location
        recursive                 : true if the sub folders should be processed as well (Default: true)
````

The example below will generate the `Mod Download Repository` saved into `repository.xml` scanning for the packages 
in the `mods` folder and subdirectories (relative to the xml).

```bash
OMMTask generateFolderRepository "repository.xml" "Mod Download Repository" "mods"
```


## How to Contribute
To keep this library up to date or to extend it with new tasks etc. 
I would be great use pull request into this repository.