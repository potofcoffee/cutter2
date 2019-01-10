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
 * Description of Router
 *
 * @author chris
 */
class Router
{
    const REDIRECT_HEADER     = 0x01;
    const REDIRECT_JAVASCRIPT = 0x02;

    static $instance             = NULL;
    protected $defaultController = '';

    /**
     * Get an instance of the request object
     * @return \Peregrinus\Cutter\Core\Router Instance of session object
     */
    static public function getInstance(): Router
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {

    }

    final private function __clone()
    {

    }

    /**
     * Get the default controller
     * @return \string Default controller name
     */
    public function getDefaultController()
    {
        if ($this->defaultController != '') {
            return $this->defaultController;
        } else {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addEmergency(
                'No default controller specified. Use Router->setDefaultController();');
            throw new Exception('No default controller specified.');
        }
    }

    /**
     * Set the default controller
     * @param \string $defaultController Default controller name
     */
    public function setDefaultController($defaultController)
    {
        $this->defaultController = $defaultController;
    }

    /**
     * Dispatch routing
     * @return void
     */
    public function dispatch()
    {
        $request = \Peregrinus\Cutter\Core\Request::getInstance();
        $request->parseUri();
        $request->applyUriPattern(array('controller', 'action'));

        if ($request->hasArgument('controller')) {
            $controllerName = $request->getArgument('controller');
        } else {
            // redirect to default controller
            $this->redirect($this->getDefaultController(), '');
        }
        $controllerClass = $this->getControllerClass($controllerName);
        $controller      = new $controllerClass();
        $controller->dispatch();
    }

    /**
     * Get controller class name for a specific controller
     * @param \string $controllerName Controller name
     * @return \string Controller class name
     */
    protected function getControllerClass($controllerName)
    {
        return '\\Peregrinus\\Cutter\\Controllers\\'.ucfirst($controllerName).'Controller';
        if (!class_exists($controllerClass)) {
            if ($controllerName == $this->getDefaultController()) {
                \Peregrinus\Cutter\Core\Logger::getLogger()->addEmergency(
                    'Default controller class '.$controllerClass.' does not exist!'
                );
                throw new Exception('Default controller class '.$controllerClass.' does not exist!');
            } else {
                $controllerClass = getControllerClass($this->getDefaultController());
            }
        }
    }

    /**
     * Redirect to Url
     * @param \string $targetUrl Url
     * @param \int $redirectMethod Method of redirecting
     * @param \int $delay Delay in ms (only with javascript redirect)
     */
    public function redirectToUrl($targetUrl,
                                  $redirectMethod = self::REDIRECT_HEADER,
                                  $delay = 0)
    {
        switch ($redirectMethod) {
            case self::REDIRECT_HEADER:
                Header('Location: '.$targetUrl);
                break;
            case self::REDIRECT_JAVASCRIPT:
                echo '<script type="text/javascript"> setTimeout(function(){ window.location.href=\''.$targetUrl.'\' }, '.$delay.');</script>';
                break;
        }
        die();
    }

    /**
     * Redirect to a controller/action pair
     * @param \string $controller Controller
     * @param \string $action Action
     * @param array $arguments Arguments
     * @param array $pattern Uri pattern
     * @param \int $redirectMethod Method of redirecting
     * @param \int $delay Delay in ms (only with javascript redirect)
     */
    public function redirect($controller, $action, $arguments = array(),
                             $pattern = array(),
                             $redirectMethod = self::REDIRECT_HEADER, $delay = 0)
    {
        if (!count($pattern)) {
            $pattern = array('controller', 'action');
        }
        $arguments['controller'] = $controller;
        $arguments['action']     = $action;
        $uri                     = \Peregrinus\Cutter\Core\Router::getInstance()->getUri($arguments,
            $pattern);


        $this->redirectToUrl($uri, $redirectMethod, $delay);
    }

    protected function getUri($arguments, $pattern)
    {
        $uriItems = array();
        foreach ($pattern as $key) {
            $uriItems[] = $arguments[$key];
            unset($arguments[$key]);
        }
        $uri = join('/', $uriItems);
        if (is_array($arguments)) {
            $uriItems = array();
            foreach ($arguments as $key => $value) {
                $uriItems[] = $key.'='.$value;
            }
            if (count($uriItems)) {
                $uri .= '?'.join('&', $uriItems);
            }
            $uri = CUTTER_baseUrl.$uri;
            return $uri;
        }
    }
}