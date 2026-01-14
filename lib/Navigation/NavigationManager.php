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
		// Following the same pattern as Files and Activity apps
		try {
			// Generate URL to our own archive view
			$archiveUrl = $this->urlGenerator->linkToRoute('files_archive.Page.index');

			// Get icon path (relative - Nextcloud will make it absolute)
			$iconPath = $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg');

			error_log('[Files Archive] Registering navigation entry...');
			error_log('[Files Archive] Archive URL: ' . $archiveUrl);
			error_log('[Files Archive] Icon path: ' . $iconPath);

			// Simple navigation entry array (like Files/Activity apps)
			$entry = [
				'id' => Application::APP_ID,
				'order' => 10,
				'href' => $archiveUrl,
				'icon' => $iconPath,
				'name' => $l->t('Archive'),
			];
			
			error_log('[Files Archive] Navigation entry: ' . json_encode($entry));
			
			// Register using closure (standard Nextcloud pattern)
			$this->navigationManager->add(function () use ($entry) {
				return $entry;
			});
			
			error_log('[Files Archive] Navigation entry registered successfully');
		} catch (\Exception $e) {
			error_log('[Files Archive] Failed to register navigation: ' . $e->getMessage());
			error_log('[Files Archive] Stack trace: ' . $e->getTraceAsString());
		} catch (\Throwable $e) {
			error_log('[Files Archive] Fatal error: ' . $e->getMessage());
		}
	}
}
