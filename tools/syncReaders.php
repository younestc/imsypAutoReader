<?php

/**
 * IMSYP Auto Reader - CLI Synchronization Tool
 *
 * Copyright (c) 2026 IMSYP
 * Developer: Younouss EL ouati
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * CLI ONLY.
 */

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use PKP\security\Role;
use PKP\user\Collector as UserCollector;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

/*
 * Normal installed location:
 * OJS_ROOT/plugins/generic/imsypAutoReader/tools/syncReaders.php
 *
 * For development/testing outside the OJS directory:
 * OJS_ROOT=/path/to/ojs php tools/syncReaders.php --dry-run
 */
$configuredRoot = getenv('OJS_ROOT');

if ($configuredRoot !== false && $configuredRoot !== '') {
    $ojsRoot = realpath($configuredRoot);
} else {
    $ojsRoot = realpath(dirname(__DIR__, 4));
}

if (
    !$ojsRoot ||
    !is_file($ojsRoot . '/index.php') ||
    !is_file($ojsRoot . '/lib/pkp/includes/bootstrap.php')
) {
    fwrite(
        STDERR,
        "ERROR: OJS root could not be detected.\n" .
        "Set OJS_ROOT to the OJS installation directory.\n"
    );
    exit(1);
}

/*
 * OJS bootstrap uses several paths relative to the installation root.
 * Change the working directory before loading it.
 */
if (!chdir($ojsRoot)) {
    fwrite(STDERR, "ERROR: Unable to enter the OJS installation directory.\n");
    exit(1);
}

define('INDEX_FILE_LOCATION', $ojsRoot . '/index.php');

require $ojsRoot . '/lib/pkp/includes/bootstrap.php';

$dryRun = in_array('--dry-run', $argv, true);
$apply = in_array('--apply', $argv, true);

if ($dryRun === $apply) {
    fwrite(
        STDERR,
        "Usage:\n" .
        "  php tools/syncReaders.php --dry-run\n" .
        "  php tools/syncReaders.php --apply\n"
    );
    exit(2);
}

/*
 * Collect Reader groups for all enabled journals.
 */
$contexts = Application::getContextDAO()->getAll(true);
$readerGroups = [];

while ($context = $contexts->next()) {
    $contextId = (int) $context->getId();

    if ($contextId <= 0) {
        continue;
    }

    $readerGroup = Repo::userGroup()
        ->getByRoleIds(
            [Role::ROLE_ID_READER],
            $contextId,
            true
        )
        ->first();

    if (!$readerGroup) {
        fwrite(
            STDERR,
            "ERROR: Default Reader group missing for context {$contextId}.\n"
        );
        exit(1);
    }

    $readerGroups[$contextId] = (int) $readerGroup->id;
}

if (!$readerGroups) {
    fwrite(STDERR, "ERROR: No enabled journals found.\n");
    exit(1);
}

/*
 * Explicitly select active users only.
 *
 * getMany() is lazy in OJS 3.5, so the complete user table
 * does not need to be loaded into memory at once.
 */
$users = Repo::user()
    ->getCollector()
    ->filterByStatus(UserCollector::STATUS_ACTIVE)
    ->getMany();

$usersProcessed = 0;
$usersChanged = 0;
$assignments = 0;

try {
    if ($apply) {
        DB::beginTransaction();
    }

    foreach ($users as $user) {
        $userId = (int) $user->getId();

        if ($userId <= 0) {
            continue;
        }

        $usersProcessed++;
        $changed = false;

        foreach ($readerGroups as $readerGroupId) {
            if (
                Repo::userGroup()->userInGroup(
                    $userId,
                    $readerGroupId
                )
            ) {
                continue;
            }

            $assignments++;
            $changed = true;

            if ($apply) {
                Repo::userGroup()->assignUserToGroup(
                    $userId,
                    $readerGroupId
                );
            }
        }

        if ($changed) {
            $usersChanged++;
        }
    }

    if ($apply) {
        DB::commit();
    }
} catch (\Throwable $e) {
    if ($apply) {
        DB::rollBack();
    }

    /*
     * Intentionally avoid exposing SQL, credentials,
     * paths, usernames or other sensitive information.
     */
    fwrite(
        STDERR,
        "ERROR: Synchronization failed (" .
        get_class($e) .
        ").\n"
    );

    exit(1);
}

echo $dryRun ? "Mode: DRY RUN\n" : "Mode: APPLY\n";
echo "Enabled journals: " . count($readerGroups) . "\n";
echo "Active users processed: {$usersProcessed}\n";
echo "Users requiring changes: {$usersChanged}\n";
echo "Reader assignments: {$assignments}\n";

if ($dryRun) {
    echo "No database changes were made.\n";
} else {
    echo "Synchronization completed successfully.\n";
}
