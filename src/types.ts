/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface TagWithId {
	id: number
	displayName: string
	userVisible: boolean
	userAssignable: boolean
	canAssign: boolean
	color?: string
}
