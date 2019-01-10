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
class EventProcessor extends AbstractProcessor
{
    protected $icon          = 'calendar';
    protected $configuration = array();
    protected $kool          = null;

    public function __construct()
    {
        parent::__construct();
        $confManager         = \VMFDS\Cutter\Core\ConfigurationManager::getInstance();
        $this->configuration = $confManager->getConfigurationSet('event',
            'processors');
        $this->kool          = new \VMFDS\Cutter\Connectors\koolConnector();
    }

    public function getAdditionalFields()
    {
        return array(
            0 => array(
                'key' => 'event',
                'form' => $this->kOOLEventSelect(),
                'label' => 'Veranstaltung'),
        );
    }

    /**
     * Get a select field with all relevant kOOL events
     *
     * @param int eid Id of an event selected in previous steps
     * @return string Select field with all relevant kOOL events
     */
    private function kOOLEventSelect($eid = NULL)
    {
        $dbConf = $this->configuration['kOOL']['db'];

        // build sql
        $eConf = $this->configuration['kOOL']['event_select'];
        $where = array();
        $sql   = 'SELECT event.id, event.title,event.startdatum,event.kommentar FROM '.$dbConf['event_table'].' event LEFT JOIN '.$dbConf['group_table'].' grp ON (event.eventgruppen_id = grp.id) ';
        if ($this->getOption('allowed_calendars')) {
            $where[] = '(grp.calendar_id IN ('.join(',',
                    $this->getOption('allowed_calendars')).'))';
        }
        if ($eConf['range']['start'])
                $where[] = '(event.startdatum>=\''.date('Y-m-d',
                    strtotime($eConf['range']['start'])).'\')';
        if ($eConf['range']['end'])
                $where[] = '(event.startdatum<=\''.date('Y-m-d',
                    strtotime($eConf['range']['end'])).'\')';
        if (is_array($eConf['allowed_categories']) && (!$this->getOption('ignore_categories'))) {
            $catWhere   = array();
            foreach ($eConf['allowed_categories'] as $cat)
                $catWhere[] = '(FIND_IN_SET(\''.$cat.'\', event.'.$eConf['category_field'].'))';
            $where[]    = '('.join(' OR ', $catWhere).')';
        }
        if ($this->getOption('skip_already_set'))
                $where[] = '(event.'.$localConfig['event_field'].' IS NULL)';
        $sql .= ' WHERE ('.join(' AND ', $where).')';
        if ($eConf['order_by'])
                $sql .= ' ORDER BY event.'.$eConf['order_by'].' ASC';
        $sql.=';';

        $events = $this->kool->getAll($sql);

        // build select
        $select = '<select class="form-control" name="event" id="event"><option value="-1"> -- Keine Veranstaltung, Datei herunterladen -- </option>';
        foreach ($events as $row) {
            $rowTitle = utf8_encode($row['title'] ? $row['title'] : $row['kommentar']);
            $select .= '<option value="'.$row['id'].' '.(($row['id'] == $eid) ? ' selected'
                        : '').'">'.strftime('%d.%m.%Y',
                    strtotime($row['startdatum'])).' '.$rowTitle.'</option>';
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
        if ($this->checkRequiredArguments($options)) {
            if ($options['event'] != -1) {
                // move file:
                $destFile = pathinfo($fileName, PATHINFO_BASENAME);
                copy($fileName, $this->configuration['move_to'].$destFile);
                $this->kOOLAssignEvent(
                    $this->configuration['kOOL']['event_image_path'].$destFile,
                    $options['event'], $this->getOption('event_field'));
                return array('result' => self::RESULT_OK);
            } else {
                return array('result' => self::RESULT_FALLBACK);
            }
        }
    }

    /**
     * Assign an image to an event field in the kOOL database
     *
     * @param string image Image file name
     * @param int event Id of the event
     * @param string field Field to fill with the image info
     * @return void
     */
    private function kOOLAssignEvent($image, $event, $field)
    {
        // connect to db
        $dbConf = $this->configuration['kOOL']['db'];
        $sql    = 'UPDATE '.$dbConf['event_table'].' SET '
            .$field.'=\''.$this->getOption('event_image_path').$image
            .'\' WHERE id='.$event.';';
        $this->kool->query($sql);
    }

    /**
     * Get a list of required arguments
     * @return array List of required arguments
     */
    public function requiresArguments()
    {
        return array('event');
    }
}