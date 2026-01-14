/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileStat } from 'webdav'
import type { TagWithId } from './types'

export const parseTags = (tags: Required<FileStat>[]): TagWithId[] => {
	return tags.map((tag) => ({
		id: parseInt(tag.props['{http://owncloud.org/ns}id'] as string, 10),
		displayName: tag.props['{http://owncloud.org/ns}display-name'] as string,
		userVisible: tag.props['{http://owncloud.org/ns}user-visible'] === 'true',
		userAssignable: tag.props['{http://owncloud.org/ns}user-assignable'] === 'true',
		canAssign: tag.props['{http://owncloud.org/ns}can-assign'] === 'true',
		color: tag.props['{http://nextcloud.org/ns}color'] as string | undefined,
	}))
}
