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

class DefaultFetchFromUrlProvider extends AbstractProvider
{

    static protected $handledHosts = [];
    public $hasCaptcha = 0;

    /**
     * Checks if this provider can handle urls from a specific host
     * Since this is a fallback provider, this method will always return
     * false. The factory will call this provider manually if needed.
     *
     * @param \string $host Host
     * @return bool True, if provider can handle urls from this host
     */
    static public function canHandleHost($host): bool
    {
        return FALSE;
    }

    /**
     * Get the name for this provider
     * @return string Provider name
     */
    static public function getName()
    {
        return 'defaultFetchFromUrl';
    }

    /**
     * Retrieve an image from a specific url
     * @param \string $imageUrl url
     */
    public function retrieveImage($imageUrl)
    {
        $session = \VMFDS\Cutter\Core\Session::getInstance();
        $meta = [
            'author' => '',
            'url' => $imageUrl,
            'legal' => '',
            'id' => '',
            'license' => '',
            'title' => '',
            'keywords' => '',
        ];


        $this->legal = '';
        $this->workFile = preg_replace('/\W+/', '_', pathinfo($imageUrl, PATHINFO_FILENAME)) . '.' . pathinfo($imageUrl, PATHINFO_EXTENSION);

        $session->setArgument('meta', $meta);
        $session->setArgument('legal', '');
        $session->setArgument('workFile', $this->workFile);

        $this->writeFile($imageUrl, CUTTER_uploadPath . $this->workFile);
    }


}
