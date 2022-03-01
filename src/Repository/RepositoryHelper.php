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

use Exception;

/**
 * Helper class for the remote repository
 */
class RepositoryHelper
{

    /**
     * Generates UUID V4
     * @return string
     * @throws Exception
     */
    public static function generateGuidV4(): string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Encodes an file path so that the url can be used in a link. It preserves the directory separators (slashes)
     * @param string $path
     * @return string
     */
    public static function urlEncodePath(string $path) : string{

        // first url raw encode elements between directory seperator
        $urlEncoded = implode(DIRECTORY_SEPARATOR, array_map("rawurlencode", explode(DIRECTORY_SEPARATOR, $path)));
        // remove first relative path point and seperator
        $urlEncoded = str_replace("." + DIRECTORY_SEPARATOR,"",$urlEncoded);
        // convert directory seperator to forward slashes
        $urlEncoded = str_replace(DIRECTORY_SEPARATOR,"/",$urlEncoded);
        return $urlEncoded;
    }
}