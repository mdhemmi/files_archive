/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Auto-open file after navigating to Files app from Archive view
 * This script runs in the Files app and checks if we need to open a file
 */
function autoOpenFile() {
	// Check if we have a file to open from Archive view
	const fileInfoStr = sessionStorage.getItem('time_archive_open_file')
	if (!fileInfoStr) {
		return
	}
	
	// Remove the flag so it doesn't trigger again
	sessionStorage.removeItem('time_archive_open_file')
	
	const fileInfo = JSON.parse(fileInfoStr)
	
	// Wait for Files app to be fully loaded
	const tryOpen = (attempts = 0) => {
		if (attempts > 50) {
			console.warn('[Time Archive] Files app did not load in time to open file')
			return
		}
		
		// Check if Files app is loaded
		if (window.OCA && window.OCA.Files && window.OCA.Files.App && window.OCA.Files.App.fileList) {
			const fileList = window.OCA.Files.App.fileList
			
			// Wait a bit more for file list to be populated
			setTimeout(() => {
				// Try to find the file by name or ID
				const fileModel = fileList.files.find(f => 
					f.name === fileInfo.name || 
					f.id === fileInfo.id ||
					f.path === fileInfo.path
				)
				
				if (fileModel) {
					// Open the file using Files app's openFile method
					if (typeof fileList.openFile === 'function') {
						fileList.openFile(fileModel.name)
					} else if (typeof fileList.open === 'function') {
						fileList.open(fileModel.name)
					} else if (fileModel.$el) {
						// Fallback: click on the file element
						fileModel.$el.click()
					}
					console.log('[Time Archive] Opened file:', fileInfo.name)
				} else {
					console.warn('[Time Archive] File not found in file list:', fileInfo.name)
				}
			}, 500)
		} else {
			// Try again after a short delay
			setTimeout(() => tryOpen(attempts + 1), 100)
		}
	}
	
	// Start trying after a short delay to let Files app initialize
	setTimeout(() => tryOpen(), 500)
}

// Run when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', autoOpenFile)
} else {
	autoOpenFile()
}

// Also listen for Files app load events
if (typeof window.addEventListener === 'function') {
	window.addEventListener('OCA.Files.App.loaded', autoOpenFile)
	window.addEventListener('OCA.Files.loaded', autoOpenFile)
	window.addEventListener('files:app:loaded', autoOpenFile)
}
