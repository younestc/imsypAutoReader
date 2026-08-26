# Contributing to IMSYP Auto Reader

Contributions, bug reports, security improvements, and compatibility fixes are welcome.

## Development Principles

Changes should preserve the plugin's small security footprint.

Contributions should avoid introducing:

- unnecessary public endpoints;
- remote telemetry;
- external network dependencies;
- direct database credentials;
- shell execution;
- unsafe dynamic code execution;
- unnecessary raw SQL.

OJS/PKP APIs and repositories should be preferred whenever available.

## Testing

Before submitting a change:

1. Run PHP syntax checks.
2. Test against a supported OJS release.
3. Verify new-user Reader assignment.
4. Verify existing roles are preserved.
5. Verify synchronization is idempotent.
6. Verify disabled users are not bulk-assigned.
7. Run a synchronization dry run when relevant.

## Security Issues

Do not submit unpatched security vulnerabilities through a public issue.

See SECURITY.md.

## Project

Official project page:

https://www.imsyp.com/index.php/index/imsyp-auto-reader

Owner: IMSYP

Developer: Younouss EL ouati
