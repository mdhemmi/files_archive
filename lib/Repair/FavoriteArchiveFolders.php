<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Archive\Repair;

use OCA\Files_Archive\Constants;
use Exception;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;

/**
 * Repair step to add existing .archive folders to favorites
 */
class FavoriteArchiveFolders implements IRepairStep {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IRootFolder $rootFolder,
		private readonly ISystemTagManager $tagManager,
		private readonly ISystemTagObjectMapper $tagMapper,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Add existing .archive folders to favorites';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$output->info('Adding existing .archive folders to favorites...');
		
		$processed = 0;
		$added = 0;
		$errors = 0;
		
		// Iterate through all users
		$this->userManager->callForAllUsers(function (IUser $user) use (&$processed, &$added, &$errors, $output) {
			$userId = $user->getUID();
			$processed++;
			
			try {
				$userFolder = $this->rootFolder->getUserFolder($userId);
				
				// Check if .archive folder exists
				try {
					$archiveNode = $userFolder->get(Constants::ARCHIVE_FOLDER);
					if (!$archiveNode instanceof Folder) {
						// Not a folder, skip
						return;
					}
					
					// Check if already favorited
					if ($this->isFavorited($archiveNode)) {
						$output->debug("User {$userId}: .archive folder already favorited");
						return;
					}
					
					// Add to favorites
					$this->addToFavorites($archiveNode);
					$added++;
					$output->info("Added .archive folder to favorites for user: {$userId}");
					
				} catch (NotFoundException $e) {
					// No .archive folder for this user, skip
					$output->debug("User {$userId}: No .archive folder found");
				}
			} catch (Exception $e) {
				$errors++;
				$output->warning("Failed to process user {$userId}: " . $e->getMessage());
				$this->logger->error('Failed to add .archive folder to favorites for user ' . $userId, [
					'exception' => $e,
				]);
			}
		});
		
		$output->info("Processed {$processed} users, added {$added} folders to favorites, {$errors} errors");
	}
	
	/**
	 * Check if a folder is already favorited
	 */
	private function isFavorited(Folder $folder): bool {
		try {
			$fileId = $folder->getId();
			$tags = $this->tagMapper->getTagIdsForObjects([$fileId], 'files');
			
			if (empty($tags[$fileId])) {
				return false;
			}
			
			// Check if any of the tags is a favorite tag
			$allTags = $this->tagManager->getAllTags('files');
			foreach ($allTags as $tag) {
				$tagName = $tag->getName();
				if (($tagName === '$user!favorite' || 
				     $tagName === 'favorite' ||
				     (strpos($tagName, 'favorite') !== false && $tag->isUserVisible() && $tag->isUserAssignable())) &&
				    in_array((string)$tag->getId(), $tags[$fileId])) {
					return true;
				}
			}
			
			return false;
		} catch (Exception $e) {
			// If we can't check, assume not favorited
			return false;
		}
	}
	
	/**
	 * Add the archive folder to favorites
	 * Same logic as in ArchiveJob::addToFavorites()
	 */
	private function addToFavorites(Folder $archiveFolder): void {
		try {
			$fileId = $archiveFolder->getId();
			$userId = $archiveFolder->getOwner()->getUID();
			
			// Try to find existing favorite tags
			$allTags = $this->tagManager->getAllTags('files');
			$favoriteTag = null;
			
			foreach ($allTags as $tag) {
				$tagName = $tag->getName();
				// Check for favorite tag patterns used in Nextcloud
				if ($tagName === '$user!favorite' || 
				    $tagName === 'favorite' ||
				    (strpos($tagName, 'favorite') !== false && $tag->isUserVisible() && $tag->isUserAssignable())) {
					$favoriteTag = $tag;
					break;
				}
			}
			
			// If no favorite tag exists, try to create one
			if ($favoriteTag === null) {
				try {
					// Try to create a favorite tag
					$favoriteTag = $this->tagManager->createTag('favorite', true, true);
					$this->logger->debug('Created favorite tag for archive folder');
				} catch (Exception $e) {
					// If we can't create the tag, log and continue
					$this->logger->debug('Could not create favorite tag: ' . $e->getMessage());
					error_log('Files Archive: Could not add archive folder to favorites automatically for user ' . $userId);
					return;
				}
			}
			
			// Assign the favorite tag to the archive folder
			$this->tagMapper->assignTags($fileId, 'files', [(string)$favoriteTag->getId()]);
			$this->logger->info('Added archive folder to favorites (file ID: ' . $fileId . ', user: ' . $userId . ')');
			error_log('Files Archive: Added .archive folder to favorites for user ' . $userId);
		} catch (Exception $e) {
			// Log but don't fail - favorite assignment is best effort
			$this->logger->warning('Failed to add archive folder to favorites: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			error_log('Files Archive: Failed to add archive folder to favorites: ' . $e->getMessage());
		}
	}
}
