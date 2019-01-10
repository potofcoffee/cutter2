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
class SermonSlideProcessor extends AbstractProcessor
{

    protected $icon = 'headphones';
    protected $configuration = array();
    protected $sermonDB = null;

    public function __construct()
    {
        parent::__construct();
        $confManager = \VMFDS\Cutter\Core\ConfigurationManager::getInstance();
        $this->configuration = $confManager->getConfigurationSet('sermonSlide', 'processors');
        $this->sermonDB = new \VMFDS\Cutter\Connectors\Typo3Connector();
    }

    public function getAdditionalFields()
    {
        return [
            0 => [
                'key' => 'sermon',
                'form' => $this->sermonSelect(),
                'label' => $this->getOption('sermonLabel'),
            ],
            1 => [
                'key' => 'slide',
                'form' => $this->slideSelect(),
                'label' => $this->getOption('slideLabel'),
            ],
            2 => [
                'key' => 'newSlideTitle',
                'form' => '<div id="divNewSlideTitle" class="form-group"><input type="text" name="newSlideTitle" id="newSlideTitle" value="" class="form-control additionalArgument" /></div>',
                'label' => 'Titel für neue Folie'
            ],
            3 => [
                'key' => 'newSlideText',
                'form' => '<div id="divNewSlideText" class="form-group"><input type="text" name="newSlideText" id="newSlideText" value="" class="form-control additionalArgument" /></div>',
                'label' => 'Text für neue Folie'
            ],
            4 => [
                'key' => 'newSlideTextSize',
                'form' => '<div id="divNewSlideTextSize" class="form-group"><input type="text" name="newSlideTextSize" id="newSlideTextSize" value="'.$this->configuration['default_font_size'].'" class="form-control additionalArgument" /></div>',
                'label' => 'Schriftgröße für den Text'
            ],
            5 => [
                'key' => 'updateSlideSelectJS',
                'form' => '<script src="'.CUTTER_baseUrl.'Resources/Public/js/processors/SermonSlideProcessor.js"></script>',
                'label' => ''
            ]
        ];
    }

    private function sermonSelect()
    {
        $dateField = $this->getOption('date_field');
        $where = array();
        $sql = 'SELECT uid, title' . ($dateField ? ', ' . $dateField : '') . ' FROM '
                . $this->getOption('sermon_table') . ' WHERE NOT (deleted) '
                . ($dateField ? 'ORDER BY ' . $dateField . ' DESC' : '') . ';';
        $sermons = $this->sermonDB->getAll($sql);

        $select = '<select class="form-control additionalArgument" name="sermon" id="sermon" onchange="javascript:updateSlideSelect()">';
        foreach ($sermons as $row) {
            if ($dateField) {
                $rowTitle = strftime('%d.%m.%Y', $row[$dateField]);
            }
            $rowTitle = utf8_encode($rowTitle . ' ' . $row['title']);
            $select .= '<option value="' . $row['uid'] . '">' . $rowTitle . '</option>';
        }
        $select .= '</select>';

        return $select;
    }

    private function getSlides($sermon) {
        $sql = 'SELECT * FROM '
            . $this->getOption('slide_table')
            . ' WHERE (sermon_id = ' . $sermon . ') AND NOT (deleted) ORDER BY sermon_id, sorting;';
        return $this->sermonDB->getAll($sql);
    }

    private function slideSelect($sermon = NULL, $includeSelectTag = TRUE)
    {
        $slides = [];
        if ($sermon) {
            $slides = $this->getSlides($sermon);
        }
        $select = '';
        if ($includeSelectTag)
            $select .= '<select class="form-control additionalArgument" name="slide" id="slide"">';
        $select .= '<option value="-1" data-sermon="-1">--- Neue Folie ---</option>';
        foreach ($slides as $slide) {
            $select .= '<option value="' . $slide['uid'] . '" data-sermon="' . $slide['sermon_id'] . '">' . utf8_encode($slide['title']) . '</option>';
        }
        if ($includeSelectTag)
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
        $request = \VMFDS\Cutter\Core\Request::getInstance();
        if ($this->checkRequiredArguments($options)) {
            if ($options['sermon'] != -1) {
                // create slide?
                if ($options['slide'] == -1) {
                    $options['newSlideTitle'] = utf8_decode($request->getArgument('newSlideTitle'));
                    $options['newSlideText'] = utf8_decode($request->getArgument('newSlideText'));
                    $options['newSlideTextSize'] = utf8_decode($request->getArgument('newSlideTextSize'));
                    $slideId = $this->createSlide($options['sermon'], $options['newSlideTitle'], $request->getArgument('legal'), $options['newSlideText'], $options['newSlideTextSize']);
                } else {
                    $slideId = $options['slide'];
                    // delete existing reference(s)
                    $sql = "DELETE FROM sys_file_reference WHERE tablenames='" . $this->getOption('slide_table') . "' AND uid_foreign=$slideId;";
                    $this->sermonDB->query($sql);
                }
                // move file:
                $destFile = pathinfo($fileName, PATHINFO_BASENAME);
                copy($fileName, $this->configuration['move_to'] . $destFile);

                $fileId = $this->sermonDB->storeFile(
                        $this->configuration['relative_base'] . $destFile, $this->configuration['move_to'] . $destFile, $this->configuration['pid'], $this->configuration['storage']
                );

                $this->sermonDB->createFileReference($fileId, $this->configuration['pid'], $this->configuration['cruser_id'], $this->getOption('slide_table'), $this->getOption('imageField'), $slideId, '', $request->getArgument('legal'));

                $res = array('result' => self::RESULT_OK);
                return $res;
            } else {
                return array('result' => self::RESULT_FALLBACK);
            }
        }
    }

    protected function createSlide($sermon, $title, $legal, $text, $textSize)
    {
        $sql = 'SELECT MAX(sorting) idx FROM ' . $this->getOption('slide_table') . ' WHERE sermon_id=' . $sermon . ' AND NOT deleted;';
        $res = $this->sermonDB->getOne($sql);
        $idx = ($res['idx'] ? $res['idx'] + 1 : 0);

        $sql = 'INSERT INTO ' . $this->getOption('slide_table') . ' (sermon_id, sorting, title, presentation_title, presentation_font_size, '
                . 'image, image_source, pid, tstamp, crdate, cruser_id) VALUES ('
                . $sermon . ', '
                . $idx . ', '
                . "'" . $title."', "
                . "'".$text."', "
                . $textSize.", "
                . "1, "
                . "'" . $legal . "', "
                . $this->configuration['pid'] . ', '
                . time() . ', '
                . time() . ', '
                . $this->configuration['cruser_id']
                . ");";
        $this->sermonDB->query($sql);
        return $this->sermonDB->getInsertId();
    }

    /**
     * Get a list of required arguments
     * @return array List of required arguments
     */
    public function requiresArguments()
    {
        return array('sermon', 'slide');
    }

    public function ajaxGetSlides()
    {
        $request = \VMFDS\Cutter\Core\Request::getInstance();
        return $this->slideSelect($request->getArgument('sermon'), false);
    }

    public function getSlidesData() {
        $request = \VMFDS\Cutter\Core\Request::getInstance();
        return $this->slideSelect($request->getArgument('sermon'), false);
    }
}
