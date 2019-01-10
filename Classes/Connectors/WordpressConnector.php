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

use Peregrinus\Cutter\Converters\AbstractConverter;
use Peregrinus\Cutter\Core\Debugger;
use Peregrinus\Cutter\Core\Image;
use Peregrinus\Cutter\Factories\ConverterFactory;
use Peregrinus\Cutter\Utility\FileUtility;
use Peregrinus\Cutter\Utility\ImageMetaUtility;

class WordpressConnector extends AbstractConnector
{

    public function __construct($overrideConfiguration = [])
    {
        parent::__construct($overrideConfiguration);
        $this->configuration['prefix'] = $this->configuration['prefix'] ?: 'wp_';
    }

    public function getUploadPath()
    {
        $uploadPath = $this->getOption('upload_path');
        if ((!$uploadPath) || 'wp-content/uploads' == $uploadPath) {
            $uploadPath = $this->configuration['path'] . 'wp-content/uploads';
        } elseif (0 !== strpos($uploadPath, $this->configuration['path'])) {
            // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
            $uploadPath = $this->pathJoin(ABSPATH, $uploadPath);
        }
        if ('/' != substr($uploadPath, -1)) $uploadPath .= '/';
        if (isset($this->configuration['blog'])) $uploadPath .= 'sites/'.$this->configuration['blog'].'/';
        $uploadPath .= date('Y/m/');
        return $uploadPath;
    }

    protected function getOption($key)
    {
        $optionsTable = $this->getTableName('options');
        $sql = 'SELECT option_value FROM ' . $optionsTable . ' WHERE option_name=\'' . $key . '\';';
        return $this->getOne($sql)['option_value'];
    }

    protected function getTableName($table)
    {
        return $this->configuration['prefix'] . $this->configuration['blog'] . '_' . $table;
    }

    protected function pathJoin($base, $path)
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    protected function isAbsolutePath($path)
    {
        /*
         * This is definitive if true but fails if $path does not exist or contains
         * a symbolic link.
         */
        if (realpath($path) == $path) {
            return true;
        }

        if (strlen($path) == 0 || $path[0] == '.') {
            return false;
        }

        // Windows allows absolute paths like this.
        if (preg_match('#^[a-zA-Z]:\\\\#', $path)) {
            return true;
        }

        // A path starting with / or \ is absolute; anything else is relative.
        return ($path[0] == '/' || $path[0] == '\\');
    }

    public function createAttachment($file)
    {
        $table = $this->getTableName('posts');
        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $now = $this->quote(date('Y-m-d H:i:s'));
        $url = str_replace($this->getUploadPath(), '', $file);
        return $this->insert($table, [
            'post_author' => $this->configuration['author'] ?: 0,
            'post_date' => $now,
            'post_date_gmt' => $now,
            'post_content' => $this->quote($file),
            'post_title' => $this->quote($baseName),
            'post_name' => $this->quote($baseName),
            'post_status' => $this->quote('inherit'),
            'comment_status' => $this->quote('open'),
            'ping_status' => $this->quote('closed'),
            'post_modified' => $now,
            'post_modified_gmt' => $now,
            'guid' => $this->quote($url),
            'post_type' => $this->quote('attachment'),
            'post_mime_type' => $this->quote(FileUtility::getMimeType($file))
        ]);
    }

    protected function getPost($id)
    {
        return $this->getOne('SELECT * FROM ' . $this->getTableName('posts') . ' WHERE ID=' . $id);
    }

    public function generateAttachmentMetaData($attachmentId, $file)
    {
        $attachment = $this->getPost($attachmentId);

        $metadata = [];
        $support = false;
        $mimeType = $attachment['post_mime-type'];

        $imagesize = getimagesize($file);
        $metadata['width'] = $imagesize[0];
        $metadata['height'] = $imagesize[1];

        // Make the file path relative to the upload dir.
        $metadata['file'] = str_replace($this->getUploadPath(), '', $file);

        // Make thumbnails and other intermediate sizes.
        // Default sizes are defined via options:
        $sql = 'SELECT option_name, option_value FROM ' . $this->getTableName('options') . ' WHERE option_name LIKE \'%_size_w\'';
        $sizes = [];
        foreach ($this->getAll($sql) as $record) {
            $sizeName = str_replace('_size_w', '', $record['option_name']);
            $sizes[$sizeName]['width'] = $record['option_value'];
            $sizes[$sizeName]['height'] = $this->getOption($sizeName . '_size_h');
            $sizes[$sizeName]['crop'] = $this->getOption($sizeName . '_crop');
        }

        // create only thumbnail
        /** @var AbstractConverter $converter */
        $converter = ConverterFactory::getFileHandler($file);
        $image = new Image($converter->getImage($file));
        $shorterSide = min($metadata['width'], $metadata['height']);
        $image->resize(0, 0, $shorterSide, $shorterSide, $sizes['thumbnail']['width'], $sizes['thumbnail']['height']);
        $image->toJpeg(pathinfo($file, PATHINFO_DIRNAME) . '/'
            . pathinfo($file, PATHINFO_FILENAME) . '-'
            . $sizes['thumbnail']['width'] . 'x' . $sizes['thumbnail']['height'] . '.jpg',
            100);

        // Fetch additional metadata from EXIF/IPTC.
        $imageMeta = ImageMetaUtility::getImageMetaData($file);
        if ($imageMeta) {
            $metadata['image_meta'] = $imageMeta;
        }

        $this->insert($this->getTableName('postmeta'), [
            'post_id' => $attachmentId,
            'meta_key' => $this->quote('_wp_attached_file'),
            'meta_value' => $this->quote($file)
        ]);
        $this->insert($this->getTableName('postmeta'), [
            'post_id' => $attachmentId,
            'meta_key' => $this->quote('_wp_attachment_metadata'),
            'meta_value' => $this->quote(serialize($metadata)),
        ]);

        return $metadata;
    }

}
