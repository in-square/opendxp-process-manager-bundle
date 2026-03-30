# InSquare OpenDXP Process Manager Bundle

OpenDXP-compatible fork of `elements/process-manager-bundle`.

## Why this fork exists

Upstream maintainers declared they do not plan OpenDXP support.
This fork is maintained by InSquare to keep Process Manager available for OpenDXP projects.

## License (Important)

This fork is based on the last upstream release that is clearly licensed under **GPL-3.0-or-later**:

- Upstream repository: `https://github.com/valantic-at/ProcessManager`
- Base tag: `v5.0.28`
- Base commit: `1b4f5fbfc48c838580140e9411b7203468850118`

The license remains **GPL-3.0-or-later**. See [License.md](./License.md) and [gpl-3.0.txt](./gpl-3.0.txt).
Fork maintainer: `https://github.com/in-square`.

## Scope of this fork

- Composer package renamed to `insquare/opendxp-process-manager-bundle`.
- Dependency migrated from `pimcore/pimcore` to `open-dxp/opendxp`.
- OpenDXP compatibility fixes in PHP runtime integration (constants, namespaces, command prefixes).
- Doctrine migrations use bundle resource notation (`@InSquareOpendxpProcessManagerBundle/Migrations`) for compatibility with both `path` repositories and `vendor` installs.

## Installation

```bash
composer require insquare/opendxp-process-manager-bundle
bin/console opendxp:bundle:install InSquareOpendxpProcessManagerBundle
```

## Routing

Bundle routes are auto-loaded by OpenDXP from
`src/Resources/config/opendxp/routing.yaml` (no manual import in host `config/routes.yaml` required).

## Upstream Origin & Version Transparency

This repository is a fork of `elements/process-manager-bundle` (valantic-at/ProcessManager), derived from `v5.0.28`.
