<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Archive\Navigation;

use OCA\Files_Archive\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Navigation\INavigationManager;

class NavigationManager {
	public function __construct(
		private INavigationManager $navigationManager,
		private IURLGenerator $urlGenerator,
		private IFactory $l10nFactory,
	) {
	}

	public function register(): void {
		$l = $this->l10nFactory->get(Application::APP_ID);

		// Register navigation entry in top navigation bar
		try {
			// Generate URL to Files app with .archive directory
			$archiveUrl = $this->urlGenerator->linkToRoute('files.view.index') . '?dir=/.archive';

			// Get icon path (relative is fine, Nextcloud will make it absolute)
			$iconPath = $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg');

			// Register using closure (allows lazy evaluation)
			$this->navigationManager->add(function () use ($l, $archiveUrl, $iconPath) {
				return [
					'id' => Application::APP_ID,
					'order' => 10,
					'href' => $archiveUrl,
					'icon' => $iconPath,
					'name' => $l->t('Archive'),
					'app' => Application::APP_ID,
				];
			});
			
			error_log('[Files Archive] Navigation entry registered successfully');
			error_log('[Files Archive] Archive URL: ' . $archiveUrl);
			error_log('[Files Archive] Icon path: ' . $iconPath);
		} catch (\Exception $e) {
			error_log('[Files Archive] Failed to register navigation: ' . $e->getMessage());
			error_log('[Files Archive] Stack trace: ' . $e->getTraceAsString());
		}
	}
}
