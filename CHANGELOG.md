# Changelog

All notable changes to IMSYP Auto Reader are documented here.

## 1.1.0.0 - 2026-08-26

### Added

- Automatic synchronization of active users when a new journal is created.
- Background synchronization through the OJS/PKP queue infrastructure.
- Synchronization when an existing journal changes from disabled to enabled.
- Bounded synchronization batches for larger OJS installations.
- CLI synchronization utility for existing installations.
- Dry-run mode for synchronization auditing.
- Project security policy and public documentation.

### Security

- No custom public web API.
- No outbound network communication.
- No direct web-input superglobals.
- No shell command execution.
- No raw SQL for Reader role assignment.
- Disabled users are excluded from bulk synchronization.
- Existing memberships are checked before assignment.

### Changed

- New-journal synchronization is processed outside the journal-creation web request.
- Plugin prepared for independent distribution outside the original IMSYP installation.

## 1.0.0

### Added

- Automatic Reader assignment for new registrations.
- Site-wide operation across enabled journals.
- Reviewer and other existing roles preserved.

Copyright (c) 2026 IMSYP
Developer: Younouss EL ouati
