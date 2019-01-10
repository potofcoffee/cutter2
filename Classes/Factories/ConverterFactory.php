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

namespace Peregrinus\Cutter\Factories;

use Peregrinus\Cutter\Utility\FileUtility;

/**
 * Description of ProviderFactory
 *
 * @author chris
 */
class ConverterFactory extends AbstractFactory
{

    static protected function getMimeType($file)
    {
        return FileUtility::getMimeType($file);
    }

    static public function getFileHandler($imageFile)
    {
        $found     = false;
        $classList = self::getAllClasses('Converter');
        foreach ($classList as $class) {
            if ($class::canHandleImage($imageFile)) {
                if (!$found) $found = new $class();
            }
        }
        if (!$found)
                throw new \Exception('No converter found for file "'.$imageFile.'" (Mime-Type: '.self::getMimeType($imageFile).')');
        return $found;
    }
}