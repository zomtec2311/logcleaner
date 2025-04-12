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

use OCA\LogCleaner\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;

use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Services\IInitialState;
use OCA\LogReader\Service\SettingsService;

class PageController extends Controller {

    public function __construct(private IConfig $config, IRequest $request, private IInitialState $initialState, private SettingsService $settingsService, private Helper $helper)
    {
        $this->config = $config;
        $this->helper = $helper;
        parent::__construct(Application::APP_ID, $request);
    }

	#[NoCSRFRequired]
	//#[NoAdminRequired]      //<----------- auskommentiert haben nur admins access
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/')]

	public function index(?string $getParameter, ?int $Zeile): TemplateResponse {
    return new TemplateResponse(
			Application::APP_ID,
			'index',
		);
  }
}
