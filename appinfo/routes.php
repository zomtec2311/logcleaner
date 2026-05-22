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
    ['name' => 'page#show', 'url' => '/show/{level}', 'verb' => 'GET'],
    ['name' => 'Settings#getlog', 'url' => '/getdata', 'verb' => 'GET'],
    ['name' => 'Settings#getalllog', 'url' => '/getalldata', 'verb' => 'GET'],
    ['name' => 'Settings#getallfilteredlog', 'url' => '/getallfiltereddata/{level}', 'verb' => 'GET'],
    ['name' => 'Settings#getallfilteredapplog', 'url' => '/getallfilteredappdata/{key}', 'verb' => 'GET'],
    ['name' => 'Settings#emptylog', 'url' => '/emptylog', 'verb' => 'GET'],
    ['name' => 'Settings#getAppValueZ', 'url' => '/getparam', 'verb' => 'GET'],
    ['name' => 'Settings#getLL', 'url' => '/getll', 'verb' => 'GET'],
    ['name' => 'Settings#setLL', 'url' => '/setll/{who}', 'verb' => 'GET'],
    ['name' => 'Settings#delDub', 'url' => '/deldub', 'verb' => 'GET'],
    ['name' => 'Settings#countDub', 'url' => '/countdub', 'verb' => 'GET'],
    ['name' => 'Settings#logapps', 'url' => '/logapps', 'verb' => 'GET'],
    ['name' => 'Settings#dellog', 'url' => '/dellog/{logid}', 'verb' => 'GET'],
    ['name' => 'Settings#delLevel', 'url' => '/delLevel/{level}', 'verb' => 'GET'],
    ['name' => 'Settings#setSettingZeilen', 'url' => '/setlines/{who}/{zeilen}', 'verb' => 'GET'],
    ['name' => 'Settings#logfileandsize', 'url' => '/logfileandsize', 'verb' => 'GET'],
    ['name' => 'Settings#getcntll', 'url' => '/getcntll', 'verb' => 'GET'],
    ['name' => 'Settings#delapp', 'url' => '/delapp/{app}', 'verb' => 'GET'],
    ['name' => 'Settings#isnoti', 'url' => '/isnoti', 'verb' => 'GET'],
    ['name' => 'Logs#list', 'url' => '/logs/list/{limit}/{offset}', 'verb' => 'GET'],
    ['name' => 'Logs#listlevel', 'url' => '/logs/list/level/{limit}/{offset}/{level}', 'verb' => 'GET'],
    ['name' => 'Logs#listapp', 'url' => '/logs/list/app/{limit}/{offset}/{app}', 'verb' => 'GET'],
    ['name' => 'Logs#showdetail', 'url' => '/showdetail/{detail}', 'verb' => 'GET'],
    ['name' => 'Logs#removeDub', 'url' => '/removeDub/{anzahl}', 'verb' => 'GET'],
    ['name' => 'Logs#getAll', 'url' => '/getall', 'verb' => 'GET'],
    ['name' => 'Logs#analyse', 'url' => '/logs/analyse', 'verb' => 'GET'],
    ['name' => 'Logs#removelog', 'url' => '/removelog/{logid}', 'verb' => 'GET'],
    ['name' => 'Logs#dellines', 'url' => '/logs/dellines', 'verb' => 'POST'],
    ['name' => 'Logs#dellinesapp', 'url' => '/logs/dellinesapp', 'verb' => 'POST'],
    ['name' => 'Logs#getAdmins', 'url' => '/getadmins', 'verb' => 'GET'],
    ['name' => 'Logs#getNotiAdmins', 'url' => '/getnotiadmins', 'verb' => 'GET'],
    ['name' => 'Logs#testLogEmail', 'url' => '/testlogemail', 'verb' => 'GET'],
    ['name' => 'Logs#testLogNotification', 'url' => '/testlognoti', 'verb' => 'GET'],
  ]
];
