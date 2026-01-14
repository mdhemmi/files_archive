/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createStore } from 'vuex'
import archiveStore from './archiveStore.js'

export default createStore({
	modules: {
		archive: archiveStore,
	},
})
