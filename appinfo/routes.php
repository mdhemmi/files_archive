<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'ocs' => [
		['name' => 'API#getArchiveRules', 'url' => '/api/v1/rules', 'verb' => 'GET'],
		['name' => 'API#createArchiveRule', 'url' => '/api/v1/rules', 'verb' => 'POST'],
		['name' => 'API#deleteArchiveRule', 'url' => '/api/v1/rules/{id}', 'verb' => 'DELETE'],
		['name' => 'API#runArchiveJob', 'url' => '/api/v1/run', 'verb' => 'POST'],
	],
];
