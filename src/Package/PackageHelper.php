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
use finfo;
use Imagick;
use ImagickException;
use ImagickPixel;
use InvalidArgumentException;


/**
 * Helper class for the package object to prepare data for the repository
 */
class PackageHelper
{
    private const THUMBNAIL_SIZE = 128;

    /**
     * Encodes the logo image for the package to be readable by OMM
     * @param string $imageRawData
     * @return string
     * @throws Exception
     */
    public static function encodePackageLogo(string $imageRawData): string
    {
        //The snapshot/logo must be the Base64 encoded binary data of a JPEG format square image of 128 x 128 pixels.
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = $fileInfo->buffer($imageRawData);

        $encodedImageData = base64_encode($imageRawData);

        //encoding was successful
        if ($encodedImageData != false && $contentType != false) {
            //return type and encoded data
            return "data:" . $contentType . ";base64," . $encodedImageData;
        } else {
            // encoding was not successful
            throw new Exception("Logo file could not be encoded properly.");
        }

    }

    /**
     * Encodes the given description text for the package into the required format for OMM.
     * Will return an array with "bytes" and "encodedText" in it.
     * @param string $rawDescriptionText
     * @return array
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function encodePackageDescription(string $rawDescriptionText): array
    {
        //no text was given
        if (empty($rawDescriptionText)) {
            throw new InvalidArgumentException("No description text was given to be encoded.");
        }

        //copy raw text
        $descriptionText = $rawDescriptionText;

        // convert line endings from LF to CRLF
        $descriptionText = self::convertLineEndings($descriptionText);

        //utf8 encode text
        $descriptionText = utf8_encode($descriptionText);

        // fetch byte length of the uncompressed utf8 text
        $byteSize = mb_strlen($descriptionText, '8bit') + 1; // +1 to add final null char in length

        //compress string with the zlip deflate method
        $descriptionText = gzcompress($descriptionText, 9);
        if ($descriptionText === false) {
            throw new Exception("Could not compress the given text.");
        }

        // encode text as base64
        $base64EncodedText = base64_encode($descriptionText);

        return array(
            "bytes" => $byteSize,
            "encodedText" => "data:application/octet-stream;base64," . $base64EncodedText
        );
    }

    /**
     * Converts Unix LF line endings to CRLF line endings
     * @param $string
     * @return array|string|string[]|null
     */
    private static function convertLineEndings($string)
    {
        return preg_replace("/(?<=[^\r]|^)\n/", "\r\n", $string);
    }

    /**
     * Calculates the hash of the of the file by using the same algorithm the OMM is using.
     * @param string $filePath
     * @param string $algorithm Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
     * @param bool $skipTest if true it will skip the internal check if the given algorithm is available
     * @return string
     * @throws Exception
     */
    public static function calculatePackageHash(string $filePath, string $algorithm, bool $skipTest = true): string
    {
        // fetch the MD5 hash of the given file
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Package file '" . $filePath . "' not found.");
        }

        //check if the given has algorithm is present
        if(!$skipTest && !in_array($algorithm,hash_algos())) {
            throw new InvalidArgumentException("Hashing algorithm '" . $algorithm . "' not found.");
        }

        //calculate xxhash
        $checksum =  hash_file($algorithm,   $filePath);

        if ($checksum != false) {
            return $checksum;
        } else {
            throw new Exception("Package '" . $filePath . "' file hash '" . $algorithm. "' could not be calculated.");
        }
    }

    /**
     * creates the thumbnail from the logo of the package to the required size constrains
     * @param string $logoImageData
     * @return string
     * @throws ImagickException
     */
    public static function createThumbnail(string $logoImageData): string
    {
        $image = new Imagick();
        $image->readImageBlob($logoImageData);
        $image->cropThumbnailImage(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);

        //ensures that the thumbnail is in jpeg data format as it is currently required for OMM remote repositories
        $image->setImageFormat('jpeg');
        $image->setImageCompression(imagick::COMPRESSION_JPEG);
        $image->setCompressionQuality(60);

        // set default background color in case there is an alpha image in the source
        $image->setImageBackgroundColor(new ImagickPixel('white'));

        $scaledImage = $image->getImageBlob();
        $image->destroy();
        return $scaledImage;
    }

}