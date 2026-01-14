<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Archive\Settings;

use OCA\Files_Archive\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	public function __construct(
		protected readonly IInitialState $initialState,
		protected readonly IURLGenerator $url,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		Util::addScript('files_archive', 'files_archive-main');

		$this->initialState->provideInitialState(
			'doc-url',
			$this->url->linkToDocs('admin-files-archive')
		);

		return new TemplateResponse('files_archive', 'admin', [], '');
	}

	#[\Override]
	public function getSection(): string {
		return 'workflow';
	}

	#[\Override]
	public function getPriority(): int {
		return 80;
	}
}
