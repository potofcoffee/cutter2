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

class Typo3Processor extends AbstractProcessor
{

    protected $icon = 'document';
    protected $configuration = array();
    protected $db = null;

    public function __construct()
    {
        parent::__construct();
        $confManager = \VMFDS\Cutter\Core\ConfigurationManager::getInstance();
        $this->configuration = $confManager->getConfigurationSet('typo3', 'processors');
        $this->db = new \VMFDS\Cutter\Connectors\Typo3Connector();
    }

    public function getAdditionalFields()
    {
        $x = array(
            0 => array(
                'key' => 'page',
                'form' => $this->pageSelect(),
                'label' => 'Seite'),
        );
        return $x;
    }

    protected function getChildren($pid)
    {
        $sql = 'SELECT uid,title,nav_title FROM pages WHERE pid=' . $pid . ' AND NOT (deleted) ORDER BY sorting;';
        $res = $this->db->query($sql);
        $pages = [];
        while ($row = $res->fetch_assoc()) {
            $pages[$row['uid']]['page'] = $row;
        }
        foreach ($pages as $uid => $page) {
            $pages[$uid]['children'] = $this->getChildren($uid);
        }
        return $pages;
    }

    protected function renderTree($pages, $o = [], $level = 0)
    {
        foreach ($pages as $uid => $page) {
            $t = '';
            for ($i = 1; $i <= $level; $i++)
                $t .= '--';
            $t .= ($page['page']['nav_title'] ? $page['page']['nav_title'] : $page['page']['title']) . ' (' . $uid . ')';
            $o[] = '<option value="' . $uid . '" data-title="' . htmlspecialchars($page['page']['title']) . '">' . htmlspecialchars($t) . '</option>';
            if (count($page['children'])) {
                $o = $this->renderTree($page['children'], $o, $level + 1);
            }
        }
        return $o;
    }

    protected function pageSelect()
    {
        $pages = $this->getChildren(0);
        $x = $this->renderTree($pages);
        return '<select class="form-control" id="page">' . join('', $this->renderTree($pages)) . '</select>';
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
            $request = \VMFDS\Cutter\Core\Request::getInstance();
            $destFile = pathinfo($fileName, PATHINFO_BASENAME);
            copy($fileName, $this->configuration['move_to'] . $destFile);
            $sql = 'UPDATE pages'
                    . ' SET media=1 WHERE '
                    . ' (pid=' . $options['page'] . ');';
            $this->db->query($sql);
            $fileId = $this->db->storeFile(
                    $this->configuration['relative_base'] . $destFile, $this->configuration['move_to'] . $destFile, $options['page'], $this->configuration['storage']
            );
            $this->db->createFileReference($fileId, $options['page'], $this->configuration['cruser_id'], 'pages', 'media', $options['page'], '', $request->getArgument('legal'));
            $res = array('result' => self::RESULT_OK);
            return $res;
        }
    }

    /**
     * Get a list of required arguments
     * @return array List of required arguments
     */
    public function requiresArguments()
    {
        return array('page');
    }

}
