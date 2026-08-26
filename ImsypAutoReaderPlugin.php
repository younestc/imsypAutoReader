<?php

/**
 * IMSYP Auto Reader
 *
 * Copyright (c) 2026 IMSYP
 * Developer: Younouss EL ouati
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

namespace APP\plugins\generic\imsypAutoReader;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\imsypAutoReader\jobs\SyncNewJournalReadersJob;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RedirectAction;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;

class ImsypAutoReaderPlugin extends GenericPlugin
{
    /**
     * User currently being created through the frontend registration form.
     *
     * This prevents the registration-specific hook from changing users
     * created through unrelated workflows.
     */
    private $registrationUser = null;

    /**
     * Whether the registering user selected Reviewer during registration.
     */
    private bool $registrationHasReviewer = false;

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if (Application::isUnderMaintenance()) {
            return $success;
        }

        if ($success && $this->getEnabled()) {
            /*
             * Frontend registration hooks.
             */
            Hook::add(
                'registrationform::execute',
                $this->prepareRegistration(...)
            );

            Hook::add(
                'User::add',
                $this->assignReaderToAllJournals(...)
            );

            /*
             * Fired after OJS has created a new journal and installed
             * its default user groups.
             */
            Hook::add(
                'Context::add',
                $this->queueNewJournalSynchronization(...)
            );

            /*
             * Synchronize users when an existing journal changes
             * from disabled to enabled.
             */
            Hook::add(
                'Context::edit',
                $this->queueSynchronizationWhenJournalEnabled(...)
            );
        }

        return $success;
    }

    public function isSitePlugin()
    {
        return true;
    }

    public function getDisplayName()
    {
        return 'IMSYP Auto Reader';
    }

    public function getDescription()
    {
        return 'Automatically assigns the Reader role across journals for new registrations and synchronizes active users when a new journal is created. Developed by IMSYP.';
    }

    /**
     * Add the official IMSYP project link to Plugin Manager.
     */
    public function getActions($request, $verb): array
    {
        $actions = parent::getActions($request, $verb);

        array_unshift(
            $actions,
            new LinkAction(
                'imsypOfficialWebsite',
                new RedirectAction(
                    'https://www.imsyp.com/index.php/index/imsyp-auto-reader',
                    '_blank'
                ),
                'Official Website',
                null
            )
        );

        return $actions;
    }

    /**
     * Prepare a frontend registration so that the new account is assigned
     * to the default Reader group of every enabled journal.
     */
    public function prepareRegistration($hookName, $args)
    {
        $form = $args[0] ?? null;

        if (!$form || !isset($form->user)) {
            return Hook::CONTINUE;
        }

        $this->registrationUser = $form->user;
        $this->registrationHasReviewer =
            (bool) $form->getData('reviewerGroup');

        $readerGroups = (array) $form->getData('readerGroup');

        $contexts = Application::getContextDAO()->getAll(true);

        while ($context = $contexts->next()) {
            $readerGroup = $this->getDefaultReaderGroup(
                (int) $context->getId()
            );

            if ($readerGroup) {
                $readerGroups[$readerGroup->id] = 1;
            }
        }

        $form->setData('readerGroup', $readerGroups);

        return Hook::CONTINUE;
    }

    /**
     * Assign a newly registered frontend user to Reader in every
     * currently enabled journal.
     */
    public function assignReaderToAllJournals($hookName, $args)
    {
        $user = $args[0] ?? null;

        if (
            !$user ||
            !$user->getId() ||
            $this->registrationUser !== $user
        ) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $currentContext = $request->getContext();

        $currentContextId = $currentContext
            ? (int) $currentContext->getId()
            : null;

        $contexts = Application::getContextDAO()->getAll(true);

        while ($context = $contexts->next()) {
            $contextId = (int) $context->getId();

            /*
             * During a normal journal registration OJS itself will add
             * Reader to the current journal immediately afterwards.
             *
             * Skip that single assignment unless Reviewer was selected.
             * The Reviewer registration path needs the complete Reader
             * group set prepared by this plugin.
             */
            if (
                !$this->registrationHasReviewer &&
                $currentContextId !== null &&
                $contextId === $currentContextId
            ) {
                continue;
            }

            $readerGroup = $this->getDefaultReaderGroup($contextId);

            if (!$readerGroup) {
                continue;
            }

            $this->assignUserToReaderGroup(
                (int) $user->getId(),
                (int) $readerGroup->id
            );
        }

        return Hook::CONTINUE;
    }

    /**
     * Queue synchronization after OJS creates a new journal.
     *
     * The web request only creates a small queue record.
     * Bulk user processing occurs outside the request.
     */
    public function queueNewJournalSynchronization(
        $hookName,
        $args
    ) {
        $context = $args[0] ?? null;

        if (!$context || !$context->getId()) {
            return Hook::CONTINUE;
        }

        $contextId = (int) $context->getId();

        if ($contextId <= 0) {
            return Hook::CONTINUE;
        }

        try {
            dispatch(
                new SyncNewJournalReadersJob($contextId)
            );
        } catch (\Throwable $e) {
            /*
             * Do not break journal creation if the queue
             * infrastructure is temporarily unavailable.
             *
             * No SQL, credentials or user data are logged.
             */
            error_log(
                sprintf(
                    '[IMSYP Auto Reader] Unable to queue context %d synchronization (%s).',
                    $contextId,
                    get_class($e)
                )
            );
        }

        return Hook::CONTINUE;
    }

    /**
     * Queue synchronization when a journal changes from
     * disabled to enabled.
     */
    public function queueSynchronizationWhenJournalEnabled(
        $hookName,
        $args
    ) {
        $newContext = $args[0] ?? null;
        $oldContext = $args[1] ?? null;

        if (!$newContext || !$oldContext) {
            return Hook::CONTINUE;
        }

        $wasEnabled = (bool) $oldContext->getData('enabled');
        $isEnabled = (bool) $newContext->getData('enabled');

        if ($wasEnabled || !$isEnabled) {
            return Hook::CONTINUE;
        }

        return $this->queueNewJournalSynchronization(
            $hookName,
            [$newContext]
        );
    }

    /**
     * Return the default Reader user group for one journal.
     */
    private function getDefaultReaderGroup(int $contextId)
    {
        return Repo::userGroup()
            ->getByRoleIds(
                [Role::ROLE_ID_READER],
                $contextId,
                true
            )
            ->first();
    }

    /**
     * Idempotently assign one user to one Reader user group.
     *
     * Existing active assignments are preserved and never duplicated.
     * Other roles are never modified or removed.
     */
    private function assignUserToReaderGroup(
        int $userId,
        int $readerGroupId
    ): void {
        if (
            Repo::userGroup()->userInGroup(
                $userId,
                $readerGroupId
            )
        ) {
            return;
        }

        Repo::userGroup()->assignUserToGroup(
            $userId,
            $readerGroupId
        );
    }
}
