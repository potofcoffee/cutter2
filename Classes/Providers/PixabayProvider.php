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

use VMFDS\Cutter\Core\Debugger;

class PixabayProvider extends AbstractProvider
{

    static protected $handledHosts = ['pixabay.com'];
    public $hasCaptcha = 0;

    public function __construct()
    {
        parent::__construct();
        $this->configuration['baseUrl'] = 'https://pixabay.com/en/photos/download/';
        $this->configuration['loginUrl'] = 'https://pixabay.com/en/accounts/login/';
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
        return 'pixabay';
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


        $meta['url'] = $imageUrl;
        $tags = $pDoc->find('h1 a');
        foreach ($tags as $tag) {
            $keyword = trim($tag->textContent);
            if ($keyword) $meta['keywords'][] = $keyword;
        }
        $meta['title'] = join('-', $meta['keywords']);
        $meta['description'] = '';

        $metaItems = array();
        foreach (pq('meta') as $metaObj) {
            $key = pq($metaObj)->attr('name');
            $value = pq($metaObj)->attr('content');
            $metaItems[$key] = $value;
        }


        foreach (pq('div.right div.clearfix a') as $item) {
            $author = explode(' / ', trim((string)(pq($item)->html())))[0];
        }
        $meta['author'] = $author;
        $meta['license'] =
            [
                'full' => 'CC0 Public Domain, https://creativecommons.org/publicdomain/zero/1.0/deed.de',
                'short' => 'CC0',
                'url' => 'https://creativecommons.org/publicdomain/zero/1.0/deed.de',
            ];


        $path = basename(parse_url($imageUrl, PHP_URL_PATH));

        $tmp = explode('-', $path);
        $markers['id'] = $meta['id'] = substr($tmp[count($tmp) - 1], 0, -1);

        unset($tmp[count($tmp) - 1]);
        $markers['title'] = join('-', $tmp);
        $markers['user'] = $meta['author'];

        // set meta data for IPTC tagging
        $session->setArgument('meta', $meta);

        $src = str_replace('_640', '_1280', $metaItems['twitter:image']);

        $this->workFile = $this->replaceMarkers($this->configuration['fileNamePattern'], $markers);
        $this->legal = $this->replaceMarkers($this->configuration['legalPattern'], $markers, false);

        // TODO:
        $this->workFile = 'pixabay_'.$meta['author'].'_'.$meta['title'];
        $this->legal = 'pixabay / '.$meta['author'];

        $this->workFile = preg_replace('/\W+/', '_', $this->workFile) . '.' . pathinfo($src, PATHINFO_EXTENSION);
        $this->writeFile($src, CUTTER_uploadPath . $this->workFile);
    }

    /**
     * @return string Answer
     */
    protected function login($imageUrl)
    {
        $res = $this->post('https://pixabay.com/en/accounts/login/', [
            'username' => $this->configuration['login']['user'],
            'password' => $this->configuration['login']['password'],
            'next' => pathinfo($imageUrl, PATHINFO_DIRNAME) . '/',
            'submit' => 'Log in',
        ]);

        return $res;
    }

    protected function getFile($src): string
    {
        return file_get_contents($src);
    }

}
