# IMSYP Auto Reader

IMSYP Auto Reader is a site-wide plugin for Open Journal Systems (OJS) that automatically assigns the Reader role across journals.

It is designed primarily for multi-journal OJS installations where registered users should have Reader access across all enabled journals.

## Official Project Page

https://www.imsyp.com/index.php/index/imsyp-auto-reader

## Features

- Automatically assigns new users as Readers in all enabled journals.
- Preserves roles selected during registration, such as Reviewer.
- Supports site-level registrations.
- Automatically synchronizes active users when a new journal is created.
- Synchronizes active users when an existing journal is enabled.
- Uses the OJS/PKP queue infrastructure for new-journal synchronization.
- Includes a CLI synchronization tool for existing installations.
- Excludes disabled user accounts from bulk synchronization.
- Does not remove or modify existing roles.
- Idempotent Reader assignments.

## Compatibility

Current release:

- IMSYP Auto Reader 1.1.0.0
- Open Journal Systems 3.5.x

Compatibility with additional OJS releases should be verified before installation.

## Requirements

For automatic synchronization when a new journal is created, the OJS queue must be processed using one of the queue-processing mechanisms supported by PKP.

For production installations, a cron-based scheduler or queue worker is recommended instead of processing large jobs during web requests.

## Installation

Copy the plugin directory to:

    plugins/generic/imsypAutoReader

Then install or enable the plugin through OJS according to the normal PKP plugin installation process.

The plugin is site-wide.

## Existing Users

A CLI synchronization utility is included:

    tools/syncReaders.php

Dry run:

    php tools/syncReaders.php --dry-run

Apply synchronization:

    php tools/syncReaders.php --apply

Run the command from a trusted server shell only.

The CLI tool is not a web endpoint.

## Security

IMSYP Auto Reader intentionally keeps its attack surface small.

The plugin:

- does not expose a custom public API;
- does not accept direct GET, POST, FILES, COOKIE, or REQUEST input;
- does not upload files;
- does not make outbound network requests;
- does not execute shell commands;
- does not use eval;
- does not store database credentials;
- uses OJS/PKP repositories and services for role assignments.

See SECURITY.md for the vulnerability reporting policy.

## Privacy

The plugin does not send user information to IMSYP or to any external service.

No telemetry or remote tracking is included.

## Owner

IMSYP

## Developer

Younouss EL ouati

## License

GPL-3.0-or-later

See LICENSE.

## Links

Official homepage:

https://www.imsyp.com/index.php/index/imsyp-auto-reader

IMSYP:

https://www.imsyp.com/
