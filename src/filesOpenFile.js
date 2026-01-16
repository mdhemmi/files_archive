/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Auto-open file after navigating to Files app from Archive view
 * This script runs in the Files app and checks if we need to open a file
 */
function autoOpenFile() {
	// Check URL parameter first
	const urlParams = new URLSearchParams(window.location.search)
	const openFileId = urlParams.get('openfile')
	
	// Check if we have a file to open from Archive view (sessionStorage or URL)
	let fileInfo = null
	const fileInfoStr = sessionStorage.getItem('time_archive_open_file')
	
	if (fileInfoStr) {
		// Remove the flag so it doesn't trigger again
		sessionStorage.removeItem('time_archive_open_file')
		fileInfo = JSON.parse(fileInfoStr)
	} else if (openFileId) {
		// Use file ID from URL parameter
		fileInfo = { id: parseInt(openFileId) }
	} else {
		return
	}
	
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
				// Try to find the file by ID first, then by name
				let fileModel = null
				if (fileInfo.id) {
					fileModel = fileList.files.find(f => f.id === fileInfo.id || String(f.id) === String(fileInfo.id))
				}
				if (!fileModel && fileInfo.name) {
					fileModel = fileList.files.find(f => f.name === fileInfo.name)
				}
				if (!fileModel && fileInfo.path) {
					fileModel = fileList.files.find(f => f.path === fileInfo.path)
				}
				
				if (fileModel) {
					// Try multiple methods to open the file
					let opened = false
					
					// Method 1: Use openFile method with file name
					if (typeof fileList.openFile === 'function') {
						try {
							fileList.openFile(fileModel.name)
							opened = true
							console.log('[Time Archive] Opened file via openFile():', fileModel.name)
						} catch (e) {
							console.warn('[Time Archive] openFile() failed:', e)
						}
					}
					
					// Method 2: Use open method
					if (!opened && typeof fileList.open === 'function') {
						try {
							fileList.open(fileModel.name)
							opened = true
							console.log('[Time Archive] Opened file via open():', fileModel.name)
						} catch (e) {
							console.warn('[Time Archive] open() failed:', e)
						}
					}
					
					// Method 3: Trigger click on file element
					if (!opened && fileModel.$el) {
						try {
							fileModel.$el.click()
							opened = true
							console.log('[Time Archive] Opened file via click():', fileModel.name)
						} catch (e) {
							console.warn('[Time Archive] click() failed:', e)
						}
					}
					
					// Method 4: Use Files app's fileActions
					if (!opened && window.OCA && window.OCA.Files && window.OCA.Files.FileActions) {
						try {
							const fileActions = window.OCA.Files.FileActions
							fileActions.triggerAction('Open', fileModel)
							opened = true
							console.log('[Time Archive] Opened file via FileActions:', fileModel.name)
						} catch (e) {
							console.warn('[Time Archive] FileActions failed:', e)
						}
					}
					
					if (!opened) {
						console.warn('[Time Archive] Could not open file - no working method found')
					}
				} else {
					console.warn('[Time Archive] File not found in file list. ID:', fileInfo.id, 'Name:', fileInfo.name)
					console.log('[Time Archive] Available files:', fileList.files.map(f => ({ id: f.id, name: f.name })))
				}
			}, 1000)
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
