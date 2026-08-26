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

namespace OCA\LogCleaner\AppInfo;

use OCP\AppFramework\App;
use OCP\Server;
use OCP\App\IAppManager;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\Util;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use OCA\LogCleaner\Dashboard\LogCleanerWidget;
use OCA\LogCleaner\Dashboard\LogCleanerWidget2;
use OCA\LogCleaner\Notification\Notifier;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'logcleaner';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(\OCP\AppFramework\Bootstrap\IRegistrationContext $context): void {
		$context->registerDashboardWidget(\OCA\LogCleaner\Dashboard\LogCleanerWidget::class);
        $context->registerDashboardWidget(\OCA\LogCleaner\Dashboard\LogCleanerWidget2::class);
		$context->registerNotifierService(Notifier::class);
		$config = Server::get(IConfig::class);
		$appConfig = Server::get(IAppConfig::class);
		$context->registerService(LogService::class, function($c) use ($config) {
			$path = $config->getSystemValue('logfile');
			if (!$path || !file_exists($path)) {
				$path = $config->getSystemValue('datadirectory') . '/nextcloud.log';
			}
			$AuditLogFile = $path;
			$LogFile = $AuditLogFile;
			$FlowLogFile = $LogFile;
			return new \OCA\LogCleaner\Log\LogService(
				$AuditLogFile,
				$FlowLogFile,
				$LogFile,
				$config,
				$appConfig,
				$c->query(\Psr\Log\LoggerInterface::class),
				$c->query(\OCP\IL10N::class),
				$c->query(\OCA\LogCleaner\Helper\Helper::class),
				$path
			);
		});

		$context->registerService('AuditLogFile', function($c)  use ($config, $appConfig) {
			$auditType = $config->getSystemValueString('log_type_audit', 'file');
			$logFile = $config->getSystemValueString('logfile_audit', '');
			if ($auditType === 'file' && !$logFile) {
				$default = $config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/audit.log';
				$logFile = $appConfig->getValueString('admin_audit', 'logfile', $default);
			}
			return $logFile;
		});

		$context->registerService('FlowLogFile', function($c)  use ($config, $appConfig) {
			$default = $config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/flow.log';
			$logFile = trim($appConfig->getValueString('workflowengine', 'logfile', $default));
			return $logFile;
		});

		$context->registerService('LogFile', function($c)  use ($config) {
			$path = $config->getSystemValue('logfile');
			if (!$path || !file_exists($path)) {
				$path = $config->getSystemValue('datadirectory') . '/nextcloud.log';
			}
			return $path;
		});
	}

	public function boot(IBootContext $context): void {
		Util::addStyle(self::APP_ID, 'logcleaner-nav');
		$igroupManager = $context->getServerContainer()->get(IGroupManager::class);
		$iuserSession = $context->getServerContainer()->get(IUserSession::class);

		$navigationManager = $context->getServerContainer()->get(INavigationManager::class);
        $urlGenerator = $context->getServerContainer()->get(IURLGenerator::class);
		$appManager = $context->getServerContainer()->get(IAppManager::class);
		$appConfig = $context->getServerContainer()->get(IAppConfig::class);

		$appManager->enableAppForGroups(self::APP_ID, array('admin'), false);

		$myuid = $iuserSession->getUser();

		if ($myuid === null) {
			return;
		}

		if (!in_array("admin", $igroupManager->getUserGroupIds($myuid))) {
			return;
		}

		try {
			$navigationManager->add(function () use ($urlGenerator, $appConfig) {
				$wtpara_menue = (int)$appConfig->getValueString(self::APP_ID, 'wtparam_menue');

				$myapptop = [
					'id' => self::APP_ID,
					'order' => 1000,
					'href' => $urlGenerator->linkToRoute(self::APP_ID.'.page.index'),
					'icon' => $urlGenerator->imagePath(self::APP_ID, self::APP_ID.'.svg'),
					'name' => 'LogCleaner',
					'type' => 'link',
					//'classes' => 'highlighted-nav-item js-admin-tab',
					'app' => self::APP_ID
				];
				$myappright = [
					'id' => self::APP_ID,
					'order' => 2,
					'href' => $urlGenerator->linkToRoute(self::APP_ID.'.page.index'),
					'icon' => $urlGenerator->imagePath(self::APP_ID, self::APP_ID.'-dark.svg'),
					'name' => 'LogCleaner',
					'type' => 'settings',
					//'classes' => 'highlighted-nav-item js-admin-tab',
					'app' => self::APP_ID
				];

				if (!isset($wtpara_menue)) {
					$appConfig->setValueString(self::APP_ID, 'wtparam_menue', '1');
				}
				if ($wtpara_menue === 1) { // right
					$myapp = $myappright;
				}
				else { // top
					$myapp = $myapptop;
				}

				return $myapp;
			});
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable) {
		}
	}

}
