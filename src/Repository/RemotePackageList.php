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

/**
 * Offers an easy interface to handle multiple remote packages in a array list
 */
class RemotePackageList
{

    /**
     * Array that stores package descriptors
     * @var array
     */
    private array $remotePackageDescriptorList;

    /**
     * Creates a list that holds package descriptors as values and identities as key
     */
    public function __construct()
    {
        $this->remotePackageDescriptorList = array();
    }

    public function __deconstruct(){
        unset($remotePackageDescriptorList);
    }

    /**
     * Adds new entry to the list by their identity.
     * Info: As identities are used as unique identifier in the list as key.
     * Therefore it will replace entries with the same identity
     * @param RemotePackageDescriptor $remotePackageDescriptor
     */
    public function add(RemotePackageDescriptor $remotePackageDescriptor): void
    {
        $this->remotePackageDescriptorList[$remotePackageDescriptor->getIdentity()] = $remotePackageDescriptor;
    }

    /**
     * Removes the remote package by the identifier
     * @param string $ident
     */
    public function removeByIdent(string $ident): void
    {
        unset($this->remotePackageDescriptorList[$ident]);
    }

    /**
     * Removes the entry by the remote package descriptor
     * @param RemotePackageDescriptor $package
     */
    public function removeByPackage(RemotePackageDescriptor $package): void
    {
       $this->removeByIdent($package->getIdentity());
    }

    /**
     * clears all entry from the list
     */
    public function clear(): void
    {
        unset($this->remotePackageDescriptorList);
        $this->remotePackageDescriptorList = array();
    }

    /**
     * Returns the amount of entries in the list
     * @return int
     */
    public function size(): int
    {
        return count($this->remotePackageDescriptorList);
    }


}