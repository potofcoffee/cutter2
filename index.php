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

require_once('vendor/autoload.php');

// define some constants
define('CUTTER_version', '3.0.0');
define('CUTTER_software', 'Peregrinus CUTTER '.CUTTER_version);


define('CUTTER_debug', true);
define('CUTTER_basePath', __DIR__.'/');
define('CUTTER_uploadPath', CUTTER_basePath.'Temp/Uploads/');
define('CUTTER_viewPath', CUTTER_basePath.'Resources/Private/Views/');
define('CUTTER_baseUrl',
    (($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http')
    .'://'.$_SERVER['SERVER_NAME'].dirname(parse_url($_SERVER['PHP_SELF'],
            PHP_URL_PATH)).'/');

// error handling stuff:
if (CUTTER_debug) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL && ~E_STRICT);
} else {
    ini_set('display_errors', 0);
}

//ensure all necessary folders are present
\Peregrinus\Cutter\Core\FileSystemUtility::ensureFolderIsPresent('Logs/');
\Peregrinus\Cutter\Core\FileSystemUtility::ensureFolderIsPresent('Temp/');
\Peregrinus\Cutter\Core\FileSystemUtility::ensureFolderIsPresent('Temp/Processed');
\Peregrinus\Cutter\Core\FileSystemUtility::ensureFolderIsPresent('Temp/Uploads');
\Peregrinus\Cutter\Core\FileSystemUtility::ensureFolderIsPresent('Temp/History');

// logging
\Peregrinus\Cutter\Core\Logger::initialize();

// do garbage collection
\Peregrinus\Cutter\Core\GarbageCollector::clean(CUTTER_basePath.'Temp/Processed/',
    '2 days');
\Peregrinus\Cutter\Core\GarbageCollector::clean(CUTTER_basePath.'Temp/Uploads/',
    '2 days');

// start session handling
\Peregrinus\Cutter\Core\Session::initialize();

// get a router and process request
$router = \Peregrinus\Cutter\Core\Router::getInstance();
$router->setDefaultController('acquisition');
$router->dispatch();
