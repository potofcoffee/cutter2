<?php
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

namespace VMFDS\Cutter\Processors;

/**
 * Description of DownloadProcessor
 *
 * @author chris
 */
class DownloadProcessor extends AbstractProcessor
{
    protected $icon = 'save';

    /**
     * Process an image file
     * @param \string $fileName Path to file
     * @param array $options Options
     * @return variant Return values
     */
    public function process($fileName, $options)
    {
        return array(
            'result' => self::RESULT_OK,
            'forceDownload' => str_replace(CUTTER_basePath, CUTTER_baseUrl,
                $fileName),
        );
    }
}