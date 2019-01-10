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
use Peregrinus\Cutter\Core\ConfigurationManager;
use Peregrinus\Cutter\Core\Template;

/**
 * Description of TemplateFactory
 *
 * @author chris
 */
class TemplateFactory extends AbstractFactory
{

    static protected $configuration = [];

    static protected function configure() {
        if (!count(self::$configuration)) self::$configuration = ConfigurationManager::getInstance()->getConfigurationSet('templates');
    }

    static public function getAllClasses($type) {
        self::configure();
        $result = [];
        foreach (self::$configuration as $category => $configuration) {
            foreach ($configuration as $set) {
                $result[] = Template::class;
            }
        }
        return $result;
    }


    /**
     * Get Template info
     * @return type
     */
    static public function getTemplateInfo()
    {
        self::configure();
        $info = [];
        foreach (self::$configuration as $category => $configuration) {
            foreach ($configuration as $set => $setConfig) {
                $template = new Template($category, $set);
                $info[$category][$set] = $template->getTemplateInfo();
            }
        }
        return $info;
    }

    /**
     * Get Template object
     * @param \string $key Key
     * @return object Template Object
     */
    static public function get($category, $set)
    {
        return new Template($category, $set);
    }
}