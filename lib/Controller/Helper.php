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

use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\Server;
use OCP\IRequest;

class Helper
{
    private IConfig $config;
    private $appName;
    private $l;
    private LogService $logService;
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/')]

   public function __construct(IConfig $config, private IAppConfig $appConfig, IL10N $l, $appName){
        $this->config = $config;
        $this->l = $l;
        $this->appName = $appName;
    }
/*
    public function getAppValue($key) {
        return $this->config->getAppValue($this->appName, $key);
    }

    public function setAppValue($key, $value) {
        return $this->config->setAppValue($this->appName, $key, $value);
    }
*/

    public function wtlogtoarr(?string $wtlog)
    {
        if ($wtlog === null) {
            $wtlog = "";
            return;
        }
        $inputFile  = $wtlog;
        $outputFile = $wtlog.'.txt';

       $format = $this->config->getSystemValue('logdateformat', \DateTimeInterface::ATOM);
		$logTimeZone = $this->config->getSystemValue('logtimezone', 'UTC');
		try {
			$timezone = new \DateTimeZone($logTimeZone);
		} catch (\Exception $e) {
			$timezone = new \DateTimeZone('UTC');
		}
		$time = \DateTime::createFromFormat('U.u', number_format(microtime(true), 4, '.', ''));
		if ($time === false) {
			$time = new \DateTime('now', $timezone);
		} else {
			$time->setTimezone($timezone);
		}
		$request = Server::get(IRequest::class);
		$reqId = $request->getId();
		$remoteAddr = $request->getRemoteAddress();
		$time = $time->format($format);
		$url = ($request->getRequestUri() !== '') ? $request->getRequestUri() : '--';
		$method = $request->getMethod();
		if ($this->config->getSystemValue('installed', false)) {
			$user = \OC_User::getUser() ?: '--';
		} else {
			$user = '--';
		}
		$userAgent = $request->getHeader('User-Agent');
		if ($userAgent === '') {
			$userAgent = '--';
		}
		$version = $this->config->getSystemValue('version', '');
		$scriptName = $request->getScriptName();
        
        $replaceWith = '{"reqId": "'.$reqId.'","level": 2,"time": "'.$time.'","remoteAddr": "'.$remoteAddr.'","user": "'.$user.'","app": "logcleaner","method": "'.$method.'","url": "'.$url.'","scriptName": "'.$scriptName.'","message": "Empty line detected within your logfile. LogCleaner has fixed this error.  This log entry can be deleted without verification.","userAgent": "'.$userAgent.'","version": "'.$version.'"}';
        $replaceWithThis = '{"reqId": "'.$reqId.'","level": 2,"time": "'.$time.'","remoteAddr": "'.$remoteAddr.'","user": "'.$user.'","app": "logcleaner","method": "'.$method.'","url": "'.$url.'","scriptName": "'.$scriptName.'","message": "Corrupted line detected within your logfile. LogCleaner has fixed this error.  This log entry can be deleted without verification.","userAgent": "'.$userAgent.'","version": "'.$version.'"}';

        $in  = fopen($inputFile, 'r');
        $out = fopen($outputFile, 'w');

        while (($line = fgets($in)) !== false) {
            if (trim($line) === '') {
                fwrite($out, $replaceWith . PHP_EOL);
            }

            elseif (!str_contains(trim($line), 'reqId')) {
                fwrite($out, $replaceWithThis . PHP_EOL);
              }
            else {
                fwrite($out, $line);
            }
        }
        fclose($in);
        fclose($out);
        rename($outputFile, $inputFile);
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

    public function myoutputdata($wtlog,$wtall,$wtlogfilezeilen,$wt_characters,$wt_offset) {
        $wtarr =[];
        $obja = new \stdClass();
        if ($wtall === 0) {
          $obja->all = 0;
          $obja->zeit = '';
          $obja->ip = '';
          $obja->user = '';
          $obja->app = '';
          $obja->method = '';
          $obja->grund = $this->l->t('no log entries available');
          return $obja;
        }
        $trenn = '*';
        $json = json_decode($wtlog);
        $obja->all = $wtall;
        $wttimelog = strtotime($json->time) + 3600*$wt_offset;
        $obja->zeit = $this->l->t('Time') . " : " . $this->l->l('date', $wttimelog) . ' - ' . $this->l->l('time', $wttimelog)  . $trenn;
        $obja->ip = $this->l->t('IP') . " : ". $json->remoteAddr . $trenn;
        $obja->user = $this->l->t('User') . " : ".$json->user . $trenn;
        $obja->app = $this->l->t('App') . " : ".$json->app . $trenn;
        $obja->method = $this->l->t('Method') . " : ".$json->method . $trenn;
        $obja->url = $this->l->t('URL') . " : ".$json->url . $trenn;
        $obja->grund = $this->l->t('Reason') . " : ".substr($json->message, 0, $wt_characters);
        switch ($json->level) {
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
        $obja->id = $wtlogfilezeilen;
        return $obja;
    }
      
    public function myfilteredoutputdata($wtlog,$wtall,$wtlogfilezeilen,$wt_characters,$wt_offset, $level) {
        $wtarr =[];
        $obja = new \stdClass();
        if ($wtall === 0) {
          $obja->all = 0;
          $obja->zeit = '';
          $obja->ip = '';
          $obja->user = '';
          $obja->app = '';
          $obja->method = '';
          $obja->grund = $this->l->t('no log entries available');
          return $obja;
        }
        $trenn = '*';
        $json = json_decode($wtlog);
        $obja->all = $wtall;
        $wttimelog = strtotime($json->time) + 3600*$wt_offset;
        $obja->zeit = $this->l->t('Time') . " : " . $this->l->l('date', $wttimelog) . ' - ' . $this->l->l('time', $wttimelog)  . $trenn;
        $obja->ip = $this->l->t('IP') . " : ". $json->remoteAddr . $trenn;
        $obja->user = $this->l->t('User') . " : ".$json->user . $trenn;
        $obja->app = $this->l->t('App') . " : ".$json->app . $trenn;
        $obja->appraw = $json->app;
        $obja->method = $this->l->t('Method') . " : ".$json->method . $trenn;
        $obja->url = $this->l->t('URL') . " : ".$json->url . $trenn;
        $obja->grund = $this->l->t('Reason') . " : ".substr($json->message, 0, $wt_characters);
        $obja->level = $json->level;
        switch ($json->level) {
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
        $obja->id = $wtlogfilezeilen;
        return $obja;
    }

	public function corruptline(?int $zeile, ?string $wtlog): array {
      $format = $this->config->getSystemValue('logdateformat', \DateTimeInterface::ATOM);
      $logTimeZone = $this->config->getSystemValue('logtimezone', 'UTC');
      try {
          $timezone = new \DateTimeZone($logTimeZone);
      } catch (\Exception $e) {
          $timezone = new \DateTimeZone('UTC');
      }

      $time = \DateTime::createFromFormat('U.u', number_format(microtime(true), 4, '.', ''));
      if ($time === false) {
          $time = new \DateTime('now', $timezone);
      } else {
          $time->setTimezone($timezone);
      }

      $request = Server::get(IRequest::class);
      $reqId = $request->getId();
      $remoteAddr = $request->getRemoteAddress();
      $time = $time->format($format);
      $url = ($request->getRequestUri() !== '') ? $request->getRequestUri() : '--';
      $method = $request->getMethod();
      $userAgent = $request->getHeader('User-Agent') ?: '--';
      $version = $this->config->getSystemValue('version', '');
      $scriptName = $request->getScriptName();

      $file = $wtlog;
      $fragment = '';
      if ($this->isExecAvailable()) {
          $fragment = exec("sed -n '{$zeile}p' " . escapeshellarg($file));
      } else {
          try {
              $fileObj = new \SplFileObject($file);
              $fileObj->seek($zeile - 1);
              $fragment = $fileObj->current();
          } catch (\Exception $e) {
              $fragment = 'could not read line';
          }
      }

      $fragment = str_replace(['{', '}'], '', (string)$fragment);
      $fragment = preg_replace("/[\"'{}\x00-\x1F\x7F]/u", '', $fragment);
      $fragment = trim($fragment);
      if ($fragment === '') $fragment = 'empty';

      $message = "Corrupt line detected within your logfile. LogCleaner has fixed this error. This log entry can be deleted without verification. Corrupt line was: $fragment";

      $replaceWith = json_encode([
          'reqId' => $reqId,
          'level' => 3,
          'time' => $time,
          'remoteAddr' => $remoteAddr,
          'user' => '',
          'app' => 'logcleaner',
          'method' => $method,
          'url' => $url,
          'scriptName' => $scriptName,
          'message' => $message,
          'userAgent' => $userAgent,
          'version' => $version
      ]);

      if ($this->isExecAvailable()) {
          $tmpNew = tempnam(sys_get_temp_dir(), 'newline_');
          file_put_contents($tmpNew, $replaceWith . PHP_EOL);

          $cmd = sprintf(
              "awk -v n=%d -v f=%s 'NR==n{while((getline line < f)>0){print line}; close(f); next} {print}' %s > %s && mv %s %s",
              $zeile,
              escapeshellarg($tmpNew),
              escapeshellarg($file),
              escapeshellarg($file . '.tmp'),
              escapeshellarg($file . '.tmp'),
              escapeshellarg($file)
          );
          exec($cmd . ' 2>&1');
          @unlink($tmpNew);
      } else {
          $tempFile = $file . '.tmp';
          $handleIn = fopen($file, 'r');
          $handleOut = fopen($tempFile, 'w');

          if ($handleIn && $handleOut) {
              $currentLine = 0;
              while (($line = fgets($handleIn)) !== false) {
                  $currentLine++;
                  if ($currentLine === $zeile) {
                      fwrite($handleOut, $replaceWith . PHP_EOL);
                  } else {
                      fwrite($handleOut, $line);
                  }
              }
              fclose($handleIn);
              fclose($handleOut);
              rename($tempFile, $file);
          }
      }

      return [
          'time' => $time,
          'zeile' => $zeile,
          'log' => $wtlog,
          'logger' => $fragment,
      ];
  }

  public function isExecAvailable() {
        if (!function_exists('exec')) {
            return false;
        }
        $disabled = explode(',', ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        if (in_array('exec', $disabled)) {
            return false;
        }
        try {
            @exec('echo 1', $output, $returnVar);
            return ($returnVar === 0);
        } catch (Exception $e) {
            return false;
        }
    }
}
