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

use Exception;
use SimpleXMLElement;

/**
 * Package Descriptor XML
 */
class PackageXML
{
    protected SimpleXMLElement $xml;

    /**
     * Creates Package XML
     * @throws Exception
     */
    public function __construct(string $packageXMLData)
    {
        // usually named package.omp
        $this->xml = simplexml_load_string($packageXMLData);

        if ($this->xml === false) {
            foreach (libxml_get_errors() as $error) {
                error_log($error->message);
            }
            throw new Exception("Failed loading XML: " . $packageXMLData);
        }
    }

    public function __destruct()
    {
        //release memory for the xml
        unset($this->xml);
    }

    /**
     * Returns the package identifier
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->xml->install;
    }

    /**
     * Returns the dependency list as XML Element
     * @return SimpleXMLElement
     */
    public function getDependencies(): SimpleXMLElement
    {
        //return dependency list
        return $this->xml->dependencies;
    }

    /**
     * Get package description
     * @return string
     */
    public function getDescription(): string
    {
        // return description of the package
        return (string)$this->xml->description;
    }

    /**
     * Get package logo
     * @return string
     */
    public function getLogoImage(): string
    {
        //return logo from the package
        return (string)$this->xml->picture;
    }


}