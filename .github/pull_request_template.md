## Summary

Describe the change and why it is needed.

## Related issue

Link the related issue when applicable.

## Testing

Describe how the change was tested, including the OJS and PHP versions used.

## Checklist

- [ ] PHP syntax checks pass.
- [ ] Tested against a supported OJS release.
- [ ] New-user Reader assignment still works as expected.
- [ ] Existing user roles are preserved.
- [ ] Synchronization remains idempotent where applicable.
- [ ] Disabled users are not unintentionally bulk-assigned.
- [ ] A synchronization dry run was performed when relevant.
- [ ] No credentials, secrets, or sensitive information are included.
- [ ] No unnecessary public endpoint, telemetry, external network dependency, shell execution, unsafe dynamic code execution, or raw SQL was introduced.
- [ ] Documentation and changelog were updated when needed.

## Additional notes

Add migration, compatibility, security, or operational notes reviewers should know.
