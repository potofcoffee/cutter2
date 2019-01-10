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

namespace VMFDS\Cutter\Factories;

use VMFDS\Cutter\Providers\DefaultFetchFromUrlProvider;

/**
 * Description of ProviderFactory
 *
 * @author chris
 */
class ProviderFactory extends AbstractFactory
{

    static public function getProviderNames()
    {
        $classList = self::getAllClasses('Provider');
        $names     = array();
        foreach ($classList as $class) {
            $names[] = $class::getName();
        }
        return $names;
    }

    static public function getByName($name)
    {
        $found     = false;
        $classList = self::getAllClasses('Provider');
        foreach ($classList as $class) {
            if ($class::getName() == $name) $found = $class;
        }
        if ($found) return new $class();
        else return false;
    }

    static public function getHostHandler($url)
    {
        $found     = false;
        $host      = parse_url($url, PHP_URL_HOST);
        $classList = self::getAllClasses('Provider');
        foreach ($classList as $class) {
            if ($class::canHandleHost($host)) {
                if (!$found) $found = new $class();
            }
        }
        if (!$found) {
            //throw new \Exception('No provider found for host "'.$host.'"');
            $found = new DefaultFetchFromUrlProvider();
        }
        return $found;
    }
}