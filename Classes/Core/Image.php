<?php
/*
 * CUTTER
 * Versatile Image Cutter and Processor
 * http://github.com/potofcoffee/cutter
 *
 * Copyright (c) Christoph Fischer, https://christoph-fischer.org
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

namespace Peregrinus\Cutter\Core;

/**
 * Description of Image
 *
 * @author chris
 */
class Image
{
    protected $image = null;

    public function __construct($image)
    {
        $this->image = $image;
    }

    public function resize($x, $y, $w1, $h1, $w2, $h2)
    {
        $dstImage    = ImageCreateTrueColor($w1, $h1);
        imagecopyresampled($dstImage, $this->image, 0, 0, $x, $y, $w1, $h1, $w2,
            $h2);
        $this->image = $dstImage;
    }

    public function toJpeg($destinationFile, $quality)
    {
        imagejpeg($this->image, $destinationFile, $quality);
    }

    /**
     * Print the legal text on the picture
     * @param string $legalText Legal text
     * @param int $w Picture width
     * @param int $h Picture height
     * @param \Peregrinus\Cutter\Core\Color $color Color object
     */
    public function setLegalText($legalText, $w, $h, $color)
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('setLegalText()');

        $minHeight = 15;
        $font      = CUTTER_basePath.'Assets/Fonts/OpenSans.ttf';

        $size   = 0;
        $height = 0;

        while (($height < $minHeight) && ($size < 100)) {
            $size++;

            // calculate bounding box
            $box    = imagettfbbox($size, 0, $font, $legalText);
            $height = abs($box[7] - $box[1]);
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(print_r(array('size' => $size,
                'height' => $height, 'bbox' => $box,
                    ), 1));
        }
        $size--;
        $box = imagettfbbox($size, 0, $font, $legalText);
        $x   = abs($box[5] - $box[1]);
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(
            'Legal font size is '.$size);
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(
            'Legal font file is '.$font);

        // get color
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(
            'Text color: '.print_r($color, 1));
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(
            'Palette usage for this image: '.imagecolorstotal($this->image));
        $imgColor = imagecolorexact($this->image, $color->R, $color->G,
            $color->B);
        if ($imgColor == -1) {
            $imgColor = imagecolorallocate($this->image, $color->R, $color->G,
                $color->B);
        }
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(
            'Palette index for this color: '.$imgColor);


// insert source:
        imagettftext($this->image, $size, 90, $x, $h - 5, $imgColor, $font,
            $legalText);
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(
            'Done inserting legal text.');
    }

    /**
     * Get the width of the image
     * @return int Width of the image
     */
    public function getWidth() {
        return imagesx($this->image);
    }

    /**
     * Get the height of the image
     * @return int Height
     */
    public function getHeight() {
        return imagesy($this->image);
    }


    public function addOverlayText($text, $font, $size, $alignment) {

    }
}