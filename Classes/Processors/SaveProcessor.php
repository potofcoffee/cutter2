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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Peregrinus\Cutter\Processors;

/**
 * Description of DownloadProcessor
 *
 * @author chris
 */
class SaveProcessor extends AbstractProcessor
{

    protected $icon = 'save';

    /**
     * Process an image file
     *
     * @param \string $fileName
     *            Path to file
     * @param array $options
     *            Options
     * @return variant Return values
     */
    public function process($fileName, $options)
    {
        if (! $this->localConfig['forceFileName']) {
            $destFile = pathinfo($fileName, PATHINFO_BASENAME);
        } else {
            $destFile = $this->localConfig['forceFileName'];
        }
        
        if ($this->localConfig['move_to']) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('Saving file as ' . $this->localConfig['move_to'] . $destFile);
            copy($fileName, $this->localConfig['move_to'] . $destFile);
            return array(
                'result' => self::RESULT_OK
            );
        } else {
            return array(
                'result' => self::RESULT_FALLBACK
            );
        }
    }
}