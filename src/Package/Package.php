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
use DOMElement;
use Exception;
use ZipArchive;

/**
 * Holds all information from a single mod package so it can be added, removed & updated into repository
 */
class Package
{

    private const PACKAGE_DESCRIPTOR_FILE = "package.omp";

    public PackageXML $packageXML;
    private string $packageArchiveFile;
    private string $packageArchiveFilePath;
    private string $logoImageData;
    private int $packageByteSize;

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

            // Crop & Resize logo to max 128x128 pixels in size
            $this->logoImageData = PackageHelper::createThumbnail($this->logoImageData);
        }

        // closes package zip after reading out all required information
        $packageZip->close();
    }


    public function __destruct()
    {
        unset($this->packageXML);
    }

    /**
     * Generates the remote descriptor to be added to the remote repository xml
     * @return DOMElement
     */
    public function getRemoteDescriptor(): DOMElement
    {
        //create DOM document to be able to create the remote xml snipped
        $xml = new DOMDocument();
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;

        //create remote root element
        $remote = $xml->createElement("remote");
        $remote->setAttribute("ident", $this->packageXML->getIdentifier());
        $remote->setAttribute("file", $this->packageArchiveFile);
        $remote->setAttribute("bytes", $this->packageByteSize);
        $remote->setAttribute("md5sum", PackageHelper::calculatePackageMD5Hash($this->packageArchiveFilePath));

        // add dependencies if defined
        $dependencies = $this->packageXML->getDependencies();
        if (isset($dependencies[0])) {
            $dom_sxe = dom_import_simplexml($dependencies);
            $convertedDependencyNode = $xml->importNode($dom_sxe, true);
            $remote->appendChild($convertedDependencyNode);
        }

        // add logo image if set
        if ($this->logoImageData != false) {
            $picture = $xml->createElement("picture", PackageHelper::encodePackageLogo($this->logoImageData));
            $remote->appendChild($picture);
        }

        // set description when set
        $rawDescription = $this->packageXML->getDescription();
        if (!empty($rawDescription)) {
            $encodedTextData = PackageHelper::encodePackageDescription($rawDescription);

            $description = $xml->createElement("description", $encodedTextData["textdata"]);
            $description->setAttribute("bytes", $encodedTextData["bytes"]);
            $remote->appendChild($description);
        }

        $xml->append($remote);
        return $remote;
    }

}