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

namespace VMFDS\Cutter\Providers;

use VMFDS\Cutter\Factories\LicenseFactory;

class FlickrApiProvider extends AbstractProvider
{

    static protected $handledHosts = ['flickr.com', 'www.flickr.com'];
    public $hasCaptcha = 0;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Checks if this provider can handle urls from a specific host
     *
     * @param \string $host Host
     *
     * @return bool True, if provider can handle urls from this host
     */
    static public function canHandleHost($host): bool
    {
        return in_array($host, self::$handledHosts);
    }

    /**
     * Get the name for this provider
     * @return string Provider name
     */
    static public function getName()
    {
        return 'flickr';
    }

    /**
     * Retrieve an image from a specific url
     *
     * @param \string $imageUrl url
     */
    public function retrieveImage($imageUrl)
    {
        $session = \VMFDS\Cutter\Core\Session::getInstance();
        $id = $this->getId($imageUrl);
        $res = $this->api('flickr.photos.getSizes', ['photo_id' => $id]);

        // find largest size
        $imageRecord = null;
        foreach ($res->sizes->size as $size) {
            if (is_null($imageRecord) || ($size->width > $imageRecord->width)) $imageRecord = $size;
        }

        $src = $imageRecord->source;

        // get image info
        $imageInfo = $this->api('flickr.photos.getInfo', ['photo_id' => $id])->photo;

        // get license info
        $licenses = $this->api('flickr.photos.licenses.getInfo', [])->licenses->license;
        $license = $this->licenseFactory->getByUrl($licenses[$imageInfo->license]->url);

        // get official image url
        foreach ($imageInfo->urls->url as $url) {
            if ($url->type == 'photopage') $imageUrl = $url->_content;
        }

        $markers = [
            'id' => $id,
            'user' => $imageInfo->owner->username,
            'user_name' => $imageInfo->owner->realname,
        ];

        $meta = [
            'title' => $imageInfo->title->_content,
            'url' => $imageUrl,
            'description' => $imageInfo->description->_content,
            'author' => $imageInfo->owner->realname,
            'license' => $license,
            'id' => $id,
        ];

        // set meta data for IPTC tagging
        $session->setArgument('meta', $meta);
        $this->workFile = $this->replaceMarkers($this->configuration['fileNamePattern'], $markers);
        $this->legal = $this->replaceMarkers($this->configuration['legalPattern'], $markers, false);

        $this->workFile = preg_replace('/\W+/', '_', $this->workFile) . '.' . pathinfo($src, PATHINFO_EXTENSION);
        $this->writeFile($src, CUTTER_uploadPath . $this->workFile);
    }

    /**
     * Extract the image id from the url
     * @param string $imageUrl Url
     * @return string id
     */
    protected function getId($imageUrl)
    {
        return explode('/', parse_url($imageUrl, PHP_URL_PATH))[3];
    }

    protected function api($method, $params)
    {
        $params = array_merge([
            'format' => 'json',
            'nojsoncallback' => 1,
        ], $params);
        $apiKey = $this->configuration['api']['key'];
        $args = [];
        foreach ($params as $key => $val) {
            $args[] = $key . '=' . $val;
        }
        $url = 'https://api.flickr.com/services/rest/?api_key=' . $apiKey . '&method=' . $method . (count($args) ? '&' . join('&', $args) : '');
        return json_decode($this->getFile($url));
    }

    protected function getFile($src): string
    {
        return file_get_contents($src);
    }

    /**
     * @return string Answer
     */
    protected function login($imageUrl)
    {
    }

}
