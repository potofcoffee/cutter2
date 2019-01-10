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

/**
 * Description of FreeImagesProvider
 *
 * @author chris
 */
class FreeImagesProvider extends AbstractProvider
{
    static protected $handledHosts = array('www.freeimages.com',
        'freeimages.com',
        'sxc.hu',
        'www.sxc.hu'
    );
    public $hasCaptcha             = 1;

    /**
     * Checks if this provider can handle urls from a specific host
     * @param \string $host Host
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
        return 'freeimages';
    }

    public function __construct()
    {
        parent::__construct();
        $this->configuration['baseUrl']  = 'http://www.freeimages.com';
        $this->configuration['loginUrl'] = 'http://www.freeimages.com/signin?next=/';
    }

    /**
     * Retrieve an image from a specific url
     * @param \string $imageUrl url
     */
    public function retrieveImage($imageUrl)
    {
        $session = \VMFDS\Cutter\Core\Session::getInstance();

        $this->login();
        $pDoc = \PhpQuery::newDocumentHTML($this->getFile($imageUrl));


        $meta['url']   = $imageUrl;
        $meta['title'] = $pDoc->find('h1 strong')->text();
        $tags          = $pDoc->find('.ui-tags li');
        foreach ($tags as $tag) {
            $meta['keywords'][] = $tag->textContent;
        }
        $meta['description'] = $pDoc->find('.detail-info-tags p')->text();
        $meta['author']      = $markers['user']     = $pDoc->find('#photographer-name')->text();
        $meta['license']     =
            [
                'full' => 'Freeimages.com Content License, http://www.freeimages.com/license',
                'url' => 'http://www.freeimages.com/license',
                'short' => 'Freeimages.com CL',
            ];



        $links = $pDoc->find('.img-btns li a');
        foreach ($links as $link) {
            $meta['src'][] = $this->configuration['baseUrl'].$link->getAttribute('href');
        }


        $path             = str_replace('/photo/', '',
            parse_url($imageUrl, PHP_URL_PATH));
        $tmp              = explode('-', $path);
        $markers['id']    = $meta['id']       = $tmp[count($tmp) - 1];
        unset($tmp[count($tmp) - 1]);
        $markers['title'] = join('-', $tmp);

        // set meta data for IPTC tagging
        $session->setArgument('meta', $meta);

        $src = $meta['src'][0];

        $this->workFile = $this->replaceMarkers($this->configuration['fileNamePattern'],
            $markers);
        $this->legal    = $this->replaceMarkers($this->configuration['legalPattern'],
            $markers, FALSE);

        $this->workFile = preg_replace('/\W+/', '_', $this->workFile).'.'.pathinfo($src,
                PATHINFO_EXTENSION);
        $this->writeFile($src, CUTTER_uploadPath.$this->workFile);
    }

    /**
     * Log into freeimages.com
     *
     * @return string Answer
     */
    protected function login()
    {

        // step 1: Get the CSRF token
        $doc    = $this->getDOMDocument($this->getFile($this->configuration['loginUrl']));
        $inputs = $doc->getElementsByTagName('input');
        foreach ($inputs as $input) {
            if ($input->getAttribute('name') == 'csrfmiddlewaretoken')
                    $token = $input->getAttribute('value');
        }


        $res = $this->post('http://www.freeimages.com/signin',
            array(
            'csrfmiddlewaretoken' => $token,
            'username' => $this->configuration['login']['user'],
            'password' => $this->configuration['login']['password'],
            'captcha' => $this->data['captcha']['text'],
            'uid' => $this->data['captcha']['hash'],
            'remember' => 'true',
            'next_url' => '/'
        ));
    }

    /**
     * Extract image source URL from a document
     *
     * @param \DOMDocument $doc DOMDocument object
     * @return string Image source url
     */
    protected function getSourceUrl($doc)
    {
        $img = $doc->getElementsByTagName('img')->item(1);
        $src = $img->getAttribute('src');
        if (strpos($src, '//') === false) {
            $sep = (substr($src, 0, 1) != '/') ? '/' : '';
            $src = $this->configuration['baseUrl'].$sep.$src;
        }
        return $src;
    }

    /**
     * Extract title and legal info from a document
     *
     * @param string $src Source url
     * @param \DOMDocument $doc Document object
     */
    protected function getTitleAndInfo($src, $doc)
    {
        if ($this->configuration['nameByTitle']) {
            $searchArray  = array();
            $replaceArray = array();
            foreach ($marker as $needle => $val) {
                $searchArray[]  = '###'.strtoupper($needle).'###';
                $replaceArray[] = str_replace(' ', '-',
                    str_replace('.', '', trim($val)));
            }
            $this->workFile = str_replace($searchArray, $replaceArray,
                $this->configuration['nameByTitle']['pattern']);

            $this->legal = str_replace($searchArray, $replaceArray,
                $this->configuration['nameByTitle']['legalPattern']);
        }
        $this->workFile = pathinfo($src, PATHINFO_FILENAME);
    }

    /**
     * Get a new captcha hash
     * @return string Captcha Hash
     */
    public function getCaptchaHash()
    {
        $this->data['captcha']['hash'] = $this->getFile('http://www.freeimages.com/accounts/captcha/new?length=5&app=accounts&change=1');
        return $this->data['captcha']['hash'];
    }

    /**
     * Get a new captcha image
     * @param string Hash
     * @return string Captcha image url
     */
    public function getCaptchaImage($hash)
    {
        return 'http://www.freeimages.com/accounts/captcha/'.$hash;
    }
}