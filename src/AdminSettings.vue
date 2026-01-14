<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->
<template>
	<NcSettingsSection :name="t('files_archive', 'File Archive')"
		:doc-url="docUrl"
		:description="t('files_archive', 'Automatically archive files based on system tags and file age. Archived files are moved to the .archive folder, which is hidden from mobile apps but accessible via the web interface.')">
		
		<!-- Existing Rules -->
		<div v-if="archiveRules.length > 0" class="archive-rules-list">
			<h3 class="archive-section-title">
				{{ t('files_archive', 'Active archive rules') }}
			</h3>
			<div class="archive-rules-grid">
				<ArchiveRule v-for="rule in archiveRules"
					:key="rule.id"
					:tags="tags"
					v-bind="rule" />
			</div>
		</div>

		<!-- Create New Rule Form -->
		<div class="archive-form-container">
			<h3 class="archive-section-title">
				{{ archiveRules.length > 0 ? t('files_archive', 'Create new archive rule') : t('files_archive', 'Create your first archive rule') }}
			</h3>
			
			<div class="archive-form">
				<!-- Tag Selection -->
				<div class="archive-form__field">
					<label class="archive-form__label">
						{{ t('files_archive', 'Files tagged with') }}
					</label>
					<NcSelectTags v-model="newTag"
						:disabled="loading"
						:multiple="false"
						:clearable="false"
						:options-filter="filterAvailableTagList"
						class="archive-form__input" />
					<p class="archive-form__hint">
						{{ t('files_archive', 'Select a system tag. Files with this tag will be archived according to the rule.') }}
					</p>
				</div>

				<!-- Archive Time -->
				<div class="archive-form__field-group">
					<div class="archive-form__field archive-form__field--time">
						<label class="archive-form__label">
							{{ t('files_archive', 'Archive after') }}
						</label>
						<div class="archive-form__time-inputs">
							<NcTextField v-model="newAmount"
								:disabled="loading"
								type="number"
								min="1"
								:label="t('files_archive', 'Amount')"
								:aria-label="t('files_archive', 'Number of time units')"
								class="archive-form__time-amount" />
							<NcSelect v-model="newUnit"
								:disabled="loading"
								:options="unitOptions"
								:allow-empty="false"
								:clearable="false"
								track-by="id"
								label="label"
								class="archive-form__time-unit" />
						</div>
						<p class="archive-form__hint">
							{{ t('files_archive', 'How long to keep files before archiving them.') }}
						</p>
					</div>

					<div class="archive-form__field">
						<label class="archive-form__label">
							{{ t('files_archive', 'Calculate from') }}
						</label>
						<NcSelect v-model="newAfter"
							:disabled="loading"
							:options="afterOptions"
							:allow-empty="false"
							:clearable="false"
							track-by="id"
							label="label"
							class="archive-form__input" />
						<p class="archive-form__hint">
							{{ t('files_archive', 'The date to use as the starting point for the archive period.') }}
						</p>
					</div>
				</div>

				<!-- Info Box -->
				<div class="archive-form__info-box">
					<Archive :size="20" class="archive-form__info-icon" />
					<p class="archive-form__info-text">
						{{ t('files_archive', 'Files will be moved to the .archive folder, which is automatically hidden from mobile apps. You can access archived files through the web interface at any time.') }}
					</p>
				</div>

				<!-- Submit Button -->
				<div class="archive-form__actions">
					<NcButton variant="primary"
						type="button"
						:disabled="loading || newTag === null || newTag < 0"
						:aria-label="createLabel"
						@click="onClickCreate">
						<template #icon>
							<Plus :size="20" />
						</template>
						{{ t('files_archive', 'Create archive rule') }}
					</NcButton>
				</div>
			</div>
		</div>
	</NcSettingsSection>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSelectTags from '@nextcloud/vue/components/NcSelectTags'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import Plus from 'vue-material-design-icons/Plus.vue'
import Archive from 'vue-material-design-icons/Archive.vue'

import ArchiveRule from './Components/ArchiveRule.vue'
import { fetchTags } from './services/api.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'

export default {
	name: 'AdminSettings',

	components: {
		NcButton,
		NcSelect,
		NcSelectTags,
		ArchiveRule,
		NcSettingsSection,
		NcTextField,
		Plus,
		Archive,
	},

	data() {
		return {
			loading: true,
			docUrl: loadState('files_archive', 'doc-url'),

			unitOptions: [
				{ id: 0, label: t('files_archive', 'Days') },
				{ id: 1, label: t('files_archive', 'Weeks') },
				{ id: 2, label: t('files_archive', 'Months') },
				{ id: 3, label: t('files_archive', 'Years') },
			],
			newUnit: {},

			afterOptions: [
				{ id: 0, label: t('files_archive', 'Creation date') },
				{ id: 1, label: t('files_archive', 'Last modification date') },
			],
			newAfter: {},

			newAmount: '90',

			newTag: null,
			filterAvailableTagList: (tag) => {
				return !this.tagIdsWithRule.includes(tag.id)
			},
			tags: [],
		}
	},

	computed: {
		archiveRules() {
			return this.$store.getters.getArchiveRules()
		},

		tagIdsWithRule() {
			return this.$store.getters.getTagIdsWithRule()
		},

		createLabel() {
			return t('files_archive', 'Create new archive rule')
		},
	},

	async mounted() {
		try {
			this.tags = await fetchTags()
			await this.$store.dispatch('loadArchiveRules')

			this.resetForm()

			this.loading = false
		} catch (e) {
			showError(t('files_archive', 'An error occurred while loading the existing archive rules'))
			console.error(e)
		}
	},

	methods: {
		t,

		async onClickCreate() {
			const newTag = this.newTag?.id ?? this.newTag
			const newUnit = this.newUnit?.id ?? this.newUnit
			const newAfter = this.newAfter?.id ?? this.newAfter
			const newAmount = parseInt(this.newAmount, 10)

			if (newTag === null || newTag < 0) {
				showError(t('files_archive', 'Please select a tag'))
				return
			}

			const tagName = this.tags.find((tag) => tag.id === newTag)?.displayName

			if (this.tagIdsWithRule.includes(newTag)) {
				showError(t('files_archive', 'Tag {tagName} already has an archive rule', { tagName }))
				return
			}

			if (newUnit < 0 || newUnit > 3) {
				showError(t('files_archive', 'Invalid time unit'))
				return
			}

			if (newAfter < 0 || newAfter > 1) {
				showError(t('files_archive', 'Invalid date option'))
				return
			}

			if (isNaN(newAmount) || newAmount < 1) {
				showError(t('files_archive', 'Please enter a valid archive period (at least 1)'))
				return
			}

			try {
				const ruleData = {
					tagid: newTag,
					timeamount: newAmount,
					timeunit: newUnit,
					timeafter: newAfter,
				}

				await this.$store.dispatch('createNewRule', ruleData)

				showSuccess(t('files_archive', 'Archive rule for tag {tagName} has been created', { tagName }))
				this.resetForm()
			} catch (e) {
				showError(t('files_archive', 'Failed to create archive rule for tag {tagName}', { tagName }))
				console.error(e)
			}
		},

		resetForm() {
			this.newTag = null
			this.newAmount = '90'
			this.newUnit = this.unitOptions[0]
			this.newAfter = this.afterOptions[0]
		},
	},
}
</script>

<style scoped lang="scss">
.archive-section-title {
	font-size: 1.2em;
	font-weight: 600;
	margin: 24px 0 16px 0;
	color: var(--color-main-text);
	
	&:first-child {
		margin-top: 0;
	}
}

.archive-rules-list {
	margin-bottom: 32px;
}

.archive-rules-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 16px;
	margin-bottom: 8px;
}

.archive-form-container {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 24px;
	margin-bottom: 24px;
}

.archive-form {
	display: flex;
	flex-direction: column;
	gap: 24px;
}

.archive-form__field {
	display: flex;
	flex-direction: column;
	gap: 8px;

	&--time {
		flex: 1;
	}
}

.archive-form__field-group {
	display: grid;
	grid-template-columns: 2fr 1fr;
	gap: 16px;

	@media (max-width: 768px) {
		grid-template-columns: 1fr;
	}
}

.archive-form__label {
	font-weight: 600;
	color: var(--color-main-text);
	font-size: 0.95em;
}

.archive-form__input {
	width: 100%;
}

.archive-form__time-inputs {
	display: grid;
	grid-template-columns: 100px 1fr;
	gap: 12px;
	align-items: end;
}

.archive-form__time-amount {
	:deep(.input-field__input) {
		text-align: right;
	}
}

.archive-form__hint {
	font-size: 0.9em;
	color: var(--color-text-maxcontrast);
	margin: 0;
	line-height: 1.4;
}

.archive-form__info-box {
	display: flex;
	gap: 12px;
	align-items: flex-start;
	padding: 16px;
	background: var(--color-primary-element-light);
	border-radius: var(--border-radius);
	margin-top: 8px;
}

.archive-form__info-icon {
	flex-shrink: 0;
	margin-top: 2px;
	color: var(--color-primary-element);
}

.archive-form__info-text {
	margin: 0;
	font-size: 0.9em;
	color: var(--color-main-text);
	line-height: 1.5;
}

.archive-form__actions {
	display: flex;
	justify-content: flex-end;
	margin-top: 8px;
}
</style>
