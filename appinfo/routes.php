<?php
/**
 *
 * LogCleaner APP (Nextcloud)
 *
 * @author Wolfgang Tödt <wtoedt@gmail.com>
 *
 * @copyright Copyright (c) 2025 Wolfgang Tödt
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
return [
  'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
     ['name' => 'page#index', 'url' => '/', 'verb' => 'POST','postfix' => 'logcleaner'],
     ['name' => 'Settings#getlog', 'url' => '/getdata', 'verb' => 'GET'],
     ['name' => 'Settings#getAppValueZ', 'url' => '/getparam/{who}', 'verb' => 'GET'],
     ['name' => 'Settings#getAll', 'url' => '/getall', 'verb' => 'GET'],
     ['name' => 'Settings#delDub', 'url' => '/deldub', 'verb' => 'GET'],
     ['name' => 'Settings#dellog', 'url' => '/dellog/{logid}', 'verb' => 'GET'],
     ['name' => 'Settings#setSettingZeilen', 'url' => '/setlines/{who}/{zeilen}', 'verb' => 'GET'],
  ]
];
