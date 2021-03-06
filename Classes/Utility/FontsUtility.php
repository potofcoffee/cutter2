<?php
/*
 * cutter2
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

namespace Peregrinus\Cutter\Utility;

class FontsUtility
{

    /**
     * Get info on all available truetype fonts
     * @return array
     */
    public static function getAllFontAssets(): array {
        $fonts = [];
        foreach (glob(CUTTER_basePath.'Assets/Fonts/*.ttf') as $fontFile) {
            $fontName = join(' ', StringUtility::camelCaseToWords(pathinfo($fontFile, PATHINFO_FILENAME)));
            $fonts[$fontName] = [
                'name' => $fontName,
                'file' => $fontFile,
            ];
        }
        ksort($fonts);
        return $fonts;
    }
}