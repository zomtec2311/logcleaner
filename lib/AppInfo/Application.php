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
use OCP\IAppConfig;
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
		$server = $context->getServerContainer();
		try {
			$context->injectFn($this->registerAppsManagementNavigation(...));
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable) {
		}
	}

	private function registerAppsManagementNavigation(IAppManager $appManager): void {
		$container = $this->getContainer();
		$config = $this->getContainer()->query(IConfig::class);
		$appConfig = $this->getContainer()->query(IAppConfig::class);
		$appManager->enableAppForGroups(self::APP_ID, array('admin'), false);
		$wtpara_menue = (int)$appConfig->getValueString(self::APP_ID, 'wtparam_menue');
		if (!isset($wtpara_menue)) {
			$wtpara_menue = 1;
			$appConfig->setValueString(self::APP_ID, 'wtparam_menue', '1');
		}
		if ($wtpara_menue == 1) { // right
			$container->get(INavigationManager::class)->add(function () use ($container) {
				$urlGenerator = $container->get(IURLGenerator::class);
				return [
					'id' => self::APP_ID,
					'order' => 2,
					'href' => $urlGenerator->linkToRoute(self::APP_ID.'.page.index'),
					'icon' => $urlGenerator->imagePath(self::APP_ID, self::APP_ID.'-dark.svg'),
					'name' => 'LogCleaner',
					'type' => 'settings'
				];
			});
		}
		else { // top
			$container->get(INavigationManager::class)->add(function () use ($container) {
				$urlGenerator = $container->get(IURLGenerator::class);
				return [
				'id' => self::APP_ID,
				'order' => 1000,
				'href' => $urlGenerator->linkToRoute(self::APP_ID.'.page.index'),
				'icon' => $urlGenerator->imagePath(self::APP_ID, self::APP_ID.'.svg'),
				'name' => 'LogCleaner',
				];
			});
		}
	}
}
