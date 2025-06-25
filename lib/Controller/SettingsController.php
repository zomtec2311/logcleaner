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
namespace OCA\LogCleaner\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\DataResponse;
use Psr\Log\LoggerInterface;
use OCP\IAppConfig;
use OCP\App\IAppManager;

class SettingsController extends Controller {
	private $config;
	private $l;
	public function __construct(
		IL10N $l,
		IConfig $config,
		IRequest $request,
		private Helper $helper,
		private readonly LoggerInterface $logger,
		private IAppManager $appManager,
		private IAppConfig $appConfig
	) {
		parent::__construct('logcleaner', $request);
		$this->l = $l;
		$this->config = $config;
		$this->helper = $helper;
		$this->appManager = $appManager;
		//$this->appconfig = $appConfig;
	}

	#[NoAdminRequired]
	#[UseSession]

	public function setSettingZeilen($who,$zeilen): DataResponse {
		$this->config->setAppValue('logcleaner', $who, $zeilen);
		return new DataResponse([
            ]);
	}

	public function getAppValueZ($who): DataResponse {
		//return $this->config->getAppValue('logcleaner', $who);
		return new DataResponse([
                'valuez' => $this->config->getAppValue('logcleaner', $who),
            ]);
	}
	
	public function getLL(): DataResponse {
		//return $this->config->getSystemValue('loglevel');
		return new DataResponse([
                'loglevel' => $this->config->getSystemValue('loglevel'),
            ]);
	}
	
	public function setLL($who): DataResponse {
		$who = intval($who);
		if (!is_int($who) || $who < 0 || $who > 4) {
				$this->logger->debug('Cannot set loglevel');
			}
			// Set backend loglevel directly via system value
			$this->config->setSystemValue('loglevel', $who);	
		//return;
			return new DataResponse([
            ]);
	}

	public function getlog(?int $logid = null) {
		if ($logid === null) {
			$logid = null;
		}
		$wt_out = "";
		$array = [];
		$wtarr =[];
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
				if (!file_exists($wtlogfile)) {
					$obja = new \stdClass();
					$obja->all = 0;
					$obja->zeit = '';
					$obja->ip = '';
					$obja->user = '';
					$obja->app = '';
					$obja->method = '';
					$obja->zeit = '';
					$obja->grund = $this->l->t('log file cannot be located');
					$wtarr [] = $obja;
					return $wtarr;
				}
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		$wt_zeilen = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_zeilen', '1', false);	
		$wt_offset = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_offset', '0', false);
		$wt_art = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_art', '1', false);
		$wt_characters = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_characters', '500', false);
		$wtpara_menue = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_menue', '2', false);
		if((!isset($wtpara_menue)) || ($wtpara_menue === 0)) {
			$this->helper->setAppValue('wtparam_menue', 1);
		}
		if((!isset($wt_zeilen)) || ($wt_zeilen === 0)) {
			$wt_zeilen = 5;
			$this->helper->setAppValue('logcleaner_wt_zeilen', 5);
		}
		if((!isset($wt_art)) || ($wt_art === 0)) {
			$wt_art = 2;
			$this->helper->setAppValue('logcleaner_wt_art', 9);
		}
		if((!isset($wt_characters)) || ($wt_characters === 0)) {
			$wt_characters = 500;
			$this->helper->setAppValue('logcleaner_wt_characters', 500);
		}
		if (isset($logid)) {
			$this->helper->wtzeileweg($logid, $wwt, $wtlogfile);
			$wwt = $this->helper->wtlogtoarr($wtlogfile);
		}
		$wtlogfilezeilen = count($wwt);
		if ($wtlogfilezeilen == 0) {
			$obja = new \stdClass();
		  $obja->all = 0;
		  $obja->zeit = '';
		  $obja->ip = '';
		  $obja->user = '';
		  $obja->app = '';
		  $obja->method = '';
		  $obja->zeit = '';
			$obja->grund = $this->l->t('no log entries available');
			$wtarr [] = $obja;
		  return $wtarr;
		}
		$wwt = array_splice($wwt, -$wt_zeilen);
		for($i=0; $i < $wt_zeilen; $i++) {
			$a = (isset($wwt[$wt_zeilen-$i-1])) ? $wwt[$wt_zeilen-$i-1] : null;
			if ($a) {
				if ($wt_zeilen >= count($wwt)) {
					$wtarr []= $this->helper->myoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen + $wt_zeilen - count($wwt)-$i-1,$wt_characters,$wt_offset); $array[$i] = $i;
				}
			 	else {
					$wtarr []= $this->helper->myoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen-$i,$wt_characters,$wt_offset); $array[$i] = $i;
			 	}
			}
		}
		return $wtarr;
	}

	public function getalllog(?int $logid = null): DataResponse {
		if ($logid === null) {
			$logid = null;
		}
		$wt_out = "";
		$array = [];
		$wtarr =[];
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
				if (!file_exists($wtlogfile)) {
					$obja = new \stdClass();
					$obja->all = 0;
					$obja->zeit = '';
					$obja->ip = '';
					$obja->user = '';
					$obja->app = '';
					$obja->method = '';
					$obja->zeit = '';
					$obja->grund = $this->l->t('log file cannot be located');
					$wtarr [] = $obja;
					//return $wtarr;
					return new DataResponse([
                'al' => $wtarr,
            ]);
				}
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		$wt_zeilen = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_zeilen', '5', false);;
		$wt_offset = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_offset', '0', false);
		$wt_art = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_art', '2', false);
		$wt_characters = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_characters', '500', false);
		$wtpara_menue = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_menue', '1', false);
		$wtpara_logmessage = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logmessage', '2', false);
		$wtpara_cron_deldub = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_cron_deldub', '1', false);
		
		if((!isset($wtpara_cron_deldub)) || ($wtpara_cron_deldub === 0)) {
			$wtpara_cron_deldub = 1;
			$this->helper->setAppValue('wtpara_cron_deldub', 1);
		}		
		if((!isset($wtpara_menue)) || ($wtpara_menue === 0)) {
			$this->helper->setAppValue('wtparam_menue', 1);
		}
		if((!isset($wt_zeilen)) || ($wt_zeilen === 0)) {
			$wt_zeilen = 5;
			$this->helper->setAppValue('logcleaner_wt_zeilen', 5);
		}
		if((!isset($wt_art)) || ($wt_art === 0)) {
			$wt_art = 2;
			$this->helper->setAppValue('logcleaner_wt_art', 9);
		}
		if((!isset($wtpara_logmessage)) || ($wtpara_logmessage === 0)) {
			$this->helper->setAppValue('wtparam_logmessage', 2);
		}
		if((!isset($wt_characters)) || ($wt_characters === 0)) {
			$wt_characters = 500;
			$this->helper->setAppValue('logcleaner_wt_characters', 500);
		}
		if (isset($logid)) {
			$this->helper->wtzeileweg($logid, $wwt, $wtlogfile);
			$wwt = $this->helper->wtlogtoarr($wtlogfile);
		}
		$wtlogfilezeilen = count($wwt);
		if ($wtlogfilezeilen == 0) {
			$obja = new \stdClass();
		  $obja->all = 0;
		  $obja->zeit = '';
		  $obja->ip = '';
		  $obja->user = '';
		  $obja->app = '';
		  $obja->method = '';
		  $obja->zeit = '';
			$obja->grund = $this->l->t('no log entries available');
			$wtarr [] = $obja;
		  //return $wtarr;
		  return new DataResponse([
                'al' => $wtarr,
            ]);
		}
		$wt_zeilen = $wtlogfilezeilen;
		$wwt = array_splice($wwt, -$wt_zeilen);
		for($i=0; $i < $wt_zeilen; $i++) {
			$a = (isset($wwt[$wt_zeilen-$i-1])) ? $wwt[$wt_zeilen-$i-1] : null;
			if ($a) {
				if ($wt_zeilen >= count($wwt)) {
					$wtarr []= $this->helper->myoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen + $wt_zeilen - count($wwt)-$i-1,$wt_characters,$wt_offset); $array[$i] = $i;
				}
			 	else {
					$wtarr []= $this->helper->myoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen-$i,$wt_characters,$wt_offset); $array[$i] = $i;
			 	}
			}
		}
		//return $wtarr;
		return new DataResponse([
                'al' => $wtarr,
            ]);
	}

	public function logfileandsize(): DataResponse {
	try {
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		if (file_exists($wtlogfile)) {
			$teile = explode("/", $wtlogfile);
			$obja = new \stdClass();
			$obja->file = $wtlogfile;
			$obja->filearr = $teile;
			$obja->appversion = $this->appManager->getAppVersion('logcleaner', true);
			$obja->filesize = $this->show_filesize($wtlogfile,2);
		}
		else {
			$obja = new \stdClass();
			$obja->file = '';
			$obja->filearr = [];
			$obja->filesize = '';
		}
	return new DataResponse([
                'file' => $obja->file,
				'filearr' => $obja->filearr,
				'appversion' => $obja->appversion,
				'filesize' => $obja->filesize,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error(
                'LogCleaner: FATAL ERROR or EXCEPTION in SettingsController->logfileandsize: ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                ['app' => 'logcleaner']
            );
            return new DataResponse([
                'file' => -1,
				'filearr' => -1,
				'appversion' => -1,
				'filesize' => -1,
            ], 500);
        }
	}

	public function show_filesize($filename, $decimalplaces = 0) {
	  $size = filesize($filename);
	  $sizes = array('B', 'kB', 'MB', 'GB', 'TB');
	  for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) {
	     $size /= 1024;
	  }
	  return round($size, $decimalplaces).' '.$sizes[$i];
	}
	
	public function wtfilesize($size, $decimalplaces = 0) {
	  $sizes = array('B', 'kB', 'MB', 'GB', 'TB');
	  for ($i=0; $size > 1024 && $i < count($sizes) - 1; $i++) {
	     $size /= 1024;
	  }
	  return round($size, $decimalplaces).' '.$sizes[$i];
	}

	public function dellog(string $logid): DataResponse {
		if ($logid === null) {
			$logid = null;
		}
		$logid = intval($logid);
		$this->getlog($logid);
		return new DataResponse([
            ]);
	}

	public function getAll(): DataResponse {
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		$wtlogfilezeilen = count($wwt);
		//return $wtlogfilezeilen;
		return new DataResponse([
				//'wtarr' => $wtarr,
                'wtlogfilezeilen' => $wtlogfilezeilen,
            ]);
	}

	public function delDub(): DataResponse {
		$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
		$i = 0;
		$ii = 0;
		$tmp_array = array();
		$key_array = array();
		$temp_array = array();
		$new_array = array();
		$uu = 0;
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		
		$filesizebefore = filesize($wtlogfile);
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		foreach ($wwt as $value) {
			$tmp_array[] = explode(',"', $value);
		}
		unset($value);
		foreach($tmp_array as $val) {
			if (!in_array($val[8], $key_array)) {
				$key_array[$i] = $val[8];
				$temp_array[$i] = $i;
      }
			else {
				$ii++;
			}
      $i++;
    }
		$new_array = array_intersect_key($wwt, array_flip($temp_array));
		$uu = count($new_array);
		if($uu > 0) {
			$file = $wtlogfile;
			$current = $new_array;
			file_put_contents($file, $current,LOCK_EX);
		}
		clearstatcache();
		$filesizediff = $this->wtfilesize($filesizebefore - filesize($wtlogfile),2);		
			$obja = new \stdClass();
			$obja->cntdub = $ii;
			$obja->sizediff = $filesizediff;
			//$wtarr [] = $obja;
			if ($wtpara_logmessage===2) {
				if ($ii===1) $this->logger->info(sprintf('LogCleaner: %d duplicate was deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $ii, $filesizediff)); //blau
				else $this->logger->info(sprintf('LogCleaner: %d duplicates were deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $ii, $filesizediff)); //blau
			}
			//return $wtarr;
			return new DataResponse([
				//'wtarr' => $wtarr,
                'cntdub' => $ii,
				'sizediff' => $filesizediff,
            ]);
	}

	public function countDub(): DataResponse {
		$i = 0;
		$ii = 0;
		$tmp_array = array();
		$key_array = array();
		$temp_array = array();
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		foreach ($wwt as $value) {
			$tmp_array[] = explode(',"', $value);
		}
		unset($value);
		foreach($tmp_array as $val) {
			if (!in_array($val[8], $key_array)) {
				$key_array[$i] = $val[8];
				$temp_array[$i] = $i;
      }
			else {
				$ii++;
			}
      $i++;
    }
		//return $ii;
		return new DataResponse([
				//'wtarr' => $wtarr,
                'cntdub' => $ii,
            ]);
	}
	
	public function countDebug() {
		$i = 0;
		$ii = 0;
		$tmp_array = array();
		$key_array = array();
		$temp_array = array();
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		foreach ($wwt as $value) {
			$tmp_array[] = explode(',"', $value);
		}
		unset($value);
		foreach($tmp_array as $val) {
			if (!in_array($val[1], $key_array)) {
				$key_array[$i] = $val[1];
				$temp_array[$i] = $i;
      }
			else {
				$ii++;
			}
      $i++;
    }
		return $ii;
	}
	
	public function logapps(): DataResponse {
		$i = 0;
		$ii = 0;
		$iii = 0;
		$tmp_array = array();
		$key_array = array();
		$temp_array = array();
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		foreach ($wwt as $value) {
			$tmp_array[] = explode(',"', $value);
		}
		unset($value);
		foreach($tmp_array as $val) {
			$teil = substr(str_replace('url":"/', "", $val[7]), 0, -1);
			$teile = explode("/", $teil);
			if($teile[0] == 'apps') $temp_array[$i] = $teile[1];
			else $temp_array[$i] = substr(str_replace('app":"', "", $val[5]), 0, -1);			
      $i++;
    }  
    //return $temp_array;
	return new DataResponse([
                'logapps' => $temp_array,                                  
            ]);
	}

	public function emptylog(): DataResponse {
		$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		file_put_contents($wtlogfile, "",LOCK_EX);
		if ($wtpara_logmessage===2) {
			$this->logger->info('LogCleaner: log file has been emptied. This log entry can be deleted without verification.');
		}
		return new DataResponse([
            ]);
	}
}
