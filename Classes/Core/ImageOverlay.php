<?php
/*
 * cutter2
 *
 * Copyright (c) 2019 Christoph Fischer, https://christoph-fischer.org
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

namespace VMFDS\Cutter\Core;

class ImageOverlay extends \Imagick {

    const TEXTALIGN_LEFT = 0;
    const TEXTALIGN_CENTER = 1;
    const TEXTALIGN_RIGHT = 2;

    protected $width;
    protected $height;

    /**
     * Initialize image object
     * @param int $width Width
     * @param int $height Height
     * @param string $backgroundColor Background color
     * @param string $format Image format
     */
    public function __construct($width, $height, $backgroundColor = 'white', $format = 'jpeg') {
        parent::__construct();
        $this->width = $width;
        $this->height = $height;
        $this->newimage($width, $height, new \ImagickPixel($backgroundColor), $format);
    }

    /**
     *
     * @param string Image file
     * @param int $composite Composite operator
     * @param int $x The column offset of the composited image
     * @param int $y The row offset of the composited image
     * @param int $width Optional: The width to which the composited image will be scaled.
     * @param int $height Optional: The height to which the composited image will be scaled.
     * @param int $channel Provide any channel constant that is valid for your channel mode. To apply to more than one channel, combine channeltype constants using bitwise operators.
     * @return bool true if success
     */
    public function compositeImageFile($imageFile, $composite, $x, $y, $width = 0, $height = 0, $channel = \Imagick::CHANNEL_ALL) {
        $tmpImage = new \Imagick($imageFile);
        if (($width > 0) || ($height > 0)) {
            $tmpImage->scaleimage($width, $height);
        }
        return $this->compositeimage($tmpImage, $composite, $x, $y, $channel);
    }

    /**
     * Write a text to an image
     *
     * @param string $text Text
     * @param string $fontFile Font file
     * @param int $fontSize Font size
     * @param string $fontColor Font color
     * @param int $x The column offset of the text
     * @param int $y The row offset of the text
     * @param int $angle The angle of the text
     * @param int $align The alignment of the text (currently only left-aligned text is supported)
     */
    public function text($text, $fontFile, $fontSize, $fontColor, $x, $y, $angle) {
        $tmpDraw = new \ImagickDraw();
        $tmpDraw->setfont($fontFile);
        $tmpDraw->setfontsize($fontSize);
        $tmpDraw->setfillcolor($fontColor);

        $bbox = $this->queryfontmetrics($tmpDraw, $text);
        if ($align == self::TEXTALIGN_CENTER) {
            $x -= floor($bbox['textWidth'] / 2);
        } elseif ($align == self::TEXTALIGN_RIGHT) {



            $x -= $bbox['textWidth'];
        }
        $this->annotateimage($tmpDraw, $x, $y, $angle, $text);
        //TODO: Implement other alignments
    }

    /**
     * Draw a filled circle
     *
     * @param \ImagickPixel $strokeColor
     * @param \ImagickPixel $fillColor
     * @param type $originX
     * @param type $originY
     * @param type $radius
     */
    public function circle($strokeColor, $fillColor, $originX, $originY, $radius) {

        $draw = new \ImagickDraw();

        $strokeColor = new \ImagickPixel($strokeColor);
        $fillColor = new \ImagickPixel($fillColor);

        $draw->setStrokeOpacity(1);
        $draw->setStrokeColor($strokeColor);
        $draw->setFillColor($fillColor);

        $draw->setStrokeWidth(2);
        $draw->setFontSize(72);

        $draw->circle($originX, $originY, $originX + $radius, $originY);

        $this->drawImage($draw);
    }

    public function getTextMetrics ($text, $fontFile, $fontSize) {
        $tmpDraw = new \ImagickDraw();
        $tmpDraw->setfont($fontFile);
        $tmpDraw->setfontsize($fontSize);
        return $this->queryFontMetrics($tmpDraw, $text);
    }

    /**
     * Write a word-wrapped text block to the image
     *
     * @author BMiner
     * @author Christoph Fischer <chris@toph.de>
     * @link http://stackoverflow.com/questions/5746537/how-can-i-draw-wrapped-text-using-imagick-in-php/5746551#5746551
     * @param string $text Text
     * @param string $fontFile Font file
     * @param int $fontSize Font size
     * @param string $fontColor Font color
     * @param int $x The column offset of the text
     * @param int $y The row offset of the text
     * @param int $maxWidth the maximum width in pixels for your wrapped "virtual" text box
     * @param int $alignment Text aligment
     */
    public function textBlock($text, $fontFile, $fontSize, $fontColor, $startX, $startY, $maxWidth, $alignment) {
        $tmpDraw = new \ImagickDraw();
        $tmpDraw->setfont($fontFile);
        $tmpDraw->setfontsize($fontSize);
        $tmpDraw->setfillcolor($fontColor);

        $words = explode(" ", $text);
        $lines = array();
        $i = 0;
        $lineHeight = 0;
        while ($i < count($words)) {
            $currentLine = $words[$i];
            if ($i + 1 >= count($words)) {
                $lines[] = $currentLine;
                break;
            }
            //Check to see if we can add another word to this line
            $metrics = $this->queryFontMetrics($tmpDraw, $currentLine . ' ' . $words[$i + 1]);
            while ($metrics['textWidth'] <= $maxWidth) {
                //If so, do it and keep doing it!
                $currentLine .= ' ' . $words[++$i];
                if ($i + 1 >= count($words))
                    break;
                $metrics = $this->queryFontMetrics($tmpDraw, $currentLine . ' ' . $words[$i + 1]);
            }
            //We can't add the next word to this line, so loop to the next line
            $lines[] = $currentLine;
            $i++;
            //Finally, update line height
            if ($metrics['textHeight'] > $lineHeight)
                $lineHeight = $metrics['textHeight'];
        }

        // Write to the image
        $y = $startY;

        // negative starting x = calculate from right border
        if ($startX < 0) $startX = $this->width-(abs($startX)+$maxWidth);

        // negative starting height = calculate from bottom up:
        if ($y < 0 ) $y = $this->height-(abs($y)+(count($lines)*$lineHeight));


        foreach ($lines as $line) {
            if (substr($line, 0, 1) == utf8_decode('·'))
                $line = trim(substr($line, 2));
            //$this->annotateImage($tmpDraw, $startX, $y, 0, $line);

            $metrics = $this->queryFontMetrics($tmpDraw, $line);
            switch ($alignment) {
                case self::TEXTALIGN_LEFT:
                    $x = $startX;
                    break;
                case self::TEXTALIGN_CENTER:
                    $x = $startX+floor(($maxWidth-$metrics['textWidth'])/2);
                    break;
                case self::TEXTALIGN_RIGHT:
                    $x = $startX+($maxWidth-$metrics['textWidth']);
                    break;
            }

            $this->text($line, $fontFile, $fontSize, $fontColor, $x, $y, 0, $alignment);
            $y += $lineHeight;
        }

        // return total height
        return $y - $startY;
    }

    function rectangle($x1, $y1, $x2, $y2, $strokeColor, $fillColor, $backgroundColor) {
        $draw = new \ImagickDraw();
        $strokeColor = new \ImagickPixel($strokeColor);
        $fillColor = new \ImagickPixel($fillColor);

        if ($strokeColor) {
            $draw->setStrokeColor($strokeColor);
            $draw->setStrokeOpacity(1);
            $draw->setStrokeWidth(2);
        } else {
            $draw->setStrokeWidth(0);
        }
        $draw->setFillColor($fillColor);

        $draw->rectangle($x1, $y1, $x2, $y2);

        $this->drawImage($draw);
    }

}
