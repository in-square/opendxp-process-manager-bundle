# Logger and action class names

## Cause and correction

The OpenDXP namespace conversion left one unescaped backslash between `InSquare`
and `OpendxpProcessManagerBundle` in seven JavaScript form fields. JavaScript
removed that separator, producing a PHP class name beginning with
`\InSquareOpendxpProcessManagerBundle\` instead of
`\InSquare\OpendxpProcessManagerBundle\`.

This InSquare correction on top of the upstream GPL `v5.0.28` base escapes that
separator in the EmailSummary, File, Application and Console logger forms, and
the OpenItem, Download and JsEvent action forms. It does not change PHP execution
or the configuration storage format.

## Deployment and existing data

Deploy the updated bundle assets using the host application's normal asset
installation process, then reload the admin panel so it loads the corrected
JavaScript.

Logger configurations may already contain the invalid class name in
`bundle_process_manager_configuration.executorSettings`. The code correction
does not repair those records. Inspect affected records separately and review a
targeted repair before applying it. Monitoring records may also contain copied
logger settings and should be considered when reviewing existing data.

## Acceptance checks

- Verify the submitted class names retain every namespace separator.
- Save each of the four logger types and reload the configuration list.
- Save each of the three action types and reopen the configuration.
- Verify valid existing configurations still open and save.
- Validate Composer metadata and bundle loading in the OpenDXP host using its
  Composer path repository.

These checks do not establish email delivery; that requires a separate runtime
check with an explicitly selected recipient.

## Validation performed

- All seven JavaScript files parse; each class literal evaluates to the expected
  fully qualified PHP name and survives JSON serialization unchanged.
- All seven classes autoload and instantiate in the OpenDXP host container.
- Configuration serialization and reconstruction through `getExtJsSettings()`
  pass for all seven types without database writes.
- Bundle Composer metadata validates and the host exposes Process Manager commands.
- The seven public JavaScript copies in the local host were updated and their
  hashes match the source files.
- The current host uses a direct PSR-4 mapping to the bundle directory, so these
  checks do not verify installation through a Composer path repository.
- Admin browser save/reopen checks and email delivery remain unverified. Existing
  stored configurations were not inspected or modified.
