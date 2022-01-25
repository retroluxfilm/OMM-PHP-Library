<?php
/*
 * OMM PHP Library
 * Copyright (c) 2022-2022. Alexandre Miguel Maia
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

namespace OMM\Repository;


use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;
use OMM\Package\Package;

/**
 * Remote Repository Descriptor XML
 */
class RemoteRepositoryXML
{
    private const OPEN_MOD_MANAGER_REPOSITORY = "Open_Mod_Manager_Repository";
    private const REMOTES_COUNT = "count";

    private DOMDocument $xml;
    private DOMElement $root;
    private DOMNode $remotes;

    private string $repositoryFileName;
    private int $remoteCount = 0;

    /**
     * Creates the remote repository Descriptor
     * @throws Exception
     */
    public function __construct(string $repositoryFileName, string $repositoryTitle, string $repositoryRootPath)
    {
        // store filename to save it later
        $this->repositoryFileName = $repositoryFileName;

        // init xml structure
        $this->initializeRepositoryXML($repositoryFileName);
        $this->updateGlobalXMLData($repositoryTitle, $repositoryRootPath);
    }

    /**
     * Initializes the remote repository document. Reuses existing or creates a new one when not present.
     * @param string $repositoryFileName
     * @throws Exception
     */
    protected function initializeRepositoryXML(string $repositoryFileName): void
    {
        // init xml document
        $this->xml = new DOMDocument();
        $this->xml->formatOutput = true;
        $this->xml->preserveWhiteSpace = false;

        // load existing repository XML
        if (file_exists($repositoryFileName)) {
            // load xml file
            if (!$this->xml->load($repositoryFileName)) {
                throw new Exception("Could not load repository xml file " . $repositoryFileName);
            }
            // fetch root node
            /** @var DOMElement $this */
            $this->root = $this->xml->getElementsByTagName(self::OPEN_MOD_MANAGER_REPOSITORY)->item(0);

            // check if the repository xml is valid
            if ($this->root == null || $this->root->nodeName != self::OPEN_MOD_MANAGER_REPOSITORY) {
                throw new Exception("Existing XML is not a valid remote repository file.");
            }
        } else {
            // create new repository xml
            $this->xml->xmlVersion = "1.0";
            // $this->xml->encoding = "utf-8";

            // create the root element
            $this->root = $this->xml->createElement(self::OPEN_MOD_MANAGER_REPOSITORY);
            $this->xml->appendChild($this->root);
        }
    }

    /**
     * Updates global remote repository data in the XML
     * @param string $repositoryTitle
     * @param string $repositoryRootPath
     * @throws Exception
     */
    protected function updateGlobalXMLData(string $repositoryTitle, string $repositoryRootPath): void
    {
        // create uuid if not present
        $uuidList = $this->root->getElementsByTagName("uuid");
        if ($uuidList->length == 0) {
            $uuid = $this->xml->createElement("uuid", RepositoryHelper::generateGuidV4());
            $this->root->appendChild($uuid);
        }

        // create & update repository title
        $titleList = $this->root->getElementsByTagName("title");
        if ($titleList->length == 0) {
            $title = $this->xml->createElement("title", $repositoryTitle);
            $this->root->appendChild($title);
        } else {
            $titleList->item(0)->nodeValue = $repositoryTitle;
        }

        //add or update repository download root path
        $downPathList = $this->root->getElementsByTagName("downpath");
        if ($downPathList->length == 0) {
            $downpath = $this->xml->createElement("downpath", $repositoryRootPath);
            $this->root->appendChild($downpath);
        } else {
            $downPathList->item(0)->nodeValue = $repositoryRootPath;
        }

        // add remote package element and store it for later modification
        $remotesList = $this->root->getElementsByTagName("remotes");
        if ($remotesList->length == 0) {
            $this->remotes = $this->xml->createElement("remotes");
            $this->remotes->setAttribute(self::REMOTES_COUNT, $this->remoteCount);
            $this->root->appendChild($this->remotes);
        } else {
            //init remote element and remote counter from xml
            $this->remotes = $remotesList->item(0);
            $this->remoteCount = $this->remotes->getAttribute(self::REMOTES_COUNT);
        }
    }

    public function __destruct()
    {
        //release memory for the xml
        unset($this->root);
        unset($this->remotes);
        unset($this->xml);
    }

    /**
     * Returns the amount of remote packages are currently in the repository
     * @return int
     */
    public function getRemotePackageCount(): int
    {
        return $this->remoteCount;
    }

    /**
     * Adds package descriptor to the repository XML
     * @param Package $package
     * @throws Exception
     */
    public function addRemotePackage(Package $package)
    {
        //TODO check if already present => skip

        // import mode into the repository document XML
        $nodeCopy = $this->xml->importNode($package->getRemoteDescriptor(), true);
        $this->remotes->appendChild($nodeCopy);

        //increment remote counter
        $this->adjustRemoteCount(1);
    }

    /**
     * Adjusts the remote count by the given delta
     * @param int $delta
     */
    protected function adjustRemoteCount(int $delta): void
    {
        $this->remoteCount += $delta;
        $this->remotes->attributes->getNamedItem(self::REMOTES_COUNT)->nodeValue = $this->remoteCount;
    }

    /**
     *  Specific removal of a remote package by the ident attribute
     * @param string $packageIdent
     */
    public function removeRemotePackage(string $packageIdent)
    {
        /* @var $childNode DOMNode */
        foreach ($this->remotes->childNodes as $childNode) {
            $nodeIdent = $childNode->attributes->getNamedItem("ident")->nodeValue;
            // if package identity has been found
            if (strcmp($nodeIdent, $packageIdent) == 0) {
                // remove node and decrement node count
                $this->remotes->removeChild($childNode);
                $this->adjustRemoteCount(-1);
                break;
            }
        }
    }

    /**
     * Removes all remote packages
     */
    public function cleanAllRemotePackages()
    {
        //reverse through all child nodes and remove them all
        $count = $this->remotes->childNodes->length;
        if($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $childNode = $this->remotes->childNodes->item(0);  // !!! not item($i) !!!
                $this->remotes->removeChild($childNode);
            }

            // reset remote counter by subtracting the current remote count
            $this->adjustRemoteCount(-$this->remoteCount);
        }
    }

    /**
     * Saves the remote repository XML
     * @throws Exception
     */
    public function saveXML()
    {
        if ($this->xml != null) {
            $bytesWritten = $this->xml->save($this->repositoryFileName);

            if ($bytesWritten === false) {
                throw new Exception("repository XML " . $this->repositoryFileName . " could not be saved");
            }
        }
    }

}