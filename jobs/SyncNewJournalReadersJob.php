<?php

declare(strict_types=1);

/**
 * IMSYP Auto Reader
 *
 * Copyright (c) 2026 IMSYP
 * Developer: Younouss EL ouati
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace APP\plugins\generic\imsypAutoReader\jobs;

use APP\core\Application;
use APP\facades\Repo;
use PKP\jobs\BaseJob;
use PKP\security\Role;
use PKP\user\Collector as UserCollector;

/**
 * Synchronize active users with the default Reader group
 * of a journal.
 *
 * Large installations are split into bounded queue batches.
 */
class SyncNewJournalReadersJob extends BaseJob
{
    private const BATCH_SIZE = 250;

    private int $contextId;

    /**
     * Null means coordinator mode.
     *
     * A non-null array contains the user IDs assigned
     * to one processing batch.
     *
     * @var int[]|null
     */
    private ?array $userIds;

    public function __construct(
        int $contextId,
        ?array $userIds = null
    ) {
        parent::__construct();

        $this->contextId = $contextId;
        $this->userIds = $userIds;

        $this->timeout = 600;
        $this->backoff = 10;
    }

    public function handle(): void
    {
        if ($this->contextId <= 0) {
            return;
        }

        if (
            !Application::getContextDAO()->exists(
                $this->contextId
            )
        ) {
            return;
        }

        $readerGroup = Repo::userGroup()
            ->getByRoleIds(
                [Role::ROLE_ID_READER],
                $this->contextId,
                true
            )
            ->first();

        if (!$readerGroup) {
            throw new \RuntimeException(
                'Default Reader group is unavailable.'
            );
        }

        /*
         * Coordinator mode.
         *
         * Only user IDs are collected. Large installations are
         * divided into bounded queue jobs instead of processing
         * every account in one long-running job.
         */
        if ($this->userIds === null) {
            $userIds = Repo::user()
                ->getCollector()
                ->filterByStatus(UserCollector::STATUS_ACTIVE)
                ->getIds()
                ->map(
                    static fn ($userId): int =>
                        (int) $userId
                )
                ->filter(
                    static fn (int $userId): bool =>
                        $userId > 0
                )
                ->values();

            if ($userIds->isEmpty()) {
                return;
            }

            /*
             * Small installations can finish in this job
             * without another queue round-trip.
             */
            if ($userIds->count() <= self::BATCH_SIZE) {
                $this->userIds = $userIds->all();
            } else {
                foreach (
                    $userIds->chunk(self::BATCH_SIZE)
                    as $batch
                ) {
                    dispatch(
                        new self(
                            $this->contextId,
                            $batch->values()->all()
                        )
                    );
                }

                return;
            }
        }

        if (!$this->userIds) {
            return;
        }

        /*
         * Check account status again when the batch executes.
         * A user disabled after the coordinator ran will
         * therefore still be excluded.
         */
        $users = Repo::user()
            ->getCollector()
            ->filterByStatus(UserCollector::STATUS_ACTIVE)
            ->filterByUserIds($this->userIds)
            ->getMany();

        $readerGroupId = (int) $readerGroup->id;

        foreach ($users as $user) {
            $userId = (int) $user->getId();

            if ($userId <= 0) {
                continue;
            }

            if (
                Repo::userGroup()->userInGroup(
                    $userId,
                    $readerGroupId
                )
            ) {
                continue;
            }

            Repo::userGroup()->assignUserToGroup(
                $userId,
                $readerGroupId
            );
        }
    }
}
