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
		$this->navigationManager->add(function () use ($l) {
			// Generate URL to Files app with .archive directory
			$archiveUrl = $this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->linkTo('', 'index.php/apps/files') . '?dir=/.archive'
			);

			return [
				'id' => Application::APP_ID,
				'order' => 10,
				'href' => $archiveUrl,
				'icon' => $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg'),
				'name' => $l->t('Archive'),
				'app' => Application::APP_ID,
			];
		});
	}
}
