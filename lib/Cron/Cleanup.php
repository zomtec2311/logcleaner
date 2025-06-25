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

use OCA\LogCleaner\Controller\SettingsController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;
use OCP\IAppConfig;

class Cleanup extends TimedJob {
	private LoggerInterface $logger;
    private SettingsController $setcon;

	public function __construct(ITimeFactory $time,
		LoggerInterface $logger, SettingsController $setcon, private IAppConfig $appConfig,) {
		parent::__construct($time);
		$this->logger = $logger;
        $this->setcon = $setcon;
		$this->setInterval(3600*24);
		$this->appconfig = $appConfig;
	}

	/**
	 * @param array $argument
	 */
	protected function run($argument): void {
		$wtpara_cron_deldub = (int)$this->appconfig->getValueString('logcleaner', 'wtpara_cron_deldub', '9', false);
		if($wtpara_cron_deldub ===2) {
        $this->setcon->delDub();
        $this->logger->debug('LogCleaner background job executed!');
		}
	}
}
