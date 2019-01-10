<?php

namespace Peregrinus\Cutter\Controllers;

/*
 * CUTTER
 * Multi-Purpose Church Administration Suite
 * http://github.com/VolksmissionFreudenstadt/communio
 *
 * Copyright (c) 2016+ Volksmission Freudenstadt, http://www.volksmission-freudenstadt.de
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

class AbstractController
{
    const REDIRECT_HEADER = 0x01;
    const REDIRECT_JAVASCRIPT = 0x02;

    private $conf = array();
    private $configurationManager = null;
    protected $defaultAction = '';
    protected $viewPath = 'Views/';
    protected $viewLoader = null;
    protected $view = null;
    protected $showView = true;
    protected $request = null;

    protected $insecureActions = [];

    public function __construct()
    {
        $confManager = $this->getConfigurationManager();
        $this->conf = $confManager->getConfigurationSet('cutter');
        $this->request = \Peregrinus\Cutter\Core\Request::getInstance();
    }

    protected function initializeController()
    {

    }

    /**
     * Process action routing
     *
     * @return void
     * @throws \Exception
     */
    public function dispatch()
    {

        if (!$this->request->hasArgument('action')) {
            // redirect to default action
            $defaultAction = $this->defaultAction ? $this->defaultAction : 'default';
            $this->redirectToAction($defaultAction);
        }
        $requestedAction = $this->request->getArgument('action');
        $actionMethod = $requestedAction . 'Action';
        if (!method_exists($this, $actionMethod)) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addEmergency(
                'Method "' . $actionMethod . '" not implemented in controller' . get_class($this) . ' .');
            throw new \Exception('Method "' . $requestedAction . '" not implemented in this controller.',
                0x01);
        } else {
            // get the view
            $this->view = new \Peregrinus\Cutter\Core\View($requestedAction);
            $this->view->setViewPath(CUTTER_viewPath . $this->getName() . '/');
            $this->view->assign('basePath', CUTTER_baseUrl);
            // run the initialize and action methods
            $this->initializeController();
            $this->$actionMethod();
            // render the view
            if ($this->showView) {
                $this->view->sendContentTypeHeader();
                $this->renderView();
            }
        }
    }

    /**
     * Get an instance of the configuration manager
     * @return \Peregrinus\Cutter\Core\ConfigurationManager Configuration manager object
     */
    protected function getConfigurationManager()
    {
        if (is_null($this->configurationManager)) {
            $this->configurationManager = \Peregrinus\Cutter\Core\ConfigurationManager::getInstance();
        }
        return $this->configurationManager;
    }

    /**
     * Get this controllers's name (class without namespace and 'Provider')
     * @return \string
     */
    public function getName()
    {
        $class = get_class($this);
        return str_replace('Controller', '',
            str_replace('Peregrinus\\Cutter\\Controllers\\', '', $class));
    }

    /**
     * Redirect to another action
     * @param \string $action
     * @param \int $redirectMethod Method of redirecting
     * @param \int $delay Delay in ms (only with javascript redirect)
     */
    protected function redirectToAction(
        $action,
        $redirectMethod = self::REDIRECT_HEADER,
        $delay = 0
    ) {
        \Peregrinus\Cutter\Core\Router::getInstance()->redirect(
            strtolower($this->getName()), $action, null, null, $redirectMethod,
            $delay);
    }

    /**
     * Get default action name for this controller
     * @return \string Default action name
     */
    function getDefaultAction()
    {
        return $this->defaultAction;
    }

    /**
     * Set default action name for this controller
     * @param \string $defaultAction Default action name
     * @return void
     */
    function setDefaultAction($defaultAction)
    {
        $this->defaultAction = $defaultAction;
    }

    /**
     * Switch off view handling
     * @return void
     */
    public function dontShowView()
    {
        $this->showView = false;
    }

    /**
     * Render the view now
     * @param bool $show Output the view right away
     */
    public function renderView($show = true)
    {
        $rendered = $this->view->render();
        if ($show) {
            echo $rendered;
        }
        // prevent showing twice:
        $this->dontShowView();
        return $rendered;
    }
}