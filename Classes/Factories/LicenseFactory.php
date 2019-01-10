<?php
/*
 * cutter
 *
 * Copyright (c) 2017 Volksmission Freudenstadt, http://www.volksmission-freudenstadt.de
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


namespace VMFDS\Cutter\Factories;


use VMFDS\Cutter\Core\ConfigurationManager;

class LicenseFactory
{

    /** @var array Configuration */
    protected $configuration = [];

    /**
     * LicenseFactory constructor.
     */
    public function __construct()
    {
        $this->configuration = ConfigurationManager::getInstance()->getConfigurationSet('licenses');
    }

    /**
     * Find license info by url
     * @param string $url Url
     * @return array License info
     */
    public function getByUrl(string $url): array {
        foreach ($this->configuration['licenses'] as $license) {
            if ($license['url'] == $url) return $license;
        }
        return [];
    }

    /**
     * Find license info by license code ("short")
     * @param string $code License code, e.g. 'CC-BY-SA-4.0'
     * @return array License info
     */
    public function getByCode(string $code): array {
        $code = str_replace(' ', '-', $code);
        return $this->configuration['licenses'][$code] ?? [];
    }

}