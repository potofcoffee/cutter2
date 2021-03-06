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

namespace Peregrinus\Cutter\Core;

/**
 * Description of Logger
 *
 * @author chris
 */
class Logger
{
    static protected $instance = null;
    protected $logger          = null;

    /**
     * Get an instance of the request object
     * @return \Peregrinus\Cutter\Core\Logger Instance of session object
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    static public function getLogger()
    {
        $me = self::getInstance();
        return $me->logger;
    }

    static public function initialize()
    {
        // call getInstance to force construction of new instance
        $me = self::getInstance();
    }

    protected function __construct()
    {
        $this->logger = new \Monolog\Logger('cutter');
        if (CUTTER_debug) {
            $this->logger->pushHandler(new \Monolog\Handler\StreamHandler(
                CUTTER_basePath.'Logs/cutter.debug.log', \Monolog\Logger::DEBUG));
        }
        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler(
            CUTTER_basePath.'Logs/cutter.notice.log', \Monolog\Logger::NOTICE));
    }

    final private function __clone()
    {

    }
}