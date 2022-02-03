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

namespace OMM\Repository;

use DOMElement;
use InvalidArgumentException;

/**
 * Remote Package identifier that includes all information from a package that is required to add it to a remote repository
 */
class RemotePackageDescriptor
{

    /**
     * Tags of the remote package descriptor
     */
    public const
        TAG_REMOTE = "remote",
        TAG_PICTURE = "picture",
        TAG_DESCRIPTION = "description",
        TAG_URL = "url",
        TAG_CATEGORY = "category";
    /**
     * Attributes of the remote package descriptor
     */
    public const
        ATTRIBUTE_IDENT = "ident",
        ATTRIBUTE_FILE = "file",
        ATTRIBUTE_BYTES = "bytes",
        ATTRIBUTE_MD5 = "md5sum";

    /**
     * remote xml element to fetch data from
     * @var DOMElement
     */
    private DOMElement $remoteXMLElement;


    /**
     * Constructs the remote package descriptor with the remote xml tag with its contents
     * to describe a remote package
     * @param DOMElement $remoteXMLElement remote tag xml element
     */
    public function __construct(DOMElement $remoteXMLElement)
    {

        if($remoteXMLElement->nodeName != self::TAG_REMOTE){
            throw new InvalidArgumentException("Passed remote xml element is not valid");
        }

        //this is the remote tag
        $this->remoteXMLElement = $remoteXMLElement;
    }

    /**
     * @return string
     */
    public function getIdentity(): string
    {
        return $this->remoteXMLElement->getAttribute(self::ATTRIBUTE_IDENT);
    }

    public function getByteSize() : string
    {
        return $this->remoteXMLElement->getAttribute(self::ATTRIBUTE_BYTES);
    }

    public function getMD5Hash() : string
    {
        return $this->remoteXMLElement->getAttribute(self::ATTRIBUTE_MD5);
    }

    /**
     * @return DOMElement
     */
    public function getRemoteXMLElement(): DOMElement
    {
        return $this->remoteXMLElement;
    }

}