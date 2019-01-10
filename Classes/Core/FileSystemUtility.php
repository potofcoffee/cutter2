<?php
/*
 * cutter2
 *
 * Copyright (c) 2018 Christoph Fischer, https://christoph-fischer.org
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


class FileSystemUtility
{

    public static function ensureFolderIsPresent(string $path, int $mode = 0777, bool $relative = true)
    {
        if ($relative) $path = CUTTER_basePath . $path;
        if (@!is_dir($path)) {
            $errorLevel = error_reporting();
            error_reporting(E_ALL);
            if (!mkdir($path, $mode, true)) throw new \Exception('Couldn\'t create folder '.$path);
            error_reporting($errorLevel);
        }
        if ($mode) chmod($path, $mode);
    }

}