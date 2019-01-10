<?php
/*
 * cutter2
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

namespace Peregrinus\Cutter\Controllers;


use Peregrinus\Cutter\Core\ConfigurationManager;
use Peregrinus\Cutter\Core\Debugger;
use Peregrinus\Cutter\Core\Router;
use Peregrinus\Cutter\Factories\AbstractFactory;
use Peregrinus\Cutter\Factories\ProcessorFactory;
use Peregrinus\Cutter\Factories\ProviderFactory;
use Peregrinus\Cutter\Utility\FontsUtility;

class SettingsController extends AbstractController
{

    protected $configurationManager = null;


    public function __construct()
    {
        parent::__construct();
        $this->setDefaultAction('templates');
        $this->configurationManager = ConfigurationManager::getInstance();
    }

    public function templatesAction()
    {
        if ($this->request->hasArgument('config')) {
            $config = $this->request->getArgument('config', false);
            yaml_emit_file(CUTTER_basePath . 'Configuration/Templates.yaml', $config);
        } else {
            $config = ConfigurationManager::getInstance()->getConfigurationSet('templates');
            ksort($config);
        }

        $this->view->assign('config', $config);
    }


    public function categoryFormAction()
    {
        $this->request->applyUriPattern(['category']);
        if ($this->request->hasArgument('category')) {
            $category = $this->request->getArgument('category');
            $sets = ConfigurationManager::getInstance()->getConfigurationSet('templates')[$category];
            $idx = $this->request->getArgument('idx');
            $this->view->assign('category', $category);
            $this->view->assign('sets', $sets);
            $this->view->assign('idx', $idx);
        }
    }

    public function setFormAction()
    {
        $this->request->applyUriPattern(['category', 'set', 'catIdx', 'setIdx']);
        if ($this->request->hasArgument('category') && $this->request->hasArgument('set')) {
            $category = $this->request->getArgument('category');
            $set = $this->request->getArgument('set');
            $catIdx = $this->request->getArgument('catIdx');
            $setIdx = $this->request->getArgument('setIdx');
            $config = ConfigurationManager::getInstance()->getConfigurationSet('templates')[$category][$set];
            $idx = $this->request->getArgument('idx');
            $processors = ProcessorFactory::getIcons();

            $this->view->assign('category', $category);
            $this->view->assign('set', $config);
            $this->view->assign('catIdx', $catIdx);
            $this->view->assign('setIdx', $setIdx);
            $this->view->assign('processors', $processors);
            $this->view->assign('fonts', FontsUtility::getAllFontAssets());
        }
    }


    protected function folderBasedYamlSettings($folder)
    {
        // save submitted form
        if ($this->request->hasArgument('config')) {
            $config = $this->configurationManager->fromFieldsets($this->request->getArgument('config', false));
            $config = $this->configurationManager->ensureAllYamlFilesPresent($folder, $config);
            //Debugger::dumpAndDie($config);
            $this->configurationManager->distributeToYamlFolder($folder, $config);
        }

        // read config
        $fieldsets = $this->configurationManager->toFieldsets($this->configurationManager->collectFromYamlFolder($folder));
        Debugger::dumpAndDie($fieldsets);

        $this->view->assign('config', $fieldsets);
    }

    public function providersAction()
    {
        sort($providers = array_keys(ProviderFactory::getAllClasses('provider')));
        $this->configurationManager->createMissingConfigurationFiles('Providers', $providers);
        $this->folderBasedYamlSettings('Providers');
    }

    public function connectorsAction()
    {
        sort($connectors = array_keys(AbstractFactory::getAllClasses('connector')));
        $this->configurationManager->createMissingConfigurationFiles('Connectors', $connectors);
        $this->folderBasedYamlSettings('Connectors');
    }


}