<?php

namespace Peregrinus\Cutter\Controllers;

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

use Peregrinus\Cutter\Core\Debugger;
use Peregrinus\Cutter\Core\Request;
use Peregrinus\Cutter\Core\Router;
use Peregrinus\Cutter\Core\Session;
use Peregrinus\Cutter\Utility\FontsUtility;

class UiController extends AbstractController
{

    function __construct()
    {
        parent::__construct();
        $this->setDefaultAction('index');
    }

    /**
     * Central UI action
     * @action index
     * @return void
     */
    function indexAction()
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('indexAction called');
        $session = \Peregrinus\Cutter\Core\Session::getInstance();

        // redirect to upload, if we don't have a file yet
        if (!$session->hasArgument('workFile')) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('No workFile in session, redirecting to upload');
            \Peregrinus\Cutter\Core\Router::getInstance()->redirect(
                'acquisition', 'form');
        }

        $this->view->assign('image',
            CUTTER_baseUrl.'Temp/Uploads/'.$session->getArgument('workFile'));

        $imgInfo = getimagesize(CUTTER_basePath.'Temp/Uploads/'.$session->getArgument('workFile'));
        $this->view->assign('width', $imgInfo[0]);
        $this->view->assign('height', $imgInfo[1]);

        $this->view->assign('legal', $session->getArgument('legal'));
        $this->view->assign('meta', $session->getArgument('meta'));

        $info = \Peregrinus\Cutter\Factories\TemplateFactory::getTemplateInfo();
        ksort($info);
        $this->view->assign('templates', $info);

        $this->view->assign('fonts', FontsUtility::getAllFontAssets());

        $templateKeys  = array_keys($info);
        $firstTemplate = $info[$templateKeys[0]][0];
        $this->view->assign('firstTemplate', $firstTemplate);
    }

    function debugAction()
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('debugAction called');
        die('<pre>'.print_r($_REQUEST, 1));
    }

    /**
     * Download script
     */
    public function downloadAction()
    {
        $this->dontShowView();
        $request = \Peregrinus\Cutter\Core\Request::getInstance();
        if ($request->hasArgument('url')) {
            $url = $request->getArgument('url');
            $raw = CUTTER_basePath.'Temp/Processed/'.basename(parse_url($url,
                        PHP_URL_PATH));
            Header('Content-Description: File Transfer');
            Header('Content-Disposition: attachment; filename='.sprintf('"%s"',
                    addcslashes(basename($raw), '"')));
            header('Content-Transfer-Encoding: binary');
            header('Content-Type: application/octet-stream');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: '.filesize($raw));
            readfile($raw);
            die();
        }
    }

    /**
     * Set changes to meta record
     */
    public function setMetaAction() {
        $request = Request::getInstance();

        $session = Session::getInstance();
        $session->setArgument('meta', array_replace_recursive($session->getArgument('meta'), $request->getArgument('meta')));
        $router = Router::getInstance();
        $router->redirect('ui', 'index');

    }
}