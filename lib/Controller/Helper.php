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
declare(strict_types=1);

namespace OCA\LogCleaner\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCA\LogReader\Service\SettingsService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Util;

use OCP\HintException;
use OCP\IConfig;


class Helper
{
    //$authorized = json_decode($this->config->getAppValue(Application::APP_ID, 'authorized', '["admin"]'))

    /** @var ISubAdmin $subAdmin */

    private IConfig $config;
    private $appName;
    private $l;
    #[NoCSRFRequired]
    //#[NoAdminRequired]      //<----------- auskommentiert haben nur admins access
    //#[FrontpageRoute(verb: 'GET', url: '/')]
   #[FrontpageRoute(verb: 'POST', url: '/')]

   public function __construct(IConfig $config, IL10N $l, $appName){
        $this->config = $config;
        $this->l = $l;
        $this->appName = $appName;
    }

    public function getAppValue($key) {
        return $this->config->getAppValue($this->appName, $key);
    }

    public function setAppValue($key, $value) {
        return $this->config->setAppValue($this->appName, $key, $value);
    }

    public function wtlogtoarr(?string $wtlog)
    {
        if ($wtlog === null) {
            $wtlog = "";
            return;
        }
        return file("$wtlog");
    }

    public function wtzeileweg(?int $wtzeile, ?array $wwt, ?string $wtlogfile)
    {
        array_splice($wwt, $wtzeile, 1);
        $file = $wtlogfile;
        $current = $wwt;
        file_put_contents($file, $current,LOCK_EX);
        return;
    }

    public function myoutputdata($wtlog,$wtall,$wtlogfilezeilen,$wt_characters) {
        $wtarr =[];
        $obja = new \stdClass();
        if ($wtall === 0) {
          $obja->all = 0;
          $obja->zeit = '';
          $obja->ip = '';
          $obja->user = '';
          $obja->app = '';
          $obja->method = '';
          $obja->zeit = '';
          $obja->grund = $this->l->t('no log entries available');
          return $obja;
        }
        $wt_characters = intval($wt_characters);
        $obja->all = $wtall;
        $out = '';
        $teile = explode(',"', $wtlog);
        for($i=1; $i < 9; $i++) {
          $teilee = explode('":', $teile[$i]);
          $log[$i-1][0] = str_replace('"', "", $teilee[0]);
          $log[$i-1][1] = str_replace('"', "", $teilee[1]);
        }
        for($i=0; $i < 8; $i++) {
          $trenn = ($i < 7) ? ' * ' : null;
          switch ($i) {
            case "0":
              break;
            case "1":
              $obja->zeit = $this->l->t('Time') . " : ".date("d.m.Y - H:i:s", strtotime($log[$i][1] . " + 1 Hour")) . $trenn;
              break;
            case "2":
              $obja->ip = $this->l->t('IP') . " :".$log[$i][1] . $trenn;
              break;
            case "3":
              $obja->user = $this->l->t('User') . " :".$log[$i][1] . $trenn;
              break;
            case "4":
              $obja->app = $this->l->t('App') . " :".$log[$i][1] . $trenn;
              break;
            case "5":
              $obja->method = $this->l->t('Method') . " :".$log[$i][1] . $trenn;
              break;
            case "6":
              $obja->url = $this->l->t('URL') . " :".$log[$i][1] . $trenn;
              break;
            case "7":
              $obja->grund = $this->l->t('Reason') . " :".substr($log[$i][1], 0, $wt_characters) . $trenn;
              break;
            //default:
          }
          switch ($log[$i][1]) {
            case "0":
              $obja->error = "alert alert-level0";
              break;
            case "1":
              $obja->error = "alert alert-level1";
              break;
            case "2":
              $obja->error = "alert alert-level2";
              break;
            case "3":
              $obja->error = "alert alert-level3";
              break;
            case "4":
              $obja->error = "alert alert-level4";
              break;
          }
        }
        $obja->id = $wtlogfilezeilen;
        return $obja;
      }
}
