# Security Policy

## Supported Versions

Security fixes are provided for the latest supported release of IMSYP Auto Reader.

## Reporting a Vulnerability

Please do not publish an unpatched security vulnerability publicly before IMSYP has had a reasonable opportunity to investigate it.

Security reports should be submitted through the official IMSYP website:

https://www.imsyp.com/index.php/index/imsyp-auto-reader

A report should include, when possible:

- affected plugin version;
- affected OJS version;
- description of the vulnerability;
- reproduction steps;
- potential security impact.

Do not include passwords, private keys, database credentials, personal data, or other secrets in a vulnerability report.

## Security Design

IMSYP Auto Reader is intentionally designed without a custom public web endpoint.

The plugin does not require:

- remote APIs;
- telemetry;
- file uploads;
- shell command execution;
- direct user-supplied SQL;
- embedded database credentials.

Role assignments use OJS/PKP application services and repositories.

Bulk synchronization is performed through trusted server-side execution or the OJS queue infrastructure.

## Disclosure

IMSYP may coordinate disclosure after a fix is available.

Copyright (c) 2026 IMSYP
Developer: Younouss EL ouati
