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

class View
{
    private $viewFile       = '';
    private $viewPath       = CUTTER_viewPath;
    private $viewExtension  = 'html';
    private $loader         = null;
    private $renderer       = null;
    private $arguments      = array();
    private $renderMultiple = false;
    private $rendered       = false;
    private $contentType    = 'text/html';

    public function __construct($actionName)
    {
        $this->viewFile = ucfirst($actionName);
        // assign baseUrl
        $this->assign('baseUrl', CUTTER_baseUrl);
    }

    /**
     * Set a new view path
     * @param \string $viewPath Path to views
     */
    function setViewPath($viewPath)
    {
        $this->viewPath = $viewPath;
    }

    /**
     * Assign a view argument
     * @param \string $argument Argument name
     * @param variant $value Value
     * @return void
     */
    public function assign($argument, $value)
    {
        $this->arguments[$argument] = $value;
    }

    /**
     * Render the view
     * @return \string Rendered view
     */
    public function render()
    {
        $viewFile = $this->viewFile.'.'.$this->viewExtension;
        if (!$this->rendered || $this->renderMultiple) {
            $cacheConfig = array();
            if (!CUTTER_debug) {
                $cacheConfig = array('cache' => CUTTER_basePath.'Temp/Cache');
            }
            $this->loader   = new \Twig_Loader_Filesystem($this->viewPath);
            $this->renderer = new \Twig_Environment($this->loader, $cacheConfig);
            $this->rendered = true;
            return $this->renderer->render($viewFile, $this->arguments);
        }
    }

    /**
     * Set the file extension for the view template (normally html)
     * @param \string $viewExtension file extension
     */
    public function setViewExtension($viewExtension)
    {
        $this->viewExtension = $viewExtension;
    }

    /**
     * Get this view's content type
     * @return \string content type
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set this view's content type
     * @param \string $contentType Content type
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Send content type header
     */
    public function sendContentTypeHeader()
    {
        Header('Content-Type: '.$this->contentType);
    }
}