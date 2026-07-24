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

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCA\LogCleaner\Log\LogService;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use Psr\Log\LoggerInterface;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCA\LogCleaner\Service\LogNotificationService;

class LogsController extends Controller {
    private $groupManager;

    public function __construct(string $AppName, IRequest $request, private LogService $logService, private LogNotificationService $logNotificationService, IGroupManager $groupManager, private readonly LoggerInterface $logger, private IConfig $config, private IAppConfig $appConfig, private Helper $helper, private IL10N $l,) {
        parent::__construct($AppName, $request);
        $this->helper = $helper;
        $this->groupManager = $groupManager;
    }

    #[NoCSRFRequired]
    public function list(): JSONResponse {
        $level = $this->request->getParam('level', null);
        $app = $this->request->getParam('app', null);
        $message = $this->request->getParam('message', null);
        $limit = (int) $this->request->getParam('limit', 5);
        $offset = (int) $this->request->getParam('offset', 2);
        $wtpara_logmessage_sizewarnings = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logmessage_sizewarnings', '2');

        $filters = [];
        if ($level !== null) $filters['level'] = (int)$level;
        if ($app !== null) $filters['app'] = $app;
        if ($message !== null) $filters['message'] = $message;

        $snapshot = $this->logService->getSnapshot($filters, $limit, $offset, ['reqId', 'level', 'time', 'remoteAddr', 'user', 'app', 'method', 'url', 'message']);
        $timestamp = time();
        $datum = date("d. M Y - H:i:s", $timestamp);
        $filePath = $this->logService->getLogFile();
        $dummy = [];

        $aksize = filesize($filePath);
        if ($aksize < 52428800) {
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_95','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_90','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_85','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_80','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_75','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_50','0');
        }

        if ($aksize > 104857600) {
            $line = '{"id": 1,"formattedtimewithoffset": "'.$datum.'","time": "","level": 5,"remoteAddr": "","message": "<span style=\"font-size: 1.5em;\"><br><br><br><h1 style=\"font-size: 1.1em;\">Oops,</h1> the size of your log file is over <strong>100 MB.</strong><br>This can cause problems with the performance of your system if you want to work with LogCleaner.<br>You should urgently check why your log file is not rotating.<br>In order for LogCleaner to open properly, you should delete, empty or rename the file <strong>'.$filePath.'</strong></span>.","user": "","app": "", "url": "","method": "" }';
            $trim = rtrim($line, "\r\n");
            $parsed = json_decode($trim, true);
            $dummy[0] = $parsed;
            $snapshot[1] = $dummy;
        }
        if (($aksize > 99614720) && ($aksize < (99614720 + 1048576))) {
            $para = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logsize_95', '0');
            if (!isset($para) || ($para === 0)){
                $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_95','1');
                if ($wtpara_logmessage_sizewarnings === 2) $this->logger->error("LogCleaner: Oops, the size of your log file is over ".$this->wtfilesize(99614720, 0).". This can cause problems with the performance of your system if you want to work with LogCleaner. You should urgently check why your log file is not rotating. In order for LogCleaner to open properly, you should delete, empty or rename the file $filePath.");
            }

        }
        if (($aksize > 94371840) && ($aksize < (94371840 + 1048576))) {
            $para = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logsize_90', '0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_95','0');
            if (!isset($para) || ($para === 0)){
                $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_90','1');
                if ($wtpara_logmessage_sizewarnings === 2) $this->logger->error("LogCleaner: Oops, the size of your log file is over ".$this->wtfilesize(94371840, 0).". This can cause problems with the performance of your system if you want to work with LogCleaner. You should urgently check why your log file is not rotating. In order for LogCleaner to open properly, you should delete, empty or rename the file $filePath.");
            }

        }
        if (($aksize > 89128960) && ($aksize < (89128960 + 1048576))) {
            $para = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logsize_85', '0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_95','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_90','0');
            if (!isset($para) || ($para === 0)){
                $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_85','1');
                if ($wtpara_logmessage_sizewarnings === 2) $this->logger->error("LogCleaner: Oops, the size of your log file is over ".$this->wtfilesize(89128960, 0).". This can cause problems with the performance of your system if you want to work with LogCleaner. You should urgently check your log file. In order for LogCleaner to open properly, you should delete, empty or rename the file $filePath.");
            }

        }
        if (($aksize > 83886080) && ($aksize < (83886080 + 1048576))) {
            $para = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logsize_80', '0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_95','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_90','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_85','0');
            if (!isset($para) || ($para === 0)){
                $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_80','1');
                if ($wtpara_logmessage_sizewarnings === 2) $this->logger->error("LogCleaner: Oops, the size of your log file is over ".$this->wtfilesize(83886080, 0).". You should urgently check your log file. In order for LogCleaner to open properly, you should delete, empty or rename the file $filePath.");
            }

        }
        if (($aksize > 78643200) && ($aksize < (78643200 + 1048576))) {
            $para = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logsize_75', '0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_95','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_90','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_85','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_80','0');
            if (!isset($para) || ($para === 0)){
                $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_75','1');
                if ($wtpara_logmessage_sizewarnings === 2) $this->logger->error("LogCleaner: Oops, the size of your log file is over ".$this->wtfilesize(78643200, 0).". You should check your log file in order for LogCleaner to open properly.");
            }
        }
        if (($aksize > 52428800) && ($aksize < (52428800 + 1048576))) {
            $para = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logsize_50', '0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_95','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_90','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_85','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_80','0');
            $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_75','0');
            if (!isset($para) || ($para === 0)){
                $this->appConfig->setValueString('logcleaner', 'wtparam_logsize_50','1');
                if ($wtpara_logmessage_sizewarnings === 2) $this->logger->info("LogCleaner: Oops, the size of your log file is over ".$this->wtfilesize(52428800, 0).". You should check your log file and cleanup.");
            }
        }
        if (isset($snapshot[0])) {
            if (!empty($snapshot[2])) {
                return new JSONResponse(['ok' => true, 'data' => $snapshot[1], 'total' => $snapshot[0], 'corrupt' => $snapshot[2]]);
            }
        return new JSONResponse(['ok' => true, 'data' => $snapshot[1], 'total' => $snapshot[0]]);
        }
        else return new JSONResponse(['ok' => true, '', 'total' => 0]);
    }

    #[NoCSRFRequired]
    public function listlevel(): JSONResponse {
        $level = $this->request->getParam('level', null);
        $app = $this->request->getParam('app', null);
        $message = $this->request->getParam('message', null);
        $limit = (int) $this->request->getParam('limit', 5);
        $offset = (int) $this->request->getParam('offset', 2);
        $myok = true;
        $filters = [];
        if ($level !== null) $filters['level'] = (int)$level;
        if ($app !== null) $filters['app'] = $app;
        if ($message !== null) $filters['message'] = $message;

        $snapshot = $this->logService->getSnapshot($filters, $limit, $offset, ['reqId', 'level', 'time', 'remoteAddr', 'user', 'app', 'method', 'url', 'message']);
        if (empty($snapshot[0])) {
            $myok = false;
            $snapshot[0] = 0;
            $snapshot[1] = 0;
        }
        if (!empty($snapshot[2])) {
            return new JSONResponse(['ok' => $myok, 'data' => $snapshot[1], 'total' => $snapshot[0], 'all' => $this->logService->count(), 'corrupt' => $snapshot[2]]);
        }
        return new JSONResponse(['ok' => $myok, 'data' => $snapshot[1], 'total' => $snapshot[0], 'all' => $this->logService->count()]);
    }

    #[NoCSRFRequired]
    public function listapp(): JSONResponse {
        $level = $this->request->getParam('level', null);
        $app = $this->request->getParam('app', null);
        $message = $this->request->getParam('message', null);
        $limit = (int) $this->request->getParam('limit', 5);
        $offset = (int) $this->request->getParam('offset', 2);
        $myok = true;
        $filters = [];
        if ($level !== null) $filters['level'] = (int)$level;
        if ($app !== null) $filters['app'] = $app;
        if ($message !== null) $filters['message'] = $message;

        $snapshot = $this->logService->getSnapshot($filters, $limit, $offset, ['reqId', 'level', 'time', 'remoteAddr', 'user', 'app', 'method', 'url', 'message']);
        if (empty($snapshot[0])) {
            $myok = false;
            $snapshot[0] = 0;
            $snapshot[1] = 0;
        }
        if (!empty($snapshot[2])) {
            return new JSONResponse(['ok' => $myok, 'data' => $snapshot[1], 'total' => $snapshot[0], 'all' => $this->logService->count(), 'corrupt' => $snapshot[2]]);
        }
        return new JSONResponse(['ok' => $myok, 'data' => $snapshot[1], 'total' => $snapshot[0], 'all' => $this->logService->count()]);
    }

    public function removeDub(?int $anzahl = 0): DataResponse {
        $inputFile = $this->logService->getLogFile();
		$outputLog  = $inputFile.'.cleaned.log';
        if ($anzahl === 0) {
            $this->analyse();
            $outputJson = $inputFile.'analysis.json';
            $json = file_get_contents($outputJson);
            $analysis_array = json_decode($json, true);
            $anzahl = $analysis_array['summary']['removed_duplicates'];
        }
		$wtpara_logmessage = (int)$this->appConfig->getValueString('logcleaner', 'wtparam_logmessage', '1');

        $filesizeoriginal = filesize($inputFile);

        if (file_exists($outputLog)) {
            $filesizecleaned = filesize($outputLog); // <-- Erst hier messen
            unlink($inputFile);
            rename($outputLog, $inputFile);
        } else {
            $filesizecleaned = $filesizeoriginal; // Keine Änderung, wenn keine Datei da ist
        }

        $filesizediff = $this->wtfilesize($filesizeoriginal - $filesizecleaned,2);

        if ($anzahl === 0) {
            return new DataResponse([
                'cntdub' => $anzahl,
                'sizediff' => $filesizediff,
            ]);
        }

        if ($wtpara_logmessage===2) {
            if ($anzahl===1) $this->logger->info(sprintf('LogCleaner: %d duplicate was deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $anzahl, $filesizediff));
            else $this->logger->info(sprintf('LogCleaner: %d duplicates were deleted and %s of disk space were cleared. This log entry can be deleted without verification.', $anzahl, $filesizediff));
        }
        return new DataResponse([
            'cntdub' => $anzahl,
            'sizediff' => $filesizediff,
        ]);
	}

	public function analyse(): DataResponse {
        $wtpara_logrotate = (int)$this->appConfig->getValueString('logcleaner', 'wtpara_logrotate', '1');
        if ($wtpara_logrotate === 2) {
            if (!$this->config->getSystemValue('log_rotate_size')) { $this->config->setSystemValue('log_rotate_size', 104857600); }
            if (!$this->config->getSystemValue('log_max_history')) { $this->config->setSystemValue('log_max_history', 5); }
        }

        try {
            $inputFile = $this->logService->getLogFile();
            $outputLog  = $inputFile.'.cleaned.log';
            $outputJson = $inputFile.'analysis.json';

            $in = fopen($inputFile, 'r');
            $out = fopen($outputLog, 'w');

            if (!$in || !$out) {
                throw new \Exception("LogCleaner error: log file cannot be analized");
            }

            $stats = [
                'levels' => [
                    0 => ['count' => 0, 'lines' => [], 'label' => $this->l->t('DEBUG'), 'color' => '#DFF0D8', 'txtcolor' => '#3C763D', 'level' => 0],
                    1 => ['count' => 0, 'lines' => [], 'label' => $this->l->t('INFO'),  'color' => '#D9EDF7', 'txtcolor' => '#31708F', 'level' => 1],
                    2 => ['count' => 0, 'lines' => [], 'label' => $this->l->t('WARN'),  'color' => '#fcf8e3', 'txtcolor' => '#8A6d3B', 'level' => 2],
                    3 => ['count' => 0, 'lines' => [], 'label' => $this->l->t('ERROR'), 'color' => '#f2dede', 'txtcolor' => '#A94442', 'level' => 3],
                    4 => ['count' => 0, 'lines' => [], 'label' => $this->l->t('FATAL'), 'color' => '#f9aaf6', 'txtcolor' => '#870482', 'level' => 4]
                ],
                'apps' => [],
                'messages' => [],

            ];

            $seenHashes = [];
            $appseenHashes = [];
            $currentLine = 0;
            $keptLineCount = 0;
            $wtarr = [];
            $linelength = [];

            while (($line = fgets($in)) !== false) {
                $currentLine++;
                $data = json_decode($line, true);

                if (!$data) {
                    fwrite($out, $line);
                    continue;
                }
                switch ($data['level']) {
                    case 0:
                        $levelcolor = '#DFF0D8';
                        break;
                    case 1:
                        $levelcolor = '#D9EDF7';
                        break;
                    case 2:
                        $levelcolor = '#fcf8e3';
                        break;
                    case 3:
                        $levelcolor = '#f2dede';
                        break;
                    case 4:
                        $levelcolor = '#f9aaf6';
                    break;
                    default:
                }
                $wtarr[] = $levelcolor;
                $linelength[] = $currentLine .  ' - ' . $data['level'] . ' - ' . strlen($line);

                $msg = $data['message'] ?? 'No Message';
                $msgHash = md5($msg);

                $lvl = $data['level'] ?? -1;
                if (isset($stats['levels'][$lvl])) {
                    $stats['levels'][$lvl]['count']++;
                    $stats['levels'][$lvl]['lines'][] = $currentLine;
                }
                if (!isset($appseenHashes[$data['app']])) {
                   if (!isset($stats['apps'][$data['app']]['count'])) { $stats['apps'][$data['app']]['count'] = 1;}
                   else { $stats['apps'][$data['app']]['count']++;}
                    $stats['apps'][$data['app']]['lines'][] = $currentLine;
                }
                if (!isset($seenHashes[$msgHash])) {
                    fwrite($out, $line);
                    $keptLineCount++;
                    $seenHashes[$msgHash] = true;
                    $stats['messages'][$msg] = [
                        'count' => 1,
                        'first_seen_original_line' => $currentLine,
                        'new_line' => $keptLineCount
                    ];
                } else {
                    $stats['messages'][$msg]['count']++;
                }
            }

            fclose($in);
            fclose($out);

            uasort($stats['messages'], function($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            $vueData = [
                'levels' => $stats['levels'],
                'apps' => $stats['apps'],
                'top_messages' => array_slice($stats['messages'], 0, 100, true),
                'summary' => [
                    'original_lines' => $currentLine,
                    'unique_lines' => $keptLineCount,
                    'removed_duplicates' => $currentLine - $keptLineCount
                ]
            ];

            file_put_contents($outputJson, json_encode($vueData, JSON_PRETTY_PRINT));

            $ii = $vueData['summary']['removed_duplicates'];
            $wttext = $this->l->n('Delete %n duplicate', 'Delete %n duplicates', $ii);
            $wttextinfo = $this->l->t('This button will delete shown number of duplicates within the error log file');

            $wtarr = array_reverse($wtarr);
            $linelength = array_reverse($linelength);
            switch ($this->config->getSystemValue('loglevel')) {
                case 0:
                    $setloglevel = $this->l->t('debug');
                    $setloglevelcolor = '#DFF0D8';
                    $setlogleveltxtcolor = '#3C763D';
                    break;
                case 1:
                    $setloglevel = $this->l->t('info');
                    $setloglevelcolor = '#D9EDF7';
                    $setlogleveltxtcolor = '#31708F';
                    break;
                case 2:
                    $setloglevel = $this->l->t('warning');
                    $setloglevelcolor = '#FCF8E3';
                    $setlogleveltxtcolor = '#8A6d3B';
                    break;
                case 3:
                    $setloglevel = $this->l->t('error');
                    $setloglevelcolor = '#F2DEDE';
                    $setlogleveltxtcolor = '#A94442';
                    break;
                case 4:
                    $setloglevel = $this->l->t('fatal');
                    $setloglevelcolor = '#F9AAF6';
                    $setlogleveltxtcolor = '#870482';
                    break;
                default:
                    $setloglevel = $this->l->t('unknown');
                    $setloglevelcolor = '#fff';
                    $setlogleveltxtcolor = '#000';
            }
            return new DataResponse([
                'status' => 'success',
                'data' => $vueData,
                'original_size' => $this->show_filesize($inputFile, 2),
                'new_size' => $this->show_filesize($outputLog, 2),
                'cntdub' => $ii,
				'wttext' => $wttext,
				'wttextinfo' => $wttextinfo,
                'showFilters' => (($stats['levels'][0]['count'] + $stats['levels'][1]['count'] + $stats['levels'][2]['count'] + $stats['levels'][3]['count'] + $stats['levels'][4]['count']) > 0),
                'wtarr' => $wtarr,
                'linelength' => $linelength,
                'setloglevel' => $setloglevel,
                'setloglevelcolor' => $setloglevelcolor,
                'setlogleveltxtcolor' => $setlogleveltxtcolor,
                ]);
        } catch (Exception $e) {
            http_response_code(400);
            return new DataResponse([
                'status' => 'error',
            ]);
        }
    }

    public function dellines(?int $level, ?array $dellines): DataResponse {
        $file = $this->logService->getLogFile();
        $filesizebefore = filesize($file);
        $deleted = count($dellines);
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
        }
        $withorwithout = $this->logService->smartDeleteLines($file, $dellines);
        $filesizeafter = filesize($file);
        $filesizediff = $this->wtfilesize(($filesizebefore - $filesizeafter),2);
        $output = ($deleted > 1) ? "LogCleaner $withorwithout: For level $levelname $deleted lines have been deleted. $filesizediff of storage space cleared in the file $file" : "LogCleaner $withorwithout: For level $levelname $deleted line has been deleted. $filesizediff of storage space cleared in the file $file";
        if ( $this->appConfig->getValueString('logcleaner', 'wtparam_logmessage', '2') === '2' ) $this->logger->info($output);
        return new DataResponse([
            'status' => 'success',
            'deleted_count' => $deleted,
            'size_diff' => $filesizediff
        ]);
    }

    public function dellinesapp(?string $app, ?array $dellines): DataResponse {
        $file = $this->logService->getLogFile();
        $filesizebefore = filesize($file);
        $deleted = count($dellines);
        $withorwithout = $this->logService->smartDeleteLines($file, $dellines);
        $filesizeafter = filesize($file);
        $filesizediff = $this->wtfilesize(($filesizebefore - $filesizeafter),2);
        $output = ($deleted > 1) ? "LogCleaner $withorwithout: For the app '$app' $deleted lines have been deleted. $filesizediff of storage space cleared in the file $file" : "LogCleaner $withorwithout: For the app '$app' $deleted line has been deleted. $filesizediff of storage space cleared in the file $file";
        if ( $this->appConfig->getValueString('logcleaner', 'wtparam_logmessage', '2') === '2' ) $this->logger->info($output);
        return new DataResponse([
            'status' => 'success',
        ]);
    }

    public function removelog(string $logid): DataResponse {
		if ($logid === null) {
			$logid = null;
		}
		$logidarray = array();
        array_push($logidarray, intval($logid) + 1);
        $file = $this->logService->getLogFile();
        $this->logService->smartDeleteLines($file, $logidarray);
        return new DataResponse([
            'status' => 'success',
        ]);
	}

    public function showdetail(string $detail): DataResponse {
        $detail = (int) $detail + 1;
        $file = $this->logService->getLogFile();
        return new DataResponse([
					 'detail' => $this->logService->smartDetail($file, $detail),
            ]);
	}

	public function getAll(): DataResponse {
		$wtlogfile = $this->logService->getLogFile();

        if (file_exists($wtlogfile)) {
            if ($this->logService->isExecAvailable()) {
                $wtlogfilezeilen = intval(exec("wc -l " . $wtlogfile));
            }
            else {
                $wtlogfilezeilen = count($this->helper->wtlogtoarr($wtlogfile));
            }
        } else {
            $wtlogfilezeilen = 0;
        }
		$wttext = $this->l->n('%n log entry', '%n log entries', $wtlogfilezeilen);
		return new DataResponse([
                'wtlogfilezeilen' => $wtlogfilezeilen,
				'wttext' => $wttext,
            ]);
	}

	public function getAdmins(): DataResponse {
    $admins = $this->groupManager->get('admin')->getUsers();
    $admins_email = [];

    foreach ($admins as $admin) {
        $email = $admin->getEMailAddress();
        $admins_email[$email] = [
            'name'  => $admin->getUID(),
            'displayname'  => $admin->getDisplayName(),
            'email' => $email,
        ];
    }
    if (count($admins) != count($admins_email)) $this->logger->info("LogCleaner: Due to identical email addresses, admins are hidden in the LogCleaner settings. This information no longer appears as soon as different email addresses are assigned to administrators.");

    return new DataResponse([
        'admins' => array_values($admins_email),
    ]);
}

public function getNotiAdmins(): DataResponse {
    $admins = $this->groupManager->get('admin')->getUsers();

    foreach ($admins as $admin) {
        $admins_email[] = [
            'name'  => $admin->getUID(),
            'displayname'  => $admin->getDisplayName(),
        ];
    }

    return new DataResponse([
        'notiadmins' => array_values($admins_email),
    ]);
}


	public function testLogEmail() {
        $this->logNotificationService->sendTestEmail();
    }

    public function testLogNotification() {
        $this->logNotificationService->sendTestNotification();
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
}
