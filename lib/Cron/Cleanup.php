<?php

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
		// t('logcleaner', 'Automatically delete duplicates every 24 hours')
		}
	}
}
