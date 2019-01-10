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

namespace VMFDS\Cutter\Connectors;

/**
 * Description of SermonConnector
 *
 * @author chris
 */
class Typo3Connector extends AbstractConnector
{

    public function createFileReference($fileId, $pid, $user, $objectTable, $objectField, $objectId, $title, $legal)
    {
        $sql = 'INSERT INTO sys_file_reference (pid, tstamp, crdate, cruser_id, sorting, '
                . 'uid_local, uid_foreign, tablenames, fieldname, sorting_foreign, table_local, '
                . 'title, description) VALUES ('
                . $pid . ', '
                . time() . ', '
                . time() . ', '
                . $user . ', 256, '
                . $fileId . ', '
                . $objectId . ', '
                . "'$objectTable', "
                . "'$objectField', 1, 'sys_file', "
                . "'$title', '$legal');";
        $this->db->query($sql);
        return $this->getInsertId();
    }

    public function storeFile($file, $absolutePath, $pid, $storage)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $sql = 'INSERT INTO sys_file (pid, tstamp, last_indexed, storage, type, identifier, '
                . 'identifier_hash, folder_hash, extension, mime_type, name, sha1, size, '
                . 'creation_date, modification_date) VALUES ('
                . '0, '
                . time() . ', '
                . time() . ', '
                . $storage . ', 2, '
                . "'" . $file . "', "
                . "'" . sha1(basename($file)) . "', "
                . "'" . sha1(pathinfo($file, PATHINFO_DIRNAME)) . "', "
                . "'" . pathinfo($file, PATHINFO_EXTENSION) . "', "
                . "'" . finfo_file($finfo, $absolutePath) . "', "
                . "'" . pathinfo($file, PATHINFO_BASENAME) . "', "
                . "'" . sha1_file($absolutePath) . "', "
                . filesize($absolutePath) . ', '
                . filectime($absolutePath) . ', '
                . filemtime($absolutePath)
                . ')';
        finfo_close($finfo);
        $res = $this->query($sql);
        return $this->getInsertId();
    }

}
