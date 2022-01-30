<?php
/*
 * OMM PHP Library
 * Copyright (c) 2022. Alexandre Miguel Maia
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

namespace OMM\Task;

use Exception;
use OMM\Package\Package;
use OMM\Repository\RemoteRepositoryXML;

class FolderRepositoryTask
{

    /**
     * Generates a repository xml based on the given root path
     * @param string $xmlName name and path of the repository xml
     * @param string $repositoryName name for the repository
     * @param string $repositoryRootPath root path where the mod packages are placed
     * @param bool $recursive true if the sub folders should be processed as well
     * @throws Exception
     */
    public static function generateFolderRepository(
        string $xmlName,
        string $repositoryName,
        string $repositoryRootPath,
        bool $recursive = false
    ) {
        // check if the root patch a valid directory
        if (!is_dir($repositoryRootPath)) {
            throw new Exception("Repository root path is not a valid directory");
        }

        $remoteRepository = new RemoteRepositoryXML($xmlName, $repositoryName, $repositoryRootPath);

        //TODO Remove this when proper checks are implemented allowing to merge etc.
        $remoteRepository->cleanAllRemotePackages();

        echo "-------------------------------------------------------------------------------\n";
        echo "Generate Folder Repository from root folder: '". $repositoryRootPath . "'\n";
        echo "-------------------------------------------------------------------------------\n";

        //fetches Available archives from the given folder
        $availableArchives = self::getAvailableArchives($repositoryRootPath, $recursive);

        // add valid archives as packages to the repository
        foreach ($availableArchives as $archiveFilePath){

            try {
                $package = new Package($archiveFilePath);

                //fetch directory name
                $directoryName = pathinfo($archiveFilePath, PATHINFO_DIRNAME);

                //set sub download directory if it is not the same as the root directory
                if( strlen($directoryName) != strlen($repositoryRootPath)){
                    //remove the rootpath from the subdir
                    $subFolder = substr($directoryName, strlen($repositoryRootPath)+1 );
                    // add subdir info to the package
                    $package->setDownloadPath($subFolder);
                }
                // add package to remote repository
                $remoteRepository->addRemotePackage($package);
                echo "Package '" . $archiveFilePath ."' added to the repository.\n";

            } catch (Exception $exception) {
                error_log("Skipped Invalid Archive '" . $archiveFilePath . "' due to the error: " . $exception->getMessage());
            }

        }

        //self::addPackagesFromFolderToRepository($remoteRepository, $repositoryRootPath, $recursive);

        //save changes to the remote repository
        $remoteRepository->saveXML();

        echo "=> Finished generating folder repository\n";
    }

    static function showHelp(){

        echo "Generates a repository xml from a given repository XML\n\n";
        echo "Usage:\n";
        echo " OMMTask generateFolderRepository <xmlName> <repositoryName> <packageRootPath> (<recursive>)\n\n";
        echo "Arguments:\n";
        echo " xmlName                   : name and path of the repository xml\n";
        echo " repositoryName            : name for the repository\n";
        echo " packageRootPath           : root path where the mod packages are placed\n";
        echo " recursive                 : true if the sub folders should be processed as well (Default: false)\n";

        // TODO parse from php doc the possible params
       // $class = new ReflectionClass(FolderRepositoryTask::class);
       // echo $class->getMethod("generateFolderRepository")->getDocComment();

    }

    /**
     *
     * @param $searchDir
     * @param bool $recursive
     * @return array
     * @throws Exception
     */
    static function getAvailableArchives($searchDir, bool $recursive): array
    {

        $result = array();

        //scan directory for all files and sub directories
        $fileList = scandir($searchDir);
        if($fileList === FALSE){
            throw new Exception("Could not search for achives in given folder: " . $searchDir);
        }

        //go through each entry
        foreach ($fileList as $fileEntry)
        {
            //skip special folders "." and ".."
            if (in_array($fileEntry,array(".",".."))) {
                continue;
            }

            // if the entry is an directory
            if (is_dir($searchDir . DIRECTORY_SEPARATOR . $fileEntry))
            {
                if($recursive) {
                    $result = array_merge($result,self::getAvailableArchives($searchDir . DIRECTORY_SEPARATOR . $fileEntry,$recursive)
                    );
                }
            }
            else
            {
                // skip non zip archives
                if (strcasecmp("zip", pathinfo($fileEntry, PATHINFO_EXTENSION)) != 0) {
                    continue;
                }

                array_push($result, $searchDir . DIRECTORY_SEPARATOR . $fileEntry);
            }

        }

        return $result;
    }

}