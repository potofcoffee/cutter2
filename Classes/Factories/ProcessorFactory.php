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

/**
 * Description of TemplateFactory
 *
 * @author chris
 */
class ProcessorFactory extends AbstractFactory
{

    /**
     * Get all glyphicons
     * @return array Glyphicon info
     */
    static public function getIcons()
    {
        $classList = self::getAllClasses('Processor');
        $info      = array();
        foreach ($classList as $class) {
            $processor                  = new $class();
            $info[$processor->getKey()] = $processor->getIcon();
        }
        return $info;
    }
}