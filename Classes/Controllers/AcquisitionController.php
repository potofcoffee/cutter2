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

class AcquisitionController extends AbstractController
{

    function __construct()
    {
        parent::__construct();
        $this->setDefaultAction('form');
    }

    /**
     * Display the upload form
     * @action form
     * @return void
     */
    function formAction()
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('formAction called');

        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('Clearing session content');
        \Peregrinus\Cutter\Core\Session::getInstance()->clear();

        // get list of possible providers
        $providers = \Peregrinus\Cutter\Factories\ProviderFactory::getProviderNames();
        $this->view->assign('providers', $providers);

        // get history
        $history = array_reverse(yaml_parse_file(CUTTER_basePath.'Temp/History/History.yaml'));

        // remove double entries
        $historyCheck = [];
        foreach ($history as $index => $item) {
            if (isset($historyCheck[$item['url']])) {
                unset($history[$index]);
            } else {
                $historyCheck[$item['url']] = true;
            }
        }

        $this->view->assign('history', $history);
    }

    /**
     * Import action
     * @action import
     * @return void
     */
    function importAction()
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('acquisition/import action called');
        $request = \Peregrinus\Cutter\Core\Request::getInstance();
        if (!$request->hasArgument('url')) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('No url specified, redirecting to upload');
            $this->redirectToAction('form');
        }
        $url      = $request->getArgument('url');
        $session  = \Peregrinus\Cutter\Core\Session::getInstance()->setArgument('original_url',
            $url);
        \Peregrinus\Cutter\Core\Logger::getLogger()->addNotice('Starting cloud import from url '.$url);
        $provider = \Peregrinus\Cutter\Factories\ProviderFactory::getHostHandler($url);
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('Using provider class '.get_class($provider));

        // do we have to process a captcha?
        if ($provider->hasCaptcha) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('Provider requires a captcha');
            if (!$request->hasArgument('captchaHash')) {
                \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('No captcha set yet, redirecting');
                \Peregrinus\Cutter\Core\Router::getInstance()->redirect(
                    strtolower($this->getName()), 'captcha',
                    array('url' => $url));
            } else {
                $provider->data['captcha'] = array(
                    'hash' => $request->getArgument('captchaHash'),
                    'text' => $request->getArgument('captchaText'),
                );
            }
        }


        // render the view prematurely (waiting ...)
        $this->renderView();

        $provider->retrieveImage($url);
        //if (CUTTER_debug) print_r($provider);

        // save image in history
        $destinationFile = CUTTER_basePath.'Temp/History/'.
            pathinfo($provider->workFile, PATHINFO_FILENAME)
            .'_history.jpg';
        $imageFile = CUTTER_uploadPath.$provider->workFile;
        $converter = \Peregrinus\Cutter\Factories\ConverterFactory::getFileHandler($imageFile);
        $image = new \Peregrinus\Cutter\Core\Image($converter->getImage($imageFile));
        $h = $image->getHeight()*(300/$image->getWidth());
        $image->resize(0, 0, 300, $h, $image->getWidth(), $image->getHeight());
        $image->toJpeg($destinationFile, 100);

        $fp = fopen(CUTTER_basePath.'Temp/History/History.yaml', 'a');
        fwrite ($fp, '- url: '.$url."\n");
        fwrite ($fp, '  preview: '.pathinfo($provider->workFile, PATHINFO_FILENAME).'_history.jpg'."\n");
        fwrite ($fp, '  time: '.time()."\n");
        fclose ($fp);


        // save data in session and redirect to index
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('Import done, saving to session.');
        $session = \Peregrinus\Cutter\Core\Session::getInstance();
        $session->setArgument('workFile', $provider->workFile);
        $session->setArgument('legal', $provider->legal);

        \Peregrinus\Cutter\Core\Logger::getLogger()->addNotice('Cloud import processed with Provider "'.$provider->getName().'".');
        \Peregrinus\Cutter\Core\Logger::getLogger()->addNotice('File received: '.CUTTER_uploadPath.$workFile);
        \Peregrinus\Cutter\Core\Logger::getLogger()->addNotice('Legal text preset: '.$legal);

        \Peregrinus\Cutter\Core\Router::getInstance()->redirect(
            'ui', 'index', null, null,
            \Peregrinus\Cutter\Core\Router::REDIRECT_JAVASCRIPT, 3000);
    }

    /**
     * Captcha action
     * Gets called to verify captcha information
     * @action captcha
     */
    function captchaAction()
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('acquisition/import action called');
        $request = \Peregrinus\Cutter\Core\Request::getInstance();
        if (!$request->hasArgument('url')) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('No url specified, redirecting to upload');
            $this->redirectToAction('form');
        }
        $url      = $request->getArgument('url');
        $provider = \Peregrinus\Cutter\Factories\ProviderFactory::getHostHandler($url);
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('Using provider class '.get_class($provider));

        $hash  = $provider->getCaptchaHash();
        $image = $provider->getCaptchaImage($hash);

        $this->view->assign('url', $url);
        $this->view->assign('hash', $hash);
        $this->view->assign('captcha', $image);
    }

    /**
     * Receive action
     * Gets called to process an uploaded file
     * @action receive
     */
    function receiveAction()
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('receiveAction called.');
        $request = \Peregrinus\Cutter\Core\Request::getInstance();
        if (!$request->hasFilesArray()) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('No files array.');
            $this->redirectToAction('upload');
        }
        $filesArray = $request->getFilesArray();
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('Files: '.print_r($filesArray, 1));
        $fileName   = $filesArray['file']['name'];
        $legal      = '';
        if ($request->hasArgument('legal')) {
            $legal = $request->getArgument('legal');
            $fileName .= '_'.str_replace(' / ', '_', $legal);
        }
        $fileName = strtr($fileName,
            array(' ' => '_', 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
                'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss'));
        $dest     = CUTTER_uploadPath.$fileName;
        move_uploaded_file($filesArray['file']['tmp_name'], $dest);

        // save info in session
        $session = \Peregrinus\Cutter\Core\Session::getInstance();
        $session->setArgument('workFile', $fileName);
        $session->setArgument('legal', $legal);

        // this is an Ajax'y action with no return, so don't show a view
        $this->dontShowView();
    }

    function uploadedAction() {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('uploadedAction called.');
        Debugger::dumpAndDie([$_REQUEST, $_FILES, $_SESSION]);
    }
}