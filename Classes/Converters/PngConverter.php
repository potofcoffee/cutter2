<?php
/*
 * CUTTER
 * Versatile Image Cutter and Processor
 * http://github.com/VolksmissionFreudenstadt/cutter
 *
 * Copyright (c) 2015 Volksmission Freudenstadt, http://www.volksmission-freudenstadt.de
 * Author: Christoph Fischer, chris@toph.de
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
 */

namespace VMFDS\Cutter\Converters;

/**
 * Description of JpegConverter
 *
 * @author chris
 */
class PngConverter extends AbstractConverter
{

    /**
     * Checks if this converter can handle a given image
     * @param \string $imageFile Image file name
     * @return boolean True if image can be handled
     */
    static function canHandleImage($imageFile)
    {
        return (self::getMimeType($imageFile) == 'image/png');
    }

    /**
     * Create image resource from jpeg file
     *
     * @param string $imageFile Path to the image file
     * @return image
     */
    public function getImage($imageFile)
    {
        return imagecreatefrompng($imageFile);
    }
}