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

class LogNotificationService {

    private $config;
    private $mailer;
    private $timeFactory;


    public function __construct(
        IConfig $config,
        IMailer $mailer,
        ITimeFactory $timeFactory,
        private LogService $logService,
        private readonly LoggerInterface $logger,
        private IFactory $l10nFactory,
        private IUserManager $userManager,
        protected IManager $notificationManager,
        private IURLGenerator $url,
    ) {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->timeFactory = $timeFactory;
        $this->userManager = $userManager;
    }

    public function sendSummaryEmail() {

        $lastSent = (int)$this->config->getAppValue('logcleaner', 'last_email_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->config->getAppValue('logcleaner', 'logcleaner_wt_offset', 0);

        $stats = $this->getLogStats($lastSent, $minLevel);

        if (array_sum($stats['counts']) === 0) {
            return;
        }

        $adminEmail = $this->config->getAppValue('logcleaner', 'admin_email', '');
        $adminName = $this->config->getAppValue('logcleaner', 'admin_email_name', '');
        $user = $this->userManager->get($adminName);
        $lang = $this->l10nFactory->getUserLanguage($user);
        $l = $this->l10nFactory->get('logcleaner', $lang);
        if (empty($adminEmail)) {
            $this->logger->error("LogCleaner: No administrator selected to send a log report or the selected administrator does not have a valid email address");
            return;
        }

        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));
        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));

        $message = $this->mailer->createMessage();
        $message->setTo([$adminEmail]);
        $message->setSubject($l->t('Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]));

        $body = $l->t('Hello %1$s,', [$adminName]) . "\n\n";
        $body .= $l->t('in the period from %1$s to %2$s new log entries were registered.', [$fromDate, $toDate]) . "\n\n";

        $body .= $l->t('Distribution by log level:') . "\n";
        $body .= "--------------------------\n";

        foreach ($stats['counts'] as $level => $count) {
            if ($count > 0) {
                $body .= sprintf("%-10s: %d\n", $l->t($this->getLevelName($level)), $count);
            }
        }

        $body .= "\n" . $l->t('Details can be found in the log management of your Nextcloud instance.');


        $message->setPlainBody($body);
        $this->mailer->send($message);
    }

    public function sendTestEmail() {

        $lastSent = (int)$this->config->getAppValue('logcleaner', 'last_email_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->config->getAppValue('logcleaner', 'logcleaner_wt_offset', 0);
        $stats = $this->getLogStats($lastSent, $minLevel);

        $adminEmail = $this->config->getAppValue('logcleaner', 'admin_email', '');
        $adminName = $this->config->getAppValue('logcleaner', 'admin_email_name', '');
        $user = $this->userManager->get($adminName);
        $lang = $this->l10nFactory->getUserLanguage($user);
        $l = $this->l10nFactory->get('logcleaner', $lang);
        if (empty($adminEmail)) {
            $this->logger->error("LogCleaner: No administrator selected to send a log report or the selected administrator does not have a valid email address");
            return;
        }

        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));
        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));

        $message = $this->mailer->createMessage();
        $message->setTo([$adminEmail]);
        $message->setSubject($l->t('Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]));

        $body = $l->t('Hello %1$s,', [$adminName]) . "\n\n";
        $body .= $l->t('in the period from %1$s to %2$s new log entries were registered.', [$fromDate, $toDate]) . "\n\n";

        $body .= $l->t('Distribution by log level:') . "\n";
        $body .= "--------------------------\n";

        foreach ($stats['counts'] as $level => $count) {
            if ($count > 0) {
                $body .= sprintf("%-10s: %d\n", $l->t($this->getLevelName($level)), $count);
            }
        }

        $body .= $l->t('This is a test email') . "\n";


        $message->setPlainBody($body);
        $this->mailer->send($message);
    }

    public function sendSummaryNotification() {
        $datetime = $this->timeFactory->getDateTime();
        $lastSent = (int)$this->config->getAppValue('logcleaner', 'last_noti_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->config->getAppValue('logcleaner', 'logcleaner_wt_offset', 0);
        $stats = $this->getLogStats($lastSent, $minLevel);

        if (array_sum($stats['counts']) === 0) {
            $this->logger->info("LogCleaner: No new log entries available. Therefore, no sending of a log report by notification");
            return;
        }

        $adminName = $this->config->getAppValue('logcleaner', 'admin_noti', '');
        $user = $this->userManager->get($adminName);

        $lang = $this->l10nFactory->getUserLanguage($user);

        $l = $this->l10nFactory->get('logcleaner', $lang);

        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));

        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));

        $translatedSubject = $l->t('Nextcloud Log Summary (%1$s to %2$s)', [$fromDate, $toDate]);

        $mysubject = $translatedSubject;

        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');

        $plainMessage = $l->t('Hello %1$s,', [$adminName]) . "\n\n" .

                        $l->t('in the period from %1$s to %2$s new log entries were registered.', [$fromDate, $toDate]) . "\n\n";

        $plainMessage .= $l->t('Distribution by log level:') . "\n";
        $plainMessage .= "--------------------------\n";

        foreach ($stats['counts'] as $level => $count) {
            if ($count > 0) {
                $plainMessage .= sprintf("%-10s: %d\n", $l->t($this->getLevelName($level)), $count);
            }
        }

        $plainMessage .= $l->t('Details can be found in the log management of your Nextcloud instance.');

        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');

        $para = [
            'richSubject' => $mysubject,
            'richSubjectParameters' => [],

            'richMessage' => $plainMessage,
            'richMessageParameters' => [],

            'parsedSubject' => $mysubject,
            'parsedMessage' => $plainMessage . "\n\n" . $targetUrl,

            'actions' => [
                [
                    'type' => 'link',
                    'id'   => 'open',
                    'label' => $l->t('open'),
                    'link' => $targetUrl,
                    'icon' => 'icon-confirm'
                ]
            ]
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
        $lastSent = (int)$this->config->getAppValue('logcleaner', 'last_noti_test_timestamp', 0);
        $minLevel = (int)$this->config->getSystemValue('loglevel', 2);
        $offset = (int)$this->config->getAppValue('logcleaner', 'logcleaner_wt_offset', 0);
        $stats = $this->getLogStats($lastSent, $minLevel);
        $adminName = $this->config->getAppValue('logcleaner', 'admin_noti', '');
        $user = $this->userManager->get($adminName);

        $lang = $this->l10nFactory->getUserLanguage($user);

        $l = $this->l10nFactory->get('logcleaner', $lang);

        $fromDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['oldest']));

        $toDate = date('d.m.Y H:i', strtotime("$offset hours", $stats['newest']));

        $translatedSubject = $l->t('Test notification');

        $mysubject = $translatedSubject;

        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');

        $plainMessage = $l->t('Hello %1$s,', [$adminName]) . "\n\n";



        if (array_sum($stats['counts']) !== 0) {
            $plainMessage .= $l->t('in the period from %1$s to %2$s new log entries were registered.', [$fromDate, $toDate]) . "\n\n";
            $plainMessage .= $l->t('Distribution by log level:') . "\n";
            $plainMessage .= "--------------------------\n";

            foreach ($stats['counts'] as $level => $count) {
                if ($count > 0) {
                    $plainMessage .= sprintf("%-10s: %d\n", $l->t($this->getLevelName($level)), $count);
                }
            }
        }

        $plainMessage .= $l->t('This is a test notification') . "\n\n";

        $targetUrl = $this->url->linkToRouteAbsolute('logcleaner.page.index');

        $para = [
            'richSubject' => $mysubject,
            'richSubjectParameters' => [],

            'richMessage' => $plainMessage,
            'richMessageParameters' => [],

            'parsedSubject' => $mysubject,
            'parsedMessage' => $plainMessage . "\n\n" . $targetUrl,


            'actions' => [
                [
                    'type' => 'link',
                    'id'   => 'open',
                    'label' => $l->t('open'),
                    'link' => $targetUrl,
                    'icon' => 'icon-confirm'
                ]
            ]
        ];

        $notification = $this->notificationManager->createNotification();

        $notification->setApp('logcleaner')
            ->setUser($adminName)
            ->setDateTime($datetime)
            ->setObject('remote', '1123')
            ->setSubject('logcleaner', $para);

        $this->notificationManager->notify($notification);

        $this->config->setAppValue('logcleaner', 'last_noti_test_timestamp', $now);

        return true;

    }

    private function getLogStats(int $lastRun, int $minLevel): array {
        $stats = ['counts' => [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0], 'oldest' => null, 'newest' => null];
        $interval = (string)$this->config->getAppValue('logcleaner', 'email_interval', 'daily');

        $secondsNeeded = [
            'daily' => 86400,
            'weekly' => 604800,
            'monthly' => 2592000
        ][$interval] ?? 86400;

        $filetime = $lastRun - $secondsNeeded;

        $logFile = $this->logService->getLogFile();
        $filesToProcess = [$logFile, $logFile . '.1'];
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
}
