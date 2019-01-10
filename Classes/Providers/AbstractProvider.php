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

require_once(CUTTER_basePath . 'vendor/electrolinux/phpquery/phpQuery/phpQuery.php');

/**
 * Description of AbstractProvider
 *
 * @author chris
 */
class AbstractProvider
{
    /** @var array List of hosts handled by this provider */
    static protected $handledHosts = [];

    /** @var bool True if this provider needs a captcha*/
    public $hasCaptcha = false;

    /** @var string Final name of the downloaded file */
    public $workFile = '';

    /** @var string Final string for legal info */
    public $legal = '';

    /** @var array Internal data */
    public $data = [];

    /** @var array Provider configuration */
    protected $configuration = [];

    /** @var  Instance of CURL used */
    protected $curl;

    /** @var string Name of the cookie jar for CURL */
    protected $cookieJar;

    /** @var LicenseFactory */
    protected $licenseFactory = null;

    /**
     * AbstractProvider constructor.
     */
    public function __construct()
    {
        $confManager = \VMFDS\Cutter\Core\ConfigurationManager::getInstance();
        $this->setConfiguration($confManager->getConfigurationSet($this->getKey(),
            'Providers'));
        $this->licenseFactory = new LicenseFactory();
        $this->cookieJar = '/tmp/cutter-cookie-' . md5(time());
        $this->initCurl();
    }

    /**
     * Set configuration array
     * @param array $configuration Configuration array
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Get this provider's key (class without namespace and 'Provider')
     * @return string
     */
    public function getKey(): string
    {
        $class = get_class($this);
        return str_replace('Provider', '',
            str_replace('VMFDS\\Cutter\\Providers\\', '', $class));
    }

    /**
     * Initialize CURL for use with this object
     */
    protected function initCurl()
    {
        $this->curl = \curl_init();
        curl_setopt($this->curl, CURLOPT_COOKIELIST, '');
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_NOBODY, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
//        curl_setopt($this->curl, CURLOPT_USERAGENT,
//            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookieJar);  //could be empty, but cause problems on some hosts
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookieJar);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl, CURLOPT_AUTOREFERER, 1);
    }

    /**
     * Checks if this provider can handle urls from a specific host
     * @param \string $host Host
     * @return bool True, if provider can handle urls from this host
     */
    static public function canHandleHost($host): bool
    {
        return in_array($host, self::$handledHosts);
    }

    public function __destroy()
    {
        if (file_exists($this->cookieJar)) unlink($this->cookieJar);
    }

    /**
     * Get a \DOMDocument object from HTML
     *
     * @param string $html HTML
     * @return \DOMDocument \DOMDocument object
     */
    protected function getDOMDocument($html): \DOMDocument
    {
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        return $doc;
    }

    /**
     * Retrieve a file from a URL and write it to disk
     *
     * @param string $src URL
     * @param string $destination Destination path (incl. file name)
     */
    protected function writeFile($src, $destination)
    {
        $rawFile = $this->getFile($src);
        $fp = fopen($destination, 'w');
        fwrite($fp, $rawFile);
        fclose($fp);
    }

    /**
     * Resolve redirects for a source url
     * @param string $src Url
     */
    protected function followRedirects($src)
    {
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);
        while ($src) {
            echo $src . '<br />';
            $res = $this->curl_exec($src);
            $src = curl_getinfo($this->curl, CURLINFO_REDIRECT_URL);
        }
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
    }

    /**
     * Wrapper function for curl_exec()
     *
     * @param string $url Url to retrieve
     * @return string Curl answer
     */
    protected function curl_exec($url): string
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $answer = curl_exec($this->curl);
        if (($answer === false) || (is_null($answer))) {
            die(curl_error($this->curl));
        } else return $answer;
    }

    /**
     * Retrieve a file from a URL
     *
     * @param string $src URL
     * @return string File contents
     */
    protected function getFile($src): string
    {
        return $this->curl_exec($src);
    }

    /**
     * Retrieve a file from a string and write it to disk
     *
     * @param string $stream Stream
     * @param string $destination Destination path (incl. file name)
     */
    protected function writeStream($stream, $destination): string
    {
        $fp = fopen($destination, 'w');
        fwrite($fp, $stream);
        fclose($fp);
    }

    /**
     * Get a document title
     * @param \DOMDocument $doc \DOMDocument object
     * @return string Title
     */
    protected function getDocTitle($doc): string
    {
        return $titleField = $doc->getElementsByTagName('title')->item(0)->nodeValue;
    }

    /**
     * Get a number of markers from a text via regex
     *
     * @param string $regex Regular expression
     * @param string $text Text to search
     * @param array $matches Array to indicate a name for each marker in the regex
     * @return array Markers and values
     */
    protected function regexMarkers($regex, $text, $matches)
    {
        if (preg_match($regex, $text, $tmp)) {
            foreach ($matches as $idx => $key) {
                $marker[$key] = $tmp[$idx];
            }
        }
        return $marker;
    }

    /**
     * Replace multiple markers in a text
     *
     * @param string $text Original text
     * @param array $markers Markers and values
     * @return string Text with markers replaced
     */
    protected function replaceMarkers($text, $markers, $sanitize = TRUE)
    {
        foreach ($markers as $needle => $val) {
            $searchArray[] = '###' . strtoupper($needle) . '###';
            if ($sanitize) {
                $val = str_replace(' ', '-', str_replace('.', '', trim($val)));
            }
            $replaceArray[] = $val;
        }
        return str_replace($searchArray, $replaceArray, $text);
    }

    /**
     * Post a form with data filled in
     *
     * @param \DOMDocument $doc Document
     * @param string $id Element id of form or parent element
     * @param array $data Arguments for form
     * @return string Answer
     */
    protected function postForm($doc, $id, $data)
    {
        $form = $this->getForm($doc, $id);
        foreach ($data as $key => $val) {
            $form['arguments'][$key] = $val;
        }
        $host = parse_url($form['action'], PHP_URL_HOST);
        if (!$host) {
            $form['action'] = $this->config['baseUrl']
                . (substr($form['action'], 0, 1) == '/' ? '' : '/')
                . $form['action'];
        }
        return $this->post($form['action'], $form['arguments']);
    }

    /**
     * Extract a form from a document
     *
     * @param \DOMDocument $doc Document
     * @param string $id Id of the form or a parent element
     * @return array Form data
     */
    protected function getForm($doc, $id)
    {
        $data = array();
        $el = $doc->getElementById($id);
        if ($el->tagName != 'form') {
            $el = $el->getElementsByTagName('form')->item(0);
        }
        $data['action'] = $el->getAttribute('action');
        $inputs = $el->getElementsByTagName('input');
        foreach ($inputs as $input) {
            $key = $input->getAttribute('name');
            if ($key) {
                $data['arguments'][$key] = $input->getAttribute('value');
            }
        }
        return $data;
    }

    /**
     * Issues a POST request to an url
     *
     * @param string $url Url to post to
     * @param array $data Arguments for the POST request
     * @return string Answer
     */
    protected function post($url, $data = array())
    {
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
        return $this->curl_exec($url);
    }

    protected function dump($s, $title = '')
    {
        return '<pre>' . ($title ? '<b>' . $title . ':</b>' : '') . print_r($s, true) . '</pre>';
    }
}