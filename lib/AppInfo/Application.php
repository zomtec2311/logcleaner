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
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IConfig;

class Application extends App implements IBootstrap {
	public const APP_ID = 'logcleaner';
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {

		$server = $context->getServerContainer();
		try {
			$context->injectFn($this->registerAppsManagementNavigation(...));
		} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable) {
		}

	}

	private function registerAppsManagementNavigation(IUserSession $userSession, IConfig $config): void {
		$container = $this->getContainer();
		$this->config = $config;
		$wtpara_menue = (int)$this->config->getAppValue('logcleaner', 'wtparam_menue');
		if (!isset($wtpara_menue)) {
			$wtpara_menue = 1;
			$this->config->setAppValue('logcleaner', 'wtparam_menue', 1);
		}
			/** @var IGroupManager $groupManager */
			$groupManager = $container->get(IGroupManager::class);
			/** @var IUser $user */
		$user = \OC::$server->getUserSession()->getUser();
		if (!is_null($user)) {
			if ($groupManager->isInGroup($user->getUID(), 'admin')) {
				if ($wtpara_menue == 1) {
					$container->get(INavigationManager::class)->add(function () use ($container) {
						$urlGenerator = $container->get(IURLGenerator::class);
						return [
							'id' => self::APP_ID,
							'order' => 2,
							'href' => $urlGenerator->linkToRoute('logcleaner.page.index'),
							'icon' => $urlGenerator->imagePath('logcleaner', 'logcleaner-dark.svg'),
							'name' => 'LogCleaner',
							'type' => 'settings'
						];
					});
				}
				else {
					$container->get(INavigationManager::class)->add(function () use ($container) {
						$urlGenerator = $container->get(IURLGenerator::class);
						return [
						'id' => self::APP_ID,
						'order' => 1000,
						'href' => $urlGenerator->linkToRoute('logcleaner.page.index'),
						'icon' => $urlGenerator->imagePath('logcleaner', 'logcleaner.svg'),
						'name' => 'LogCleaner',
						];
					});
				}
			}
			else {
				$container->get(INavigationManager::class)->add(function () use ($container) {
					$urlGenerator = $container->get(IURLGenerator::class);
					return [
						'id' => self::APP_ID,
						'order' => 1000,
						'icon' => $urlGenerator->imagePath('logcleaner', 'logcleaner-none.svg'),
						'name' => ' ',
					];
				});
			}
		}
	}
}
