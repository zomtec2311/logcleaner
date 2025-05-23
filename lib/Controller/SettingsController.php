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
use Psr\Log\LoggerInterface;

class SettingsController extends Controller {
	private $config;
	private $l;
	public function __construct(
		IL10N $l,
		IConfig $config,
		IRequest $request,
		private Helper $helper,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct('logcleaner', $request);
		$this->l = $l;
		$this->config = $config;
		$this->helper = $helper;
	}

	#[NoAdminRequired]
	#[UseSession]

	public function setSettingZeilen($who,$zeilen) {
		$this->config->setAppValue('logcleaner', $who, $zeilen);
		return;
	}

	public function getAppValueZ($who) {
		return $this->config->getAppValue('logcleaner', $who);
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
		$wt_zeilen = (int)$this->helper->getAppValue("logcleaner_wt_zeilen");
		$wt_offset = (int)$this->helper->getAppValue("logcleaner_wt_offset");
		$wt_art = (int)$this->helper->getAppValue("logcleaner_wt_art");
		$wt_characters = (int)$this->helper->getAppValue("logcleaner_wt_characters");
		$wtpara_menue = (int)$this->helper->getAppValue('wtparam_menue');
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

	public function getalllog(?int $logid = null) {
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
		$wt_zeilen = (int)$this->helper->getAppValue("logcleaner_wt_zeilen");
		$wt_offset = (int)$this->helper->getAppValue("logcleaner_wt_offset");
		$wt_art = (int)$this->helper->getAppValue("logcleaner_wt_art");
		$wt_characters = (int)$this->helper->getAppValue("logcleaner_wt_characters");
		$wtpara_menue = (int)$this->helper->getAppValue('wtparam_menue');
		$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
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
		  return $wtarr;
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
		return $wtarr;
	}

	public function logfileandsize() {
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		if (file_exists($wtlogfile)) {
			$teile = explode("/", $wtlogfile);
			$obja = new \stdClass();
			$obja->file = $wtlogfile;
			$obja->filearr = $teile;
			$obja->filesize = $this->show_filesize($wtlogfile,2);
			$wtarr [] = $obja;
			return $wtarr;
		}
		else {
			$obja = new \stdClass();
			$obja->file = '';
			$obja->filearr = [];
			$obja->filesize = '';
			$wtarr [] = $obja;
			return $wtarr;
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

	public function dellog(string $logid) {
		if ($logid === null) {
			$logid = null;
		}
		$logid = intval($logid);
		return $this->getlog($logid);
	}

	public function getAll() {
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		$wtlogfilezeilen = count($wwt);
		return $wtlogfilezeilen;
	}

	public function delDub() {
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
			$wtarr [] = $obja;
			if ($wtpara_logmessage===2) {
				if ($ii===1) $this->logger->info(sprintf('LogCleaner: %d duplicate was deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $ii, $filesizediff)); //blau
				else $this->logger->info(sprintf('LogCleaner: %d duplicates were deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $ii, $filesizediff)); //blau
			}
			return $wtarr;
	}

	public function countDub() {
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
		return $ii;
	}

	public function emptylog() {
		$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		file_put_contents($wtlogfile, "",LOCK_EX);
		if ($wtpara_logmessage===2) {
			$this->logger->info('LogCleaner: log file has been emptied. This log entry can be deleted without verification.');
		}
		return;
	}
}
