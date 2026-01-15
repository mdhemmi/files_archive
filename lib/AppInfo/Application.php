<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Time_Archive\AppInfo;

use OCA\Time_Archive\EventListener;
use OCA\Time_Archive\Navigation\NavigationManager;
use OCA\Time_Archive\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\SystemTag\ManagerEvent;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'time_archive';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(ManagerEvent::EVENT_DELETE, EventListener::class);
		$context->registerNotifierService(Notifier::class);
		// Note: Repair steps are registered in appinfo/info.xml, not here
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		$container = $context->getAppContainer();
		
		try {
			// Register top navigation entry
			$navigationManager = $container->get(NavigationManager::class);
			$navigationManager->register();
			error_log('[Time Archive] NavigationManager registered in boot()');
		} catch (\Exception $e) {
			error_log('[Time Archive] Error registering navigation in boot(): ' . $e->getMessage());
		}
		
		// Load Files app sidebar navigation script
		// This script will register the Archive entry in the Files app sidebar
		// Note: Util::addScript automatically prefixes with app ID, so we just pass the base name
		// DISABLED: Commented out to avoid "Could not find resource" errors
		// The navigation script is optional - it only adds a sidebar entry in Files app
		// Users can still access archived files via the Archive app view or by navigating to .archive folder
		// 
		// To enable: Uncomment below and ensure js/time_archive-navigation.js exists on the server
		// $requestUri = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
		// $isFilesPage = strpos($requestUri, '/apps/files') !== false || 
		//                strpos($requestUri, '/index.php/apps/files') !== false ||
		//                (isset($_GET['app']) && $_GET['app'] === 'files');
		// 
		// if ($isFilesPage) {
		// 	try {
		// 		Util::addScript(self::APP_ID, 'navigation');
		// 	} catch (\Exception $e) {
		// 		error_log('[Time Archive] Could not load navigation script: ' . $e->getMessage());
		// 	}
		// }
		// 
		// Note: archiveLink is also optional - uncomment if needed after verifying file exists
		// Util::addScript(self::APP_ID, 'archiveLink');
	}
}

