<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Archive\Controller;

use OCA\Files_Archive\BackgroundJob\ArchiveJob;
use OCA\Files_Archive\Constants;
use OCA\Files_Archive\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagNotFoundException;

/**
 * @psalm-import-type Files_ArchiveRule from ResponseDefinitions
 */
class APIController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IDBConnection $db,
		private readonly ISystemTagManager $tagManager,
		private readonly IJobList $jobList,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List archive rules
	 *
	 * @return DataResponse<Http::STATUS_OK, list<Files_ArchiveRule>, array{}>
	 *
	 * 200: List archive rules
	 */
	public function getArchiveRules(): DataResponse {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('archive_rules')
			->orderBy('id');

		$cursor = $qb->executeQuery();

		$result = $tagIds = [];
		while ($data = $cursor->fetch()) {
			$tagIds[] = (string)$data['tag_id'];
			$hasJob = $this->jobList->has(ArchiveJob::class, ['tag' => (int)$data['tag_id']]);
			if (!$hasJob) {
				$this->jobList->add(ArchiveJob::class, ['tag' => (int)$data['tag_id']]);
			}

			$result[] = [
				'id' => (int)$data['id'],
				'tagid' => (int)$data['tag_id'],
				'timeunit' => (int)$data['time_unit'],
				'timeamount' => (int)$data['time_amount'],
				'timeafter' => (int)$data['time_after'],
				'hasJob' => true,
			];
		}
		$cursor->closeCursor();

		try {
			$this->tagManager->getTagsByIds($tagIds);
		} catch (TagNotFoundException $e) {
			$missingTags = array_map('intval', $e->getMissingTags());

			$result = array_values(array_filter($result, static function (array $rule) use ($missingTags): bool {
				return !in_array($rule['tagid'], $missingTags, true);
			}));
		}

		return new DataResponse($result);
	}

	/**
	 * Create an archive rule
	 *
	 * @param int $tagid Tag the archive rule is based on
	 * @param 0|1|2|3 $timeunit Time unit (days, weeks, months, years)
	 * @psalm-param Constants::UNIT_* $timeunit
	 * @param positive-int $timeamount Amount of time units
	 * @param 0|1 $timeafter Whether archive time is based on creation time (0) or modification time (1)
	 * @psalm-param Constants::MODE_* $timeafter
	 * @return DataResponse<Http::STATUS_BAD_REQUEST, array{error: 'tagid'|'timeunit'|'timeamount'|'timeafter'}, array{}>|DataResponse<Http::STATUS_CREATED, Files_ArchiveRule, array{}>
	 *
	 * 201: Archive rule created
	 * 400: At least one of the parameters was invalid
	 */
	public function createArchiveRule(int $tagid, int $timeunit, int $timeamount, int $timeafter = Constants::MODE_CTIME): DataResponse {
		try {
			$this->tagManager->getTagsByIds((string)$tagid);
		} catch (\InvalidArgumentException) {
			return new DataResponse(['error' => 'tagid'], Http::STATUS_BAD_REQUEST);
		}

		if ($timeunit < 0 || $timeunit > 3) {
			return new DataResponse(['error' => 'timeunit'], Http::STATUS_BAD_REQUEST);
		}
		if ($timeamount < 1) {
			return new DataResponse(['error' => 'timeamount'], Http::STATUS_BAD_REQUEST);
		}
		if ($timeafter < 0 || $timeafter > 1) {
			return new DataResponse(['error' => 'timeafter'], Http::STATUS_BAD_REQUEST);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->insert('archive_rules')
			->setValue('tag_id', $qb->createNamedParameter($tagid))
			->setValue('time_unit', $qb->createNamedParameter($timeunit))
			->setValue('time_amount', $qb->createNamedParameter($timeamount))
			->setValue('time_after', $qb->createNamedParameter($timeafter));

		$qb->executeStatement();
		$id = $qb->getLastInsertId();

		// Insert background job
		$this->jobList->add(ArchiveJob::class, ['tag' => $tagid]);

		return new DataResponse([
			'id' => $id,
			'tagid' => $tagid,
			'timeunit' => $timeunit,
			'timeamount' => $timeamount,
			'timeafter' => $timeafter,
			'hasJob' => true,
		], Http::STATUS_CREATED);
	}

	/**
	 * Delete an archive rule
	 *
	 * @param int $id Archive rule to delete
	 * @return DataResponse<Http::STATUS_NO_CONTENT|Http::STATUS_NOT_FOUND, list<empty>, array{}>
	 *
	 * 204: Archive rule deleted
	 * 404: Archive rule not found
	 */
	public function deleteArchiveRule(int $id): DataResponse {
		$qb = $this->db->getQueryBuilder();

		// Fetch tag_id
		$qb->select('tag_id')
			->from('archive_rules')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->setMaxResults(1);
		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		// Remove from archive_rules db
		$qb = $this->db->getQueryBuilder();
		$qb->delete('archive_rules')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
		$qb->executeStatement();

		// Remove background job
		$this->jobList->remove(ArchiveJob::class, ['tag' => (int)$data['tag_id']]);

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}
}
