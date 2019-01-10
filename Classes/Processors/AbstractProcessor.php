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

namespace VMFDS\Cutter\Processors;

/**
 * Description of AbstractProcessor
 *
 * @author chris
 */
class AbstractProcessor
{
    const RESULT_OK       = 1;
    const RESULT_FALLBACK = 2;

    protected $icon        = '';
    protected $localConfig = '';

    /**
     * Get the glyphicon for this processor
     * @return \string Glyphicon
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Get this processors's key (class without namespace and 'Provider')
     * @return \string
     */
    public function getKey()
    {
        $class = get_class($this);
        return str_replace('Processor', '',
            str_replace('VMFDS\\Cutter\\Processors\\', '', $class));
    }

    function __construct()
    {

    }

    public function getAdditionalFields()
    {
        return array();
    }

    public function setOptionsArray($arr)
    {
        $this->localConfig = $arr;
    }

    public function setOption($key, $value)
    {
        $this->localConfig[$key] = $value;
    }

    public function getOption($key)
    {
        return (isset($this->localConfig[$key]) ? $this->localConfig[$key] : false);
    }

    /**
     * Process an image file
     * @param \string $fileName Path to file
     * @param array $options Options
     * @return variant Return values
     */
    public function process($fileName, $options)
    {
        return array('result' => self::RESULT_OK);
    }

    /**
     * Get a list of required arguments
     * @return array List of required arguments
     */
    public function requiresArguments()
    {
        return array();
    }

    /**
     * Check if all required arguments are present
     * @param array $data Data array
     * @return boolean True if all required arguments are present
     * @throws \Exception if a required argument is missing
     */
    protected function checkRequiredArguments($data)
    {
        $args = $this->requiresArguments();
        foreach ($args as $arg) {
            if (!isset($data[$arg])) {
                \VMFDS\Cutter\Core\Logger::getLogger()->addError(
                    'Required argument "'.$arg.'" not passed to '.get_called_class());
                throw new \Exception('Required argument "'.$arg.'" not passed to '.get_called_class());
            }
        }
        return true;
    }
}