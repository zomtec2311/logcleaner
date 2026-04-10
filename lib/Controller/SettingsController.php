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
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
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
	}

	#[NoAdminRequired]
	#[UseSession]
	public function setSettingZeilen($who,$zeilen): DataResponse {
		$this->config->setAppValue('logcleaner', $who, $zeilen);
		return new DataResponse([
			'wert' => $zeilen,
            ]);
	}

	public function getAppValueZ(): DataResponse {
		return new DataResponse([
			'logcleaner_wt_zeilen' => (empty($this->config->getAppValue('logcleaner', 'logcleaner_wt_zeilen')))?$this->setSettingZeilen('logcleaner_wt_zeilen',5):$this->config->getAppValue('logcleaner', 'logcleaner_wt_zeilen'),
			'wtpara_settings_am' => (empty($this->config->getAppValue('logcleaner', 'wtpara_settings_am')))?$this->setSettingZeilen('wtpara_settings_am',2):$this->config->getAppValue('logcleaner', 'wtpara_settings_am'),
			'logcleaner_wt_offset' => (empty($this->config->getAppValue('logcleaner', 'logcleaner_wt_offset')))?$this->setSettingZeilen('logcleaner_wt_offset',0):$this->config->getAppValue('logcleaner', 'logcleaner_wt_offset'),
			'logcleaner_wt_characters' => (empty($this->config->getAppValue('logcleaner', 'logcleaner_wt_characters')))?$this->setSettingZeilen('logcleaner_wt_characters',500):$this->config->getAppValue('logcleaner', 'logcleaner_wt_characters'),
			'wtparam_menue' => (empty($this->config->getAppValue('logcleaner', 'wtparam_menue')))?$this->setSettingZeilen('wtparam_menue',2):$this->config->getAppValue('logcleaner', 'wtparam_menue'),
			'wtparam_logmessage' => (empty($this->config->getAppValue('logcleaner', 'wtparam_logmessage')))?$this->setSettingZeilen('wtparam_logmessage',1):$this->config->getAppValue('logcleaner', 'wtparam_logmessage'),
			'wtparam_filter' => (empty($this->config->getAppValue('logcleaner', 'wtparam_filter')))?$this->setSettingZeilen('wtparam_filter',1):$this->config->getAppValue('logcleaner', 'wtparam_filter'),
			'wtpara_cron_deldub' => (empty($this->config->getAppValue('logcleaner', 'wtpara_cron_deldub')))?$this->setSettingZeilen('wtpara_cron_deldub',1):$this->config->getAppValue('logcleaner', 'wtpara_cron_deldub'),
			'loglevel' => $this->config->getSystemValue('loglevel'),
			'wtpara_show_footer' => (empty($this->config->getAppValue('logcleaner', 'wtpara_show_footer')))?$this->setSettingZeilen('wtpara_show_footer',1):$this->config->getAppValue('logcleaner', 'wtpara_show_footer'),
			'wtpara_miniview' => (empty($this->config->getAppValue('logcleaner', 'wtpara_miniview')))?$this->setSettingZeilen('wtpara_miniview',1):$this->config->getAppValue('logcleaner', 'wtpara_miniview'),
			'wtpara_logmessage_sizewarnings' => (empty($this->config->getAppValue('logcleaner', 'wtpara_logmessage_sizewarnings')))?$this->setSettingZeilen('wtpara_logmessage_sizewarnings',2):$this->config->getAppValue('logcleaner', 'wtpara_logmessage_sizewarnings'),
			'wtpara_logrotate' => (empty($this->config->getAppValue('logcleaner', 'wtpara_logrotate')))?$this->setSettingZeilen('wtpara_logrotate',1):$this->config->getAppValue('logcleaner', 'wtpara_logrotate'),
			'wtpara_position_mini' => (empty($this->config->getAppValue('logcleaner', 'wtpara_position_mini')))?$this->setSettingZeilen('wtpara_position_mini',1):$this->config->getAppValue('logcleaner', 'wtpara_position_mini'),
            ]);
	}

	public function setLL($who): DataResponse {
		$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
		$who = intval($who);
		if (!is_int($who) || $who < 0 || $who > 4) {
				$this->logger->debug('Cannot set loglevel');
			}
			$this->config->setSystemValue('loglevel', $who);
			if ($wtpara_logmessage===2) {
				switch ($who) {
					case 0:
						$levelname = 'DEBUG';
						break;
					case 1:
						$levelname = 'INFO';
						break;
					case 2:
						$levelname = 'WARN';
						break;
					case 3:
						$levelname = 'ERROR';
						break;
					case 4:
						$levelname = 'FATAL';
					break;
					default:
				}
				$this->logger->info("LogCleaner: Set log level to $levelname($who). This log entry can be deleted without verification.");
			}
			return new DataResponse([
            ]);
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
		$wtpara_miniview = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_miniview', '1', false);
		$wtpara_logmessage_sizewarnings = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logmessage_sizewarnings', '2', false);
		$wtpara_logrotate = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logrotate', '1', false);

		if((!isset($wtpara_logrotate)) || ($wtpara_logrotate === 0)) {
			$wtpara_logrotate = 1;
			$this->helper->setAppValue('wtpara_logrotate', 1);
		}

		if((!isset($wtpara_miniview)) || ($wtpara_miniview === 0)) {
			$wtpara_miniview = 1;
			$this->helper->setAppValue('wtpara_miniview', 1);
		}

		if((!isset($wtpara_logmessage_sizewarnings)) || ($wtpara_logmessage_sizewarnings === 0)) {
			$wtpara_logmessage_sizewarnings = 2;
			$this->helper->setAppValue('logmessage_sizewarnings', 2);
		}

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
		return new DataResponse([
                'al' => $wtarr,
            ]);
	}

	public function getallfilteredlog($level): DataResponse {
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
		$wtpara_miniview = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_miniview', '1', false);
		$wtpara_logmessage_sizewarnings = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logmessage_sizewarnings', '2', false);
		$wtpara_logrotate = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logrotate', '1', false);

		if((!isset($wtpara_logrotate)) || ($wtpara_logrotate === 0)) {
			$wtpara_logrotate = 1;
			$this->helper->setAppValue('wtpara_logrotate', 1);
		}

		if((!isset($wtpara_miniview)) || ($wtpara_miniview === 0)) {
			$wtpara_miniview = 1;
			$this->helper->setAppValue('wtpara_miniview', 1);
		}
		if((!isset($wtpara_logmessage_sizewarnings)) || ($wtpara_logmessage_sizewarnings === 0)) {
			$wtpara_logmessage_sizewarnings = 2;
			$this->helper->setAppValue('logmessage_sizewarnings', 2);
		}

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
					$wta = $this->helper->myfilteredoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen + $wt_zeilen - count($wwt)-$i-1,$wt_characters,$wt_offset, $level);
					if (intval($wta->level) === intval($level)) {
						$wtarr []= $wta;
					}
					$array[$i] = $i;
				}
			 	else {
					$wta = $this->helper->myfilteredoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen-$i,$wt_characters,$wt_offset, $level);
					if (intval($wta->level) === intval($level)) {
					$wtarr []= $wta;
					}
					$array[$i] = $i;
			 	}
			}
		}
		return new DataResponse([
                'al' => $wtarr,
            ]);
	}

	public function getallfilteredapplog($key): DataResponse {
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
		$wtpara_miniview = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_miniview', '1', false);
		$wtpara_logmessage_sizewarnings = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logmessage_sizewarnings', '2', false);
		$wtpara_logrotate = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logrotate', '1', false);

		if((!isset($wtpara_logrotate)) || ($wtpara_logrotate === 0)) {
			$wtpara_logrotate = 1;
			$this->helper->setAppValue('wtpara_logrotate', 1);
		}

		if((!isset($wtpara_miniview)) || ($wtpara_miniview === 0)) {
			$wtpara_miniview = 1;
			$this->helper->setAppValue('wtpara_miniview', 1);
		}

		if((!isset($wtpara_logmessage_sizewarnings)) || ($wtpara_logmessage_sizewarnings === 0)) {
			$wtpara_logmessage_sizewarnings = 2;
			$this->helper->setAppValue('logmessage_sizewarnings', 2);
		}

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
					$wta = $this->helper->myfilteredoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen + $wt_zeilen - count($wwt)-$i-1,$wt_characters,$wt_offset, $key);
					if ($wta->appraw === $key) {
						$wtarr []= $wta;
					}
					$array[$i] = $i;
				}
			 	else {
					$wta = $this->helper->myfilteredoutputdata($a,$wtlogfilezeilen,$wtlogfilezeilen-$i,$wt_characters,$wt_offset, $key);
					if ($wta->appraw === $key) {
					$wtarr []= $wta;
					}
					$array[$i] = $i;
			 	}
			}
		}
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
				'filesizeraw' => filesize($wtlogfile),
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
				'filesizeraw' => -1,
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

		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}

		$filename = $wtlogfile;
		$lines = file($filename);
		$lineToDelete = $logid;

		$newFile = $wtlogfile.'.txt';
		$fp = fopen($newFile, 'w');

		foreach ($lines as $key => $line) {
			if ($key !== $lineToDelete) {
				fwrite($fp, $line);
			}
		}

		fclose($fp);
		unlink($filename);
		rename($newFile, $filename);
		return new DataResponse([
            ]);
	}

	public function showdetail(string $detail): DataResponse {
		if ($detail === null) {
			$detail = null;
		}
		$wt_out = "";
		$array = [];
		$wtarr =[];
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
				if (!file_exists($wtlogfile)) {
					return new DataResponse([
					 'detail' => $this->l->t('log file cannot be located'),
            ]);
				}
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		if (isset($detail)) {
			$wtdetail = $wwt[$detail];
			return new DataResponse([
					 'detail' => $wtdetail,
            ]);
		}
		return new DataResponse([
					 'detail' => '',
            ]);
	}

	public function getAll(): DataResponse {
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		$wtlogfilezeilen = count($wwt);
		$wttext = $this->l->n('%n log entry', '%n log entries', $wtlogfilezeilen);
		return new DataResponse([
                'wtlogfilezeilen' => $wtlogfilezeilen,
				'wttext' => $wttext,
            ]);
	}

	public function getcntll(): DataResponse {
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$stats = [
			0 => ['count' => 0, 'label' => $this->l->t('DEBUG'), 'color' => '#DFF0D8', 'txtcolor' => '#3C763D', 'level' => 0],
			1 => ['count' => 0, 'label' => $this->l->t('INFO'),  'color' => '#D9EDF7', 'txtcolor' => '#31708F', 'level' => 1],
			2 => ['count' => 0, 'label' => $this->l->t('WARN'),  'color' => '#fcf8e3', 'txtcolor' => '#8A6d3B', 'level' => 2],
			3 => ['count' => 0, 'label' => $this->l->t('ERROR'), 'color' => '#f2dede', 'txtcolor' => '#A94442', 'level' => 3],
			4 => ['count' => 0, 'label' => $this->l->t('FATAL'), 'color' => '#f9aaf6', 'txtcolor' => '#870482', 'level' => 4],
		];

		if (file_exists($wtlogfile)) {
			$handle = fopen($wtlogfile, "r");
			while (($line = fgets($handle)) !== false) {
				$data = json_decode($line, true);
				if (isset($data['level']) && isset($stats[$data['level']])) {
					$stats[$data['level']]['count']++;
				}
			}
			fclose($handle);
		}
		$activeFiltersCount = 0;
		foreach ($stats as $level => $data) {
			$stats[$level]['level'] = $level;

			if (isset($data['count']) && $data['count'] > 0) {
				$activeFiltersCount++;
			}
		}

		$showFilters = ($activeFiltersCount > 0);

		return new DataResponse([
			'wtcntll' => array_values($stats),
			'showFilters' => $showFilters
		]);
	}

	public function delDub(): DataResponse {
		$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
		$i = 0;
		$ii = 0;
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
		foreach($wwt as $val) {
			$json = json_decode($val);
			if (!isset($json->message)) $json->message = 'no message available';
			if (!in_array($json->message, $key_array)) {
				$key_array[$i] = $json->message;
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
			if ($wtpara_logmessage===2) {
				if ($ii===1) $this->logger->info(sprintf('LogCleaner: %d duplicate was deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $ii, $filesizediff));
				else $this->logger->info(sprintf('LogCleaner: %d duplicates were deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $ii, $filesizediff));
			}
			return new DataResponse([
                'cntdub' => $ii,
				'sizediff' => $filesizediff,
            ]);
	}

	public function delLevel(?int $level = null): DataResponse {
		if ($level === null) {
			$level = null;
		}
		if (isset($level)) {

			$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
			$i = 0;
			$ii = 0;
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
			foreach($wwt as $val) {
				$json = json_decode($val);
				if (intval($json->level) <> $level) {
					$temp_array[$i] = $val;
				}
				else {
					$ii++;
				}
				$i++;
			}
			$new_array = $temp_array;
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
			if ($wtpara_logmessage===2) {
				switch ($level) {
					case 0:
						$levelname = 'DEBUG';
						break;
					case 1:
						$levelname = 'INFO';
						break;
					case 2:
						$levelname = 'WARN';
						break;
					case 3:
						$levelname = 'ERROR';
						break;
					case 4:
						$levelname = 'FATAL';
					break;
					default:
						//code block
				}
				if ($ii===1) $this->logger->info(sprintf("LogCleaner: %d log entry was deleted from error level $levelname and %s of disk space were cleared.", $ii, $filesizediff));
				else $this->logger->info(sprintf("LogCleaner: %d log entries were deleted from error level $levelname and %s of disk space were cleared.", $ii, $filesizediff));
			}
			return new DataResponse([
                'cntlevel' => $ii,
				'sizediff' => $filesizediff,
            ]);
		}
		else {
			return new DataResponse([
                'cntlevel' => 0,
				'sizediff' => 0,
            ]);
		}
	}

	public function countDub(): DataResponse {
		$i = 0;
		$ii = 0;
		$key_array = array();
		$temp_array = array();
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		foreach($wwt as $val) {
			$json = json_decode($val);
			//if (!isset($json->message)) $json->message = 'no message available';
			if (!in_array($json->message, $key_array)) {
				$key_array[$i] = $json->message;
				$temp_array[$i] = $i;
      }
			else {
				$ii++;
			}
      $i++;
    }
    $ii = $ii;
		$wttext = $this->l->n('Delete %n duplicate', 'Delete %n duplicates', $ii);
		$wttextinfo = $this->l->t('This button will delete shown number of duplicates within the error log file');
		return new DataResponse([
                'cntdub' => $ii,
				'wttext' => $wttext,
				'wttextinfo' => $wttextinfo,
            ]);
	}

	public function logapps(): DataResponse {
		$i = 0;
		$logapps = array();
		$wtlogfile = $this->config->getSystemValue('logfile');
		if (!file_exists($wtlogfile)) {
			$wtlogfile = $this->config->getSystemValue('datadirectory') . '/nextcloud.log';
		}
		$wwt = $this->helper->wtlogtoarr($wtlogfile);
		foreach($wwt as $val) {
			$json = json_decode($val);
			if (isset($json->app))$logapps[$i] = $json->app;
			else $logapps[$i] = 'no app in context';
			$i++;
		}
		return new DataResponse([
                'logapps' => $logapps,
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

	public function delapp($app): DataResponse {
		if ($app === null) {
			$app = null;
		}
		if (isset($app)) {

			$wtpara_logmessage = (int)$this->helper->getAppValue('wtparam_logmessage');
			$i = 0;
			$ii = 0;
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
			foreach($wwt as $val) {
				$json = json_decode($val);
				if ($json->app <> $app) {
					$temp_array[$i] = $val;
				}
				else {
					$ii++;
				}
				$i++;
			}
			$new_array = $temp_array;
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
			if ($wtpara_logmessage===2) {
				if ($ii===1) $this->logger->info(sprintf("LogCleaner: %d log entry was deleted from app '$app' and %s of disk space were cleared.", $ii, $filesizediff));
				else $this->logger->info(sprintf("LogCleaner: %d log entries were deleted from app '$app' and %s of disk space were cleared.", $ii, $filesizediff));
			}
			return new DataResponse([
                'cntlevel' => $ii,
				'sizediff' => $filesizediff,
            ]);
		}
		else {
			return new DataResponse([
                'cntlevel' => 0,
				'sizediff' => 0,
            ]);
		}
	}
}
