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

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



namespace OCA\LogCleaner\Cron;

use OCA\LogCleaner\Controller\LogsController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use OCP\IAppConfig;
use OCA\LogCleaner\Service\LogNotificationService;

class LogReportJob extends TimedJob {
	private LoggerInterface $logger;
    private LogsController $setcon;
	 private $logService;

	public function __construct(ITimeFactory $time,
		LoggerInterface $logger, LogsController $setcon, private IAppConfig $appConfig, LogNotificationService $logService) {
		parent::__construct($time);
		$this->logger = $logger;
        $this->setcon = $setcon;
        $this->setInterval(3600);
		$this->appconfig = $appConfig;
		$this->logService = $logService;
	}

	protected function run($arguments) {
        $notienabled = $this->appconfig->getValueString('logcleaner', 'notification_enabled', 'no');
        if ($notienabled === 'yes') {
            $this->runnotify();
        }
        $enabled = $this->appconfig->getValueString('logcleaner', 'email_notification_enabled', 'no');
        if ($enabled !== 'yes') {
            return;
        }


        $interval = $this->appconfig->getValueString('logcleaner', 'email_interval', 'daily');
        $lastSent = $this->appconfig->getValueInt('logcleaner', 'last_email_timestamp', 0);
        $now = time();

        $secondsNeeded = [
            'daily' => 86400,
            'weekly' => 604800,
            'monthly' => 2592000
        ][$interval] ?? 86400;
        if (($now - $lastSent) >= $secondsNeeded) {
            $this->logService->sendSummaryEmail();
            $this->appconfig->setValueInt('logcleaner', 'last_email_timestamp', $now);
			$this->logger->info('LogCleaner background job to report logs by email executed!');
        }
    }

    public function runnotify() {
        $interval = $this->appconfig->getValueString('logcleaner', 'noti_interval', 'daily');
        $lastSent = $this->appconfig->getValueInt('logcleaner', 'last_noti_timestamp', 0);
        $now = time();

        $secondsNeeded = [
            'daily' => 86400,
            'weekly' => 604800,
            'monthly' => 2592000
        ][$interval] ?? 86400;

        if (($now - $lastSent) >= $secondsNeeded) {
            $this->logService->sendSummaryNotification();
            $this->appconfig->setValueInt('logcleaner', 'last_noti_timestamp', $now);
			$this->logger->info('LogCleaner background job to report logs by notification executed!');
        }

    }
}
