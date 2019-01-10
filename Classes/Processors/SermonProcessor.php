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
 * Description of EventProcessor
 *
 * @author chris
 */
class SermonProcessor extends AbstractProcessor
{
    protected $icon          = 'headphones';
    protected $configuration = array();
    protected $sermonDB      = null;

    public function __construct()
    {
        parent::__construct();
        $confManager         = \VMFDS\Cutter\Core\ConfigurationManager::getInstance();
        $this->configuration = $confManager->getConfigurationSet('sermon',
            'processors');
        $this->sermonDB      = new \VMFDS\Cutter\Connectors\SermonConnector();
    }

    public function getAdditionalFields()
    {
        return array(
            0 => array(
                'key' => 'sermon',
                'form' => $this->sermonSelect(),
                'label' => $this->getOption('label')),
        );
    }

    private function sermonSelect()
    {
        $dateField = $this->getOption('date_field');
        $imageField = $this->getOption('image_field');
        $imageField = $imageField ? $imageField : 'image';
        $where     = array();
        $sql       = 'SELECT uid, title'.($dateField ? ', '.$dateField : '').' FROM '
            .$this->getOption('sermon_table')
            .' WHERE '.$imageField.'=\'\' '.($dateField ? 'ORDER BY '.$dateField.' DESC' : '').';';
        $sermons   = $this->sermonDB->getAll($sql);

        $select = '<select class="form-control" name="sermon" id="sermon"><option value="-1">-- Keine '
            .$this->getOption('label').', Datei herunterladen --</option>';
        foreach ($sermons as $row) {
            if ($dateField) {
                $rowTitle = strftime('%d.%m.%Y', ($row[$dateField] + 0));
            } else {
                $rowTitle = '';
            }
            $rowTitle = utf8_encode($rowTitle.' '.$row['title']);
            $select .= '<option value="'.$row['uid'].'">'.$rowTitle.'</option>';
        }
        $select .= '</select>';

        return $select;
    }

    /**
     * Process an image file
     * @param \string $fileName Path to file
     * @param array $options Options
     * @return variant Return values
     */
    public function process($fileName, $options)
    {
        $imageField = $this->getOption('image_field');
        $imageField = $imageField ? $imageField : 'image';
        if ($this->checkRequiredArguments($options)) {
            if ($options['sermon'] != -1) {
                // move file:
                $destFile = pathinfo($fileName, PATHINFO_BASENAME);
                copy($fileName, $this->configuration['move_to'].$destFile);

                $sql = 'UPDATE '.$this->getOption('sermon_table')
                    .' SET '.$imageField.' =\''.$this->sermonDB->escape($destFile).'\' WHERE '
                    .' (uid='.$options['sermon'].');';
                $this->sermonDB->query($sql);
                $res = array('result' => self::RESULT_OK);
                if ($this->configuration['force_download']) {
                    // additionally force download?
                    $res['force_download'] = CUTTER_baseUrl.'Temp/Processed/'.$fileName;
                }
                return $res;
            } else {
                return array('result' => self::RESULT_FALLBACK);
            }
        }
    }

    /**
     * Get a list of required arguments
     * @return array List of required arguments
     */
    public function requiresArguments()
    {
        return array('sermon');
    }
}