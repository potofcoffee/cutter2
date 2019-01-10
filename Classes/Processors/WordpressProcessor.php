<?php

namespace VMFDS\Cutter\Processors;

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

use VMFDS\Cutter\Connectors\WordpressConnector;
use VMFDS\Cutter\Core\Debugger;

class WordpressProcessor extends AbstractProcessor
{

    protected $icon = 'document';
    protected $configuration = array();
    protected $wp = null;

    public function __construct()
    {
        parent::__construct();
        $confManager = \VMFDS\Cutter\Core\ConfigurationManager::getInstance();
        $this->configuration = $confManager->getConfigurationSet('wordpress', 'processors');
    }


    /**
     * Process an image file
     * @param \string $fileName Path to file
     * @param array $options Options
     * @return variant Return values
     */
    public function process($fileName, $options)
    {
        $this->localConfig = yaml_parse($this->localConfig);
        $this->wp = new WordpressConnector(array_merge($this->localConfig, $this->configuration));
        if ($this->checkRequiredArguments($options)) {
            $request = \VMFDS\Cutter\Core\Request::getInstance();
            $destFile = pathinfo($fileName, PATHINFO_BASENAME);


            // 1. copy to appropriate path
            $path = $this->wp->getUploadPath();
            if (!file_exists($path)) mkdir ($path);
            $destFile = $path . $destFile;
            copy($fileName, $destFile);
            // Set correct file permissions.
            $stat = stat( dirname( $destFile ));
            $perms = $stat['mode'] & 0000666;
            @ chmod( $destFile, $perms );

            // enter into wordpress db
            $id = $this->wp->createAttachment($destFile);
            $data = $this->wp->generateAttachmentMetaData($id, $destFile);

            $res = [
                'configuration' => $this->configuration,
                'local' => $this->localConfig,
                'file' => $destFile,
                'meta' => $data,
                'result' => self::RESULT_OK
            ];
            return $res;
        }
    }


}
