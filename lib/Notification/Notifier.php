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

namespace OCA\LogCleaner\Notification;

use OCA\LogCleaner\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;
use Psr\Log\LoggerInterface;

use OCP\IUserSession;

class Notifier implements INotifier {
	private IFactory $factory;
	private IURLGenerator $url;
	private IUserSession $userSession;

	public function __construct(\OCP\L10N\IFactory $factory,
								\OCP\IURLGenerator $urlGenerator,
								IUserSession $userSession,
								private readonly LoggerInterface $logger,) {
		$this->factory = $factory;
		$this->url = $urlGenerator;
		$this->userSession = $userSession;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 * @return string
	 */
	public function getID(): string {
		return 'logcleaner';
	}

	/**
	 * Human-readable name describing the notifier
	 * @return string
	 */
	public function getName(): string {
		return $this->factory->get('logcleaner')->t('logcleaner');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'logcleaner') {
			throw new UnknownNotificationException();
		}

        switch ($notification->getSubject()) {
			case 'logcleaner':
				$parameters = $notification->getSubjectParameters() ?? [];

				$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('logcleaner', 'logcleaner-dark.svg')))
				->setLink($this->url->linkToRouteAbsolute('logcleaner.page.index'));

				if (!empty($parameters['richSubject'])) {
				$subjectParams = $parameters['richSubjectParameters'] ?? [];
				$notification->setRichSubject($parameters['richSubject'], $subjectParams);
				} else {
				$notification->setParsedSubject($parameters['parsedSubject'] ?? 'Log Summary');
				}
				if (!empty($parameters['richMessage'])) {
				$messageParams = $parameters['richMessageParameters'] ?? [];
				$notification->setRichMessage($parameters['richMessage'], $messageParams);
				} else {
				$notification->setParsedMessage($parameters['parsedMessage'] ?? '');
				}

				if (!empty($parameters['actions'])) {
					foreach ($parameters['actions'] as $actionData) {
						$action = $notification->createAction();
						$action->setLabel($actionData['label'])
						->setLink($actionData['link'], 'GET')
						->setPrimary(true);
						$notification->addAction($action);
					}
				}
				return $notification;

			case 'cli':
			case 'ocs':
			case 'self':
				$subjectParams = $notification->getSubjectParameters();
				if (isset($subjectParams['subject'])) {

					$notification->setRichSubject($subjectParams['subject'], $subjectParams['parameters']);
				} else {

					$notification->setParsedSubject($subjectParams[0]);
				}
				$messageParams = $notification->getMessageParameters();
				if (!empty($messageParams)) {
					if (!empty($messageParams['message'])) {

						$notification->setRichMessage($messageParams['message'], $messageParams['parameters']);
					} elseif (!empty($messageParams[0])) {

						$notification->setParsedMessage($messageParams[0]);
					}
				}

				$notification->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('notifications', 'notifications-dark.svg')));
				return $notification;

			default:
				throw new UnknownNotificationException('subject');
		}
	}

	/**
	 * This is a little helper function which automatically sets the simple parsed subject
	 * based on the rich subject you set. This is also the default behaviour of the API
	 * since Nextcloud 26, but in case you would like to return simpler or other strings,
	 * this function allows you to take over.
	 *
	 * @param INotification $notification
	 */
	protected function setParsedSubjectFromRichSubject(INotification $notification): void {
		$placeholders = $replacements = [];
		foreach ($notification->getRichSubjectParameters() as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			if ($parameter['type'] === 'file') {
				$replacements[] = $parameter['path'];
			} else {
				$replacements[] = $parameter['name'];
			}
		}

		$notification->setParsedSubject(str_replace($placeholders, $replacements, $notification->getRichSubject()));
	}
}
