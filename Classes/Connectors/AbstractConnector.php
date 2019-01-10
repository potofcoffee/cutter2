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

namespace Peregrinus\Cutter\Connectors;

use Peregrinus\Cutter\Core\Debugger;

/**
 * Description of AbstractConnector
 *
 * @author chris
 */
class AbstractConnector
{
    protected $configuration = array();
    protected $db = null;

    public function __construct($overrideConfiguration = [])
    {
        $confMan = \Peregrinus\Cutter\Core\ConfigurationManager::getInstance();
        $this->configuration = array_merge($overrideConfiguration, $confMan->getConfigurationSet(
            $this->getKey(), 'Connectors'));
        $this->db = new \mysqli(
            $this->configuration['host'], $this->configuration['user'],
            $this->configuration['password'], $this->configuration['name']
        );
        if ($this->configuration['setIgnoreSqlMode']) {
            $this->db->query('SET SESSION sql_mode = \'\'');
        }
    }

    /**
     * Get this connector's key (class without namespace and 'Provider')
     * @return \string
     */
    public function getKey()
    {
        $class = get_class($this);
        return str_replace('Connector', '',
            str_replace('Peregrinus\\Cutter\\Connectors\\', '', $class));
    }

    public function escape($s)
    {
        return $this->db->real_escape_string($s);
    }

    public function query($sql)
    {
        \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug('SQL: ' . $sql);
        $res = $this->db->query($sql);
        if (false === $res) {
            \Peregrinus\Cutter\Core\Logger::getLogger()->addDebug(
                'MySQLi Error: ' . $this->db->error);
        }
        return $res;
    }

    public function getAll($sql)
    {
        $res = $this->query($sql);
        $rows = array();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function getOne($sql)
    {
        $res = $this->query($sql);
        $rows = array();
        if (!$row = $res->fetch_assoc()) {
            $row = false;
        }
        return $row;
    }

    /**
     * Get id of last inserted record
     *
     * @return mixed Insert id or false
     */
    public function getInsertId()
    {
        return $this->db->insert_id;
    }

    /**
     * Quote a string
     * @param string $s string
     * @return string Quoted string
     */
    public function quote(string $s): string
    {
        return "'$s'";
    }

    public function insert(string $table, array $data)
    {
        $sql = 'INSERT INTO ' . $table . ' (' . join(',', array_keys($data)) . ') VALUES (' . join(',', $data) . ')';
        $res = $this->query($sql);
        if ($res) {
            return $this->getInsertId();
        }
    }
}