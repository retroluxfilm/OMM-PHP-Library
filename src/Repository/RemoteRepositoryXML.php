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

    /**
     * Tags of the remote repository xml
     */
    private const
        TAG_OMM_REPOSITORY = "Open_Mod_Manager_Repository",
        TAG_UUID = "uuid",
        TAG_TITLE = "title",
        TAG_DOWNPATH = "downpath",
        TAG_REMOTES = "remotes";

    /**
     * Attributes of the remote repository xml
     */
    private const ATTRIBUTE_COUNT = "count";

    private DOMDocument $xml;
    private DOMElement $root;
    private DOMNode $remotes;

    private RemotePackageList $remotePackageList;

    private string $repositoryFileName;
    private string $repositoryRootPath;


    /**
     * Creates the remote repository xml
     * @throws Exception
     */
    public function __construct(string $repositoryFileName, string $repositoryTitle, string $repositoryRootPath)
    {
        // store filename to save it later
        $this->repositoryFileName = $repositoryFileName;
        $this->repositoryRootPath = $repositoryRootPath;
        $this->remotePackageList = new RemotePackageList();

        // init xml structure
        $this->initializeRepositoryXML($repositoryFileName);
        $this->updateRepositoryFromXMLData($repositoryTitle, $repositoryRootPath);
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
                throw new Exception("Could not load repository xml '" . $repositoryFileName . "' file.");
            }
            // fetch root node
            $this->root = $this->xml->documentElement;

            // check if the repository xml is valid
            if ($this->root == null || $this->root->nodeName != self::TAG_OMM_REPOSITORY) {
                throw new Exception("Existing XML '". $repositoryFileName . "' is not a valid remote repository file.");
            }
        } else {
            // create new repository xml
            $this->xml->xmlVersion = "1.0";
            //$this->xml->encoding = "utf-8";

            // create the root element
            $this->root = $this->xml->createElement(self::TAG_OMM_REPOSITORY);
            $this->xml->appendChild($this->root);
        }
    }

    /**
     * Updates global remote repository data in the XML
     * @param string $repositoryTitle
     * @param string $repositoryRootPath
     * @throws Exception
     */
    protected function updateRepositoryFromXMLData(string $repositoryTitle, string $repositoryRootPath): void
    {
        // create uuid if not present
        $uuidList = $this->root->getElementsByTagName(self::TAG_UUID);
        if ($uuidList->length == 0) {
            $uuid = $this->xml->createElement(self::TAG_UUID, RepositoryHelper::generateGuidV4());
            $this->root->appendChild($uuid);
        }

        // create & update repository title
        $titleList = $this->root->getElementsByTagName(self::TAG_TITLE);
        if ($titleList->length == 0) {
            $title = $this->xml->createElement(self::TAG_TITLE, $repositoryTitle);
            $this->root->appendChild($title);
        } else {
            $titleList->item(0)->nodeValue = $repositoryTitle;
        }

        //add or update repository download root path
        $downPathList = $this->root->getElementsByTagName(self::TAG_DOWNPATH);
        if ($downPathList->length == 0) {
            $downpath = $this->xml->createElement(self::TAG_DOWNPATH, rawurlencode($repositoryRootPath . DIRECTORY_SEPARATOR));
            $this->root->appendChild($downpath);
        } else {
            $downPathList->item(0)->nodeValue = rawurlencode($repositoryRootPath . DIRECTORY_SEPARATOR);
        }

        // add remote package element and store it for later modification
        $remotesList = $this->root->getElementsByTagName(self::TAG_REMOTES);
        if ($remotesList->length == 0) {
            $this->remotes = $this->xml->createElement(self::TAG_REMOTES);
            $this->remotes->setAttribute(self::ATTRIBUTE_COUNT, 0);
            $this->root->appendChild($this->remotes);

        } else {
            //fetch remote element
            $this->remotes = $remotesList->item(0);
        }

        // initialize array for remote packages
        $this->prefillRemotePackagesList();
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
        return $this->remotePackageList->size();
    }

    /**
     * Adds package descriptor to the repository XML
     * @param Package $package
     * @throws Exception
     */
    public function addRemotePackage(Package $package) : void
    {
        //check if already present => remove old entry
        if($this->remotePackageList->contains($package->getIdentifier())) {
            $this->removeRemotePackage($package->getIdentifier());
        }

        $remoteDescriptor = $package->createRemotePackageDescriptor();

        // import mode into the repository document XML
        $nodeCopy = $this->xml->importNode($remoteDescriptor->getRemoteXMLElement(), true);
        $this->remotes->appendChild($nodeCopy);

        // add to package list
        $this->remotePackageList->add($remoteDescriptor);
    }

    /**
     *  Specific removal of a remote package by the ident attribute
     * @param string $packageIdent
     */
    public function removeRemotePackage(string $packageIdent) : void
    {
        /* @var $childNode DOMNode */
        foreach ($this->remotes->childNodes as $childNode) {
            $nodeIdent = $childNode->attributes->getNamedItem(RemotePackageDescriptor::ATTRIBUTE_IDENT)->nodeValue;
            // if package identity has been found
            if (strcmp($nodeIdent, $packageIdent) == 0) {
                // remove node and decrement node count
                $this->remotes->removeChild($childNode);

                // remove from list as well
                $this->remotePackageList->remove($nodeIdent);

                break;
            }
        }
    }

    /**
     * Returns true if the package identifier is already present in the list
     * @param string $packageIdent
     * @return bool
     */
    public function containsRemotePackage(string $packageIdent) : bool
    {
        return $this->remotePackageList->contains($packageIdent);
    }

    /**
     * Removes all remote packages
     */
    public function cleanAllRemotePackages() : void
    {
        //reverse through all child nodes and remove them all
        $count = $this->remotes->childNodes->length;
        if($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $childNode = $this->remotes->childNodes->item(0);  // !!! not item($i) !!!
                $this->remotes->removeChild($childNode);
            }

            // clean list as well
            $this->remotePackageList->clear();
        }
    }

    /**
     * Saves the remote repository XML
     * @throws Exception
     */
    public function saveXML() : void
    {
        if ($this->xml != null) {

            //ensure that the remote count is set before saving the xml
            $this->remotes->attributes->getNamedItem(self::ATTRIBUTE_COUNT)->nodeValue = $this->getRemotePackageCount();

            $bytesWritten = $this->xml->save($this->repositoryFileName);

            if ($bytesWritten === false) {
                throw new Exception("repository XML " . $this->repositoryFileName . " could not be saved");
            }
        }
    }

    /**
     * initializes and fills the all remote packages into the list for quick access
     */
    protected function prefillRemotePackagesList(): void
    {
        $this->remotePackageDescriptorList = array();

        //read in all remote packages
        if ($this->remotes->hasChildNodes()) {
            /* @var $childNode DOMElement */
            foreach ($this->remotes->childNodes as $childNode) {
                $remotePackageDescriptor = new RemotePackageDescriptor($childNode);
                $this->remotePackageList->add( $remotePackageDescriptor);
            }
        }
    }

    /**
     * Returns the remove package descriptor
     * @param string $packageIdentier
     * @return RemotePackageDescriptor
     */
    public function getRemotePackageDescriptor(string $packageIdentier) : RemotePackageDescriptor
    {
        return $this->remotePackageList->get($packageIdentier);
    }


    /**
     * Returns all remote packages that are part of the remote repository
     * @return array
     */
    public function getRemotePackageList(): array
    {
        return $this->remotePackageList->getValues();
    }

    /**
     * Returns the global repository root path where the mods are located
     * @return string
     */
    public function getRepositoryRootPath(): string
    {
        return rawurldecode($this->repositoryRootPath);
    }


}