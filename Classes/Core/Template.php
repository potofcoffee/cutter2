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
 * Description of AbstractTemplate
 *
 * @author chris
 */
class Template
{
    protected $category = '';
    protected $height = 0;
    protected $width = 0;
    protected $processor = '';
    protected $suffix = '';
    protected $title = '';
    protected $processorOptions = array();
    protected $set = 0;
    protected $config = [];

    public function getTemplateInfo()
    {
        $icon = \Peregrinus\Cutter\Factories\ProcessorFactory::get($this->processor)->getIcon();
        /*
        return array(
            'title' => $this->title,
            'w' => $this->width,
            'h' => $this->height,
            'key' => $this->getKey(),
            'processor' => $this->processor,
            'category' => $this->category,
            'icon' => $icon,
            'set' => $this->set,
        );
        */


        $config = $this->config;
        $config['w'] = $config['width'];
        unset($config['width']);
        $config['h'] = $config['height'];
        unset($config['height']);
        $config['key'] = $this->getKey();
        $config['icon'] = $icon;


        return $config;
    }

    /**
     * Get this templates's key (class without namespace and 'Provider')
     * @return \string
     */
    public function getKey()
    {
        $class = get_class($this);
        return str_replace('Template', '',
            str_replace('Peregrinus\\Cutter\\Templates\\', '', $class));
    }

    public function __construct($category, $set)
    {
        $config = ConfigurationManager::getInstance()->getConfigurationSet('Templates')[$category][$set];
        $config['category'] = $category;
        $config['set'] = $set;
        $this->config = $config;
        foreach (['category', 'height', 'width', 'processor', 'suffix', 'title', 'processorOptions', 'set'] as $field) {
            $this->$field = $config[$field];
        }
    }

    public function getProcessorObject()
    {
        return \Peregrinus\Cutter\Factories\ProcessorFactory::get($this->processor);
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getProcessor()
    {
        return $this->processor;
    }

    public function getSuffix()
    {
        return $this->suffix;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getProcessorOptions()
    {
        return $this->processorOptions;
    }
}