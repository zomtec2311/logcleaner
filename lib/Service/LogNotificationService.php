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
namespace OCA\LogCleaner\Service;

use OCP\IConfig;
use OCP\IAppConfig;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\AppFramework\Utility\ITimeFactory;
use OCA\LogCleaner\Log\LogService;
use Psr\Log\LoggerInterface;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCA\LogCleaner\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\IDb;
use OCP\ILogger;
use OCP\Notification\INotificationManager;
use OCP\App\IAppManager;

class LogNotificationService {

    private $config;
    private $mailer;
    private $timeFactory;

    public function __construct(
        IConfig $config,
        IMailer $mailer,
        ITimeFactory $timeFactory,
        private IAppConfig $appConfig,
        private LogService $logService,
        private readonly LoggerInterface $logger,
        private IFactory $l10nFactory,
        private IUserManager $userManager,
        protected IManager $notificationManager,
        private IURLGenerator $url,
        private IAppManager $appManager,
    ) {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->timeFactory = $timeFactory;
        $this->userManager = $userManager;
    }

    public function sendSummaryEmail() {
        $lastSent = $this->appConfig->getValueInt('logcleaner', 'last_email_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_offset', '0');
        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');
        $stats = $this->getLogStats($lastSent, $minLevel);
        if (array_sum($stats['counts']) === 0) {
            return;
        }
        $adminEmail = $this->appConfig->getValueString('logcleaner', 'admin_mail', '');
        $adminName = $this->appConfig->getValueString('logcleaner', 'admin_mail_name', '');
        $user = $this->userManager->get($adminName);
        if ($user !== null) {
            $lang = $this->l10nFactory->getUserLanguage($user);
            $adminDisplayname = $user->getDisplayName();
        } else {
            $this->logger->error("LogCleaner: No administrator selected to send a log report or the selected administrator does not have a valid email address (adminname '$adminName' not known)");
            return;
        }
        $l = $this->l10nFactory->get('logcleaner', $lang);
        if (empty($adminEmail)) {
            $this->logger->error("LogCleaner: No administrator selected to send a log report or the selected administrator does not have a valid email address");
            return;
        }
        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));
        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));
        $message = $this->mailer->createMessage();
        $message->setTo([$adminEmail]);
         switch ($this->appConfig->getValueString('logcleaner', 'email_interval', 'daily')) {
			case 'daily':
				$subject = $l->t('Daily Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
                break;
			case 'weekly':
                $subject = $l->t('Weekly Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
                break;
			case 'monthly':
                $subject = $l->t('Monthly Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
                break;
			default:
				$subject = $l->t('Daily Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
		}
        $body = "<center>" . $l->t('Hello %1$s,', ['<strong>'.$adminDisplayname.'</strong>']) . "<br><br>";
        $body .= $l->t('in the period from %1$s to %2$s new log entries were registered.', ['<strong>'.$fromDate.'</strong>', '<strong>'.$toDate.'</strong>']) . "<br><br>";
        $body .= $l->t('Distribution by log level:') . "<br><br>";
        $body .= '<table style="border-collapse:collapse;border-spacing:0;">';
        $body .= '<tbody>';
        $body .= '<tr style="height: 1px;"><td style="background: black;" colspan="3"></td></tr>';
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        foreach ($stats['counts'] as $level => $count) {
                if ($count > 0) {
                    $body .= '<tr style="background: '.$this->getLevelBackground($level).'; color: '.$this->getLevelColor($level).';"><td style="padding: 6px; text-align:right;"><strong>' . $l->t($this->getLevelName($level)) . '</strong></td><td>:</td><td style="padding: 6px; text-align:right;">' . $count . '</td></tr>';
                    $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
                }
            }
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
        $body .= '<tr style="height: 1px;"><td style="background: black;" colspan="3"></td></tr>';
        $body .= '</tbody>';
        $body .= '</table>';
        $body .= '<p style="font-size: 0.8em;">Ⓒ LogCleaner ' . $this->appManager->getAppVersion('logcleaner', true) .' <img width="14px" height="14px" src="'.$this->url->getBaseUrl() . $this->url->imagePath('logcleaner', 'logcleaner-dark.svg').'"></p>';
        $body .= '<a href="' . $targetUrl . '"><button style="padding: 8px; font-size: 16px; background-color: #00679E; color: white; border: none; border-radius: 8px; cursor: pointer;">'. $l->t('Details can be found in the log management of your Nextcloud instance.') .'</button></a></center>';
        $message = $this->mailer->createMessage();
		$emailTemplate = $this->generateEmailTemplate($subject, $body, $targetUrl, $user, $l->t('Details can be found in the log management of your Nextcloud instance.'));
		$emailTemplate->setSubject($subject);
		$message->useTemplate($emailTemplate);
		$message->setTo([$adminEmail]);
		$this->mailer->send($message);
    }

     public function sendTestEmail() {
        $lastSent = $this->appConfig->getValueInt('logcleaner', 'last_email_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_offset', '0');
        $stats = $this->getLogStats($lastSent, $minLevel, true);
        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');
        $adminEmail = $this->appConfig->getValueString('logcleaner', 'admin_mail', '');
        $adminName = $this->appConfig->getValueString('logcleaner', 'admin_mail_name', '');
        $user = $this->userManager->get($adminName);
        if ($user !== null) {
            $lang = $this->l10nFactory->getUserLanguage($user);
            $adminDisplayname = $user->getDisplayName();
        } else {
            $this->logger->error("LogCleaner: No administrator selected to send a log report or the selected administrator does not have a valid email address (adminname '$adminName' not known)");
            return;
        }

        $l = $this->l10nFactory->get('logcleaner', $lang);
        if (empty($adminEmail)) {
            $this->logger->error("LogCleaner: No administrator selected to send a log report or the selected administrator does not have a valid email address");
            return;
        }
        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));
        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));
        $body = "<center>" . $l->t('Hello %1$s,', ['<strong>'.$adminDisplayname.'</strong>']) . "<br><br>";
        if (array_sum($stats['counts']) !== 0) {
            $body .= $l->t('in the period from %1$s to %2$s new log entries were registered.', ['<strong>'.$fromDate.'</strong>', '<strong>'.$toDate.'</strong>']) . "<br><br>";
            $body .= $l->t('Distribution by log level:') . "<br><br>";
            $body .= '<table style="border-collapse:collapse;border-spacing:0;">';
            $body .= '<tbody>';
            $body .= '<tr style="height: 1px;"><td style="background: black;" colspan="3"></td></tr>';
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            foreach ($stats['counts'] as $level => $count) {
                    if ($count > 0) {
                        $body .= '<tr style="background: '.$this->getLevelBackground($level).'; color: '.$this->getLevelColor($level).';"><td style="padding: 6px; text-align:right;"><strong>' . $l->t($this->getLevelName($level)) . '</strong></td><td>:</td><td style="padding: 6px; text-align:right;">' . $count . '</td></tr>';
                        $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
                    }
                }
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            $body .= '<tr style="height: 3px;"><td></td><td></td><td></td></tr>';
            $body .= '<tr style="height: 1px;"><td style="background: black;" colspan="3"></td></tr>';
            $body .= '</tbody>';
            $body .= '</table>';
        }
        $body .= '<p style="font-size: 0.8em;">Ⓒ LogCleaner ' . $this->appManager->getAppVersion('logcleaner', true) .' <img width="14px" height="14px" src="'.$this->url->getBaseUrl() . $this->url->imagePath('logcleaner', 'logcleaner-dark.svg').'"></p>';
        $body .= '<a href="' . $targetUrl . '"><button style="padding: 8px; font-size: 16px; background-color: #00679E; color: white; border: none; border-radius: 8px; cursor: pointer;">'. $l->t('This is a test email') .'</button></a></center>';
        $message = $this->mailer->createMessage();
		$subject = $l->t('Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
		$emailTemplate = $this->generateEmailTemplate($subject, $body, $targetUrl, $user, $l->t('This is a test email'));
		$emailTemplate->setSubject($subject);
		$message->useTemplate($emailTemplate);
		$message->setTo([$adminEmail]);
		$this->mailer->send($message);
    }

    public function sendSummaryNotification() {
        $datetime = $this->timeFactory->getDateTime();
        $lastSent = $this->appConfig->getValueInt('logcleaner', 'last_noti_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_offset', '0');
        $stats = $this->getLogStats($lastSent, $minLevel);
        if (array_sum($stats['counts']) === 0) {
            if( $this->appConfig->getValueString('logcleaner', 'wtparam_logmessage', '2') === '2' ) $this->logger->info("LogCleaner: No new log entries available. Therefore, no sending of a log report by notification");
            return;
        }
        $adminName = $this->appConfig->getValueString('logcleaner', 'admin_noti', '');
        $user = $this->userManager->get($adminName);
        $lang = $this->l10nFactory->getUserLanguage($user);
        $adminDisplayname = $user->getDisplayName();
        $l = $this->l10nFactory->get('logcleaner', $lang);
        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));
        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));
        switch ($this->appConfig->getValueString('logcleaner', 'noti_interval', 'daily')) {
			case 'daily':
				$translatedSubject = $l->t('Daily Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
                break;
			case 'weekly':
                $translatedSubject = $l->t('Weekly Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
                break;
			case 'monthly':
                $translatedSubject = $l->t('Monthly Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
                break;
			default:
				$translatedSubject = $l->t('Daily Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);
		}
        $mysubject = $translatedSubject;
        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');
        $plainMessage = $l->t('Hello %1$s,', [$adminDisplayname]) . "\n\n" . $l->t('in the period from %1$s to %2$s new log entries were registered.', [$fromDate, $toDate]) . "\n\n";
        $plainMessage .= $l->t('Distribution by log level:') . "\n";
        $plainMessage .= "--------------------------\n";
        foreach ($stats['counts'] as $level => $count) {
            if ($count > 0) {
                $plainMessage .= sprintf("%-10s: %d\n", $l->t($this->getLevelName($level)), $count);
            }
        }
        $plainMessage .= "--------------------------\n\n";
        $plainMessage .= "{applink}";
        $para = [
            'richSubject' => $mysubject,
            'richSubjectParameters' => [],
            'richMessage' => $plainMessage,
            'richMessageParameters' => [
                'applink' => [
                    'type' => 'highlight',
                    'id'   => 'logcleaner_highlight_link',
                    'name' => $l->t('Details can be found in the log management of your Nextcloud instance.'),
                    'link' => $targetUrl
                ]
            ],
            'parsedSubject' => $mysubject,
            'parsedMessage' => $plainMessage
        ];
        $notification = $this->notificationManager->createNotification();
        $notification->setApp('logcleaner')
            ->setUser($adminName)
            ->setDateTime($datetime)
            ->setObject('remote', '1123')
            ->setSubject('logcleaner', $para);
        $this->notificationManager->notify($notification);
        return true;
    }

    public function sendTestNotification() {
        $now = time();
        $datetime = $this->timeFactory->getDateTime();
        $lastSent = $this->appConfig->getValueInt('logcleaner', 'last_noti_test_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->appConfig->getValueString('logcleaner', 'logcleaner_wt_offset', '0');
        $stats = $this->getLogStats($lastSent, $minLevel, true);
        $adminName = $this->appConfig->getValueString('logcleaner', 'admin_noti', '');
        $user = $this->userManager->get($adminName);
        $lang = $this->l10nFactory->getUserLanguage($user);
        $adminDisplayname = $user->getDisplayName();
        $l = $this->l10nFactory->get('logcleaner', $lang);
        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));
        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));
        $translatedSubject = $l->t('Test notification');
        $mysubject = $translatedSubject;
        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');
        $plainMessage = $l->t('Hello %1$s,', [$adminDisplayname]) . "\n\n";
        if (array_sum($stats['counts']) !== 0) {
            $plainMessage .= $l->t('in the period from %1$s to %2$s new log entries were registered.', [$fromDate, $toDate]) . "\n\n";
            $plainMessage .= $l->t('Distribution by log level:') . "\n";
            $plainMessage .= "--------------------------\n";
            foreach ($stats['counts'] as $level => $count) {
                if ($count > 0) {
                    $plainMessage .= sprintf("%-10s: %d\n", $l->t($this->getLevelName($level)), $count);
                }
            }
            $plainMessage .= "\n";
            $plainMessage .= "--------------------------\n";
        }
        $plainMessage .= $l->t('This is a test notification') . "\n\n";
        $plainMessage .= "{applink}";
        $para = [
            'richSubject' => $mysubject,
            'richSubjectParameters' => [],
            'richMessage' => $plainMessage,
            'richMessageParameters' => [
                'applink' => [
                    'type' => 'highlight',
                    'id'   => 'logcleaner_highlight_link',
                    'name' => $l->t('Details can be found in the log management of your Nextcloud instance.'),
                    'link' => $targetUrl
                ]
            ],
            'parsedSubject' => $mysubject,
            'parsedMessage' => $plainMessage
        ];
        $notification = $this->notificationManager->createNotification();
        $notification->setApp('logcleaner')
            ->setUser($adminName)
            ->setDateTime($datetime)
            ->setObject('remote', '1123')
            ->setSubject('logcleaner', $para);
        $this->notificationManager->notify($notification);
        $this->appConfig->setValueInt('logcleaner', 'last_noti_test_timestamp', $now);
        return true;
    }

    private function getLogStats(int $lastRun, int $minLevel, bool $test = false): array {
        $stats = ['counts' => [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0], 'oldest' => null, 'newest' => null];
        $interval = $this->appConfig->getValueString('logcleaner', 'email_interval', 'daily');
        $secondsNeeded = [
            'daily' => 86400,
            'weekly' => 604800,
            'monthly' => 2592000
        ][$interval] ?? 86400;
        $filetime = $lastRun - $secondsNeeded;
        $logFile = $this->logService->getLogFile();
        if ($test === true) $filesToProcess = [$logFile];
        else $filesToProcess = [$logFile, $logFile . '.1'];
        foreach ($filesToProcess as $file) {
            if (!file_exists($file) || filemtime($file) < $filetime) {
                continue;
            }
            $handle = fopen($file, 'r');
            $fileStats = $this->parseFileBackwards($handle, $lastRun, $minLevel);
            fclose($handle);
            foreach ($fileStats['counts'] as $level => $count) {
                $stats['counts'][$level] += $count;
            }
            if ($fileStats['newest'] && $stats['newest'] === null) $stats['newest'] = $fileStats['newest'];
            if ($fileStats['oldest']) $stats['oldest'] = $fileStats['oldest'];
            if ($fileStats['reachedLimit']) break;
        }
        return $stats;
    }

    private function parseFileBackwards($handle, int $lastRun, int $minLevel): array {
        $res = ['counts' => [0=>0,1=>0,2=>0,3=>0,4=>0], 'reachedLimit' => false, 'newest' => null, 'oldest' => null];
        $cursor = -1;
        $line = '';

        while (fseek($handle, $cursor, SEEK_END) !== -1) {
            $char = fgetc($handle);
            if ($char === "\n" && $line !== '') {
                $entry = json_decode(strrev($line), true);
                if ($entry && isset($entry['time'])) {
                    $entryTime = strtotime($entry['time']);
                    if ($entryTime <= $lastRun) {
                        $res['reachedLimit'] = true;
                        break;
                    }
                    if ($entry['level'] >= $minLevel) {
                        $res['counts'][$entry['level']]++;
                        if ($res['newest'] === null) $res['newest'] = $entryTime;
                        $res['oldest'] = $entryTime;
                    }
                }
                $line = '';
            } else {
                $line .= $char;
            }
            $cursor--;
        }
        return $res;
    }

    private function getLevelName(int $level): string {
        return [0 => 'debug', 1 => 'info', 2 => 'warning', 3 => 'error', 4 => 'fatal'][$level] ?? 'Unknown';
    }

    private function getLevelBackground(int $level): string {
        return [0 => '#DFF0D8', 1 => '#D9EDF7', 2 => '#fcf8e3', 3 => '#f2dede', 4 => '#f9aaf6'][$level] ?? 'Unknown';
    }

    private function getLevelColor(int $level): string {
        return [0 => '#3C763D', 1 => '#31708F', 2 => '#8A6d3B', 3 => '#A94442', 4 => '#870482'][$level] ?? 'Unknown';
    }

    private function generateEmailTemplate($subject, $text, $link, $user, $buttontext) {
        $text = '<p style="width: 100%;">' . $text . '</p>';
		$emailTemplate = $this->mailer->createEMailTemplate('logcleaner.LogNotification', []);
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($subject, false);
		$emailTemplate->addBodyText($text, $text);
		//$emailTemplate->addBodyButton($buttontext, $link);
        $emailTemplate->addFooter();
		return $emailTemplate;
	}
}
