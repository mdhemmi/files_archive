/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'

let attempts = 0
const MAX_ATTEMPTS = 50 // Try for up to 10 seconds (50 * 200ms)

/**
 * Register Archive navigation entry in Files app
 */
function initFilesNavigation() {
	attempts++
	
	// Debug logging
	if (attempts === 1) {
		console.log('[Files Archive] Initializing navigation registration...')
		console.log('[Files Archive] window.OCA:', typeof window.OCA)
		console.log('[Files Archive] window.OCA.Files:', typeof window.OCA?.Files)
		console.log('[Files Archive] window.OCA.Files.Navigation:', typeof window.OCA?.Files?.Navigation)
	}

	// Wait for Files app navigation to be available
	let Navigation = null
	
	if (typeof window.OCA?.Files?.Navigation !== 'undefined') {
		Navigation = window.OCA.Files.Navigation
	} else if (typeof window.OC?.Files?.Navigation !== 'undefined') {
		Navigation = window.OC.Files.Navigation
	} else {
		// Files app not loaded yet, try again
		if (attempts < MAX_ATTEMPTS) {
			setTimeout(initFilesNavigation, 200)
		} else {
			console.warn('[Files Archive] Navigation API not found after', attempts, 'attempts')
		}
		return
	}

	try {
		// Try different registration methods
		const navConfig = {
			id: 'archive',
			appName: 'files',
			name: t('files_archive', 'Archive'),
			icon: 'icon-archive',
			order: 10,
			href: generateUrl('/apps/files/?dir=/.archive'),
		}

		console.log('[Files Archive] Attempting to register navigation with config:', navConfig)
		console.log('[Files Archive] Navigation object:', Navigation)
		console.log('[Files Archive] Navigation methods:', Object.keys(Navigation))

		// Try .add() method (older API)
		if (typeof Navigation.add === 'function') {
			Navigation.add(navConfig)
			console.log('[Files Archive] ✓ Navigation entry registered via .add()')
			return
		}

		// Try .register() method (newer API)
		if (typeof Navigation.register === 'function') {
			Navigation.register(navConfig)
			console.log('[Files Archive] ✓ Navigation entry registered via .register()')
			return
		}

		// Try direct assignment if it's an array
		if (Array.isArray(Navigation)) {
			Navigation.push(navConfig)
			console.log('[Files Archive] ✓ Navigation entry added to array')
			return
		}

		console.warn('[Files Archive] Navigation API found but no supported registration method')
	} catch (error) {
		console.error('[Files Archive] Failed to register navigation:', error)
	}
}

// Initialize when DOM is ready and Files app might be loaded
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		setTimeout(initFilesNavigation, 500)
	})
} else {
	// Start trying immediately and also after delays
	setTimeout(initFilesNavigation, 500)
	setTimeout(initFilesNavigation, 1000)
	setTimeout(initFilesNavigation, 2000)
}
