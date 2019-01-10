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

class WikimediaCommonsProvider extends AbstractProvider
{

    static protected $handledHosts = ['commons.wikimedia.org'];
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
        return 'wikimediaCommons';
    }

    /**
     * Retrieve an image from a specific url
     *
     * @param \string $imageUrl url
     */
    public function retrieveImage($imageUrl)
    {
        $session = \VMFDS\Cutter\Core\Session::getInstance();

        $this->login($imageUrl);
        $pDoc = \PhpQuery::newDocumentHTML($this->getFile($imageUrl));


        $rawUrl = $pDoc->find('div.fullMedia a.internal');

        $meta['url'] = $imageUrl;

        // get more info via api
        $shortName = str_replace('https://commons.wikimedia.org/wiki/', '', $imageUrl);
        $apiUrl = 'https://commons.wikimedia.org/w/api.php?action=query&titles=' . $shortName . '&prop=imageinfo&iiprop=extmetadata&format=json';
        $apiResult = json_decode($this->getFile($apiUrl), true);
        $info = [];
        foreach ($apiResult['query']['pages'] as $page) {
            foreach ($page['imageinfo'] as $imageInfo) {
                foreach ($imageInfo['extmetadata'] as $key => $data) {
                    $info[lcfirst($key)] = $data['value'];
                }
            }
        }

        $meta['title'] = $info['objectName'];
        $meta['keywords'] = explode('|', $info['categories']);
        $meta['description'] = $info['imageDescription'];


        // author
        $pDoc = \PhpQuery::newDocumentHTML($info['artist']);
        $meta['author'] = $pDoc->find('a:first')->text();

        $meta['license'] = [
            'full' => $info['licenseShortName'] . ', ' . $info['licenseUrl'],
            'short' => $info['licenseShortName'],
            'url' => $info['licenseUrl']
        ];


        // get the url
        $apiUrl = 'https://commons.wikimedia.org/w/api.php?action=query&titles=' . $shortName . '&prop=imageinfo&iiprop=url&format=json';
        $apiResult = json_decode($this->getFile($apiUrl), true);
        foreach ($apiResult['query']['pages'] as $page) {
            foreach ($page['imageinfo'] as $imageInfo) {
                $src = $meta['src'] = $imageInfo['url'];
            }
        }

        $markers['id'] = $meta['id'] = str_replace(':', '__', $shortName);

        $markers['title'] = str_replace(' ', '_', $meta['title']);
        $markers['user'] = $meta['author'];

        // set meta data for IPTC tagging
        $session->setArgument('meta', $meta);

        $this->workFile = $this->replaceMarkers($this->configuration['fileNamePattern'], $markers);
        $this->legal = $this->replaceMarkers($this->configuration['legalPattern'], $markers, false);

        $this->workFile = preg_replace('/\W+/', '_', $this->workFile) . '.' . strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $this->writeFile($src, CUTTER_uploadPath . $this->workFile);
    }

    /**
     * @return string Answer
     */
    protected
    function login($imageUrl)
    {
        $res = $this->post('https://pixabay.com/en/accounts/login/', [
            'username' => $this->configuration['login']['user'],
            'password' => $this->configuration['login']['password'],
            'next' => pathinfo($imageUrl, PATHINFO_DIRNAME) . '/',
            'submit' => 'Log in',
        ]);

        return $res;
    }

    protected
    function getFile($src): string
    {
        return file_get_contents($src);
    }

}
