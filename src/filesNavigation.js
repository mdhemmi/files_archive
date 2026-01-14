/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

/**
 * Register Archive navigation entry in Files app
 */
function initFilesNavigation() {
	// Wait for Files app navigation to be available
	if (typeof window.OCA?.Files?.Navigation === 'undefined') {
		// Files app not loaded yet, try again later
		setTimeout(initFilesNavigation, 100)
		return
	}

	try {
		const Navigation = window.OCA.Files.Navigation

		// Register Archive navigation entry
		Navigation.register({
			id: 'archive',
			name: t('files_archive', 'Archive'),
			icon: 'icon-archive',
			order: 10,
			href: generateUrl('/apps/files/?dir=/.archive'),
		})

		console.log('Archive navigation entry registered')
	} catch (error) {
		console.error('Failed to register Archive navigation:', error)
	}
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initFilesNavigation)
} else {
	initFilesNavigation()
}
