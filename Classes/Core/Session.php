<?php

namespace VMFDS\Cutter\Core;

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

// just a wrapper around PHP's session handling
class Session
{
    const SESSION_KEY = 'VMFDS\\Cutter';

    static private $instance = NULL;
    static private $started  = false;
    protected $conf          = array();

    protected function __construct()
    {
        $this->initialize();
    }

    final private function __clone()
    {

    }

    /**
     * Get an instance of the session object
     * @return \VMFDS\Cutter\Core\Session Instance of session object
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Start session processing
     * @return void
     */
    static public function initialize()
    {
        if (session_status() != PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * Checks if a specific argument is present in the session
     * @param \string $argument Argument name
     * @return \bool True if argument exists
     */
    public function hasArgument($argument)
    {
        return isset($_SESSION[self::SESSION_KEY][$argument]);
    }

    /**
     * Get a specific argument from the session
     * @param \string $argument Argument name
     * @param variant Argument value or FALSE if argument not present
     */
    public function getArgument($argument)
    {
        return ($this->hasArgument($argument) ? $_SESSION[self::SESSION_KEY][$argument]
                    : false);
    }

    /**
     * Set a session argument
     * @param \string $argument Argument name
     * @param variant $value Argument value
     * @return void
     */
    public function setArgument($argument, $value)
    {
        $_SESSION[self::SESSION_KEY][$argument] = $value;
    }

    /**
     * Get all session arguments
     * @return array Arguments
     */
    public function getArguments()
    {
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Clear the session
     * @return void
     */
    public function clear()
    {
        $_SESSION[self::SESSION_KEY] = array();
    }
}