/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createClient, WebDAVClient } from 'webdav'

import { getRootUrl } from '@nextcloud/router'

export const davClient: WebDAVClient = createClient(getRootUrl() + '/remote.php/dav', {
	username: undefined,
	password: undefined,
})
