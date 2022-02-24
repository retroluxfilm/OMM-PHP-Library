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


namespace OMM\Package;

use DOMDocument;
use Exception;
use ImagickException;
use ZipArchive;
use OMM\Repository\RemotePackageDescriptor;

/**
 * Holds all information from a single mod package so it can be added, removed & updated into repository
 */
class Package
{

    /**
     * Package descriptor file
     */
    private const PACKAGE_DESCRIPTOR_FILE = "package.omp";

    private PackageXML $packageXML;
    protected string $packageArchiveFile;
    protected string $packageArchiveFilePath;
    protected string $logoImageData;
    protected int $packageByteSize;
    protected string $customURL;

    /**
     * Creates the data object for the mod Package by the given file archive
     * @param string $packageArchiveFilePath
     * @throws Exception
     */
    public function __construct(string $packageArchiveFilePath)
    {
        // check if the file archive exists
        if (!file_exists($packageArchiveFilePath)) {
            throw new Exception("Package archive " . $packageArchiveFilePath . " does not exist");
        }

        // save file and path info
        $this->packageArchiveFilePath = $packageArchiveFilePath;
        $this->packageArchiveFile = pathinfo($packageArchiveFilePath, PATHINFO_BASENAME);

        // get the size of the package
        $this->packageByteSize = filesize($packageArchiveFilePath);

        // open the archive to retrieve information
        $this->retrieveInformationFromArchive($packageArchiveFilePath);
    }


    public function __destruct()
    {
        unset($this->packageXML);
    }

    /**
     * Creates the remote descriptor to be added to the remote repository xml.
     * INFO: this is an an expensive operation and should only be used when the package needs to be added to the repository
     * @return RemotePackageDescriptor
     * @throws ImagickException
     * @throws Exception
     */
    public function createRemotePackageDescriptor(): RemotePackageDescriptor
    {

        //create DOM document to be able to create the remote xml snipped
        $xml = new DOMDocument();
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;

        //create remote root element
        $remote = $xml->createElement(RemotePackageDescriptor::TAG_REMOTE);
        $remote->setAttribute(RemotePackageDescriptor::ATTRIBUTE_IDENT, $this->packageXML->getIdentifier());
        $remote->setAttribute(RemotePackageDescriptor::ATTRIBUTE_FILE, $this->packageArchiveFile);
        $remote->setAttribute(RemotePackageDescriptor::ATTRIBUTE_BYTES, $this->packageByteSize);

        //generate hash (expensive on large files)
        if(in_array("xxh3",hash_algos())){
            // uses the faster xxhash algorithm if available (PHP 8.1+)
            $remote->setAttribute(RemotePackageDescriptor::ATTRIBUTE_CHECKSUM, PackageHelper::calculatePackageHash($this->packageArchiveFilePath,"xxh3"));
        } else {
            $remote->setAttribute(RemotePackageDescriptor::ATTRIBUTE_MD5, PackageHelper::calculatePackageHash($this->packageArchiveFilePath,"md5"));
        }

        // add dependencies if defined
        $dependencies = $this->packageXML->getDependencies();
        if (isset($dependencies[0])) {
            $dom_sxe = dom_import_simplexml($dependencies);
            $convertedDependencyNode = $xml->importNode($dom_sxe, true);
            $remote->appendChild($convertedDependencyNode);
        }

        // add logo image if set
        if (isset($this->logoImageData)) {

            // Crop & Resize logo to max 128x128 pixels in size
            $thumbnail = PackageHelper::createThumbnail($this->logoImageData);

            $picture = $xml->createElement(RemotePackageDescriptor::TAG_PICTURE, PackageHelper::encodePackageLogo($thumbnail));
            $remote->appendChild($picture);
         }

        // set description when set
        $rawDescription = $this->packageXML->getDescription();
        if (!empty($rawDescription)) {
            $encodedTextData = PackageHelper::encodePackageDescription($rawDescription);

            $description = $xml->createElement(RemotePackageDescriptor::TAG_DESCRIPTION, $encodedTextData["encodedText"]);
            $description->setAttribute(RemotePackageDescriptor::ATTRIBUTE_BYTES, $encodedTextData["bytes"]);
            $remote->appendChild($description);
        }

        //set sub folder download path if set
        if(isset($this->customURL)){
            $downpath = $xml->createElement(RemotePackageDescriptor::TAG_URL, rawurldecode($this->customURL));
            $remote->appendChild($downpath);
        }

        // set category if defined
        $category = $this->packageXML->getCategory();
        if(!empty($category)){
            $remote->setAttribute(RemotePackageDescriptor::ATTRIBUTE_CATEGORY, $category);
        }


        $xml->appendChild($remote);

        return new RemotePackageDescriptor($remote);
    }

    /**
     * Retrieves all information from the package archive
     * @param string $packageArchiveFilePath
     * @throws Exception
     */
    protected function retrieveInformationFromArchive(string $packageArchiveFilePath): void
    {

        // zip file handle
        $packageZip = new ZipArchive();

        $result = $packageZip->open($packageArchiveFilePath, ZipArchive::RDONLY);
        if ($result != true) {
            throw new Exception("Could not open package zip file. Error: " . $result);
        }

        // read package descriptor contents
        $packageDescriptorData = $packageZip->getFromName(self::PACKAGE_DESCRIPTOR_FILE);
        if ($packageDescriptorData === false) {
            throw new Exception(
                "OMM Package Descriptor (" . self::PACKAGE_DESCRIPTOR_FILE . ") not found or invalid in" . $packageArchiveFilePath
            );
        }

        // load package XML from the descriptor file
        $this->packageXML = new PackageXML($packageDescriptorData);

        // load logo image if present
        $logoImageName = $this->packageXML->getLogoImage();
        if (!empty($logoImageName)) {
            $this->logoImageData = $packageZip->getFromName($logoImageName);
            if ($this->logoImageData === false) {
                throw new Exception(
                    "OMM Package Logo (" . $logoImageName . ") not found or invalid in " . $packageArchiveFilePath
                );
            }
        }

        // closes package zip after reading out all required information
        $packageZip->close();
    }

    /**
     * Adds custom URL download path. It can be a http url or a folder.
     * @param string $urlOrFolder
     */
    public function setCustomURL(string $urlOrFolder)
    {
        $this->customURL = $urlOrFolder;
    }

    /**
     * Gets the package xml from the package to get more information about the package
     * @return PackageXML
     */
    public function getPackageXML(): PackageXML
    {
        return $this->packageXML;
    }

    /**
     * Returns the unique identifier for the package
     * @return string
     */
    public function getIdentifier() : string
    {
        return $this->packageXML->getIdentifier();
    }

    /**
     * Returns the size of the package archive in bytes
     * @return int
     */
    public function getByteSize() : int
    {
        return $this->packageByteSize;
    }

}