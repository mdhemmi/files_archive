<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Time_Archive\Navigation;

use OCA\Time_Archive\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

class NavigationManager {
	public function __construct(
		private IURLGenerator $urlGenerator,
		private IFactory $l10nFactory,
	) {
		// Note: INavigationManager doesn't exist in all Nextcloud versions
		// Top navigation is typically handled automatically by Nextcloud when an app has a main route
	}

	public function register(): void {
		// Note: INavigationManager doesn't exist in all Nextcloud versions
		// Top navigation bar entries are typically handled automatically by Nextcloud
		// when an app has a main route defined in routes.php
		// 
		// The app should appear in top navigation automatically if:
		// 1. The app has a main route (which we do: PageController::index)
		// 2. The route is properly registered in appinfo/routes.php
		// 3. The app is enabled
		//
		// No manual registration needed - Nextcloud handles it automatically
		error_log('[Time Archive] NavigationManager::register() called - top navigation is handled automatically by Nextcloud');
	}
}
