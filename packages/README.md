# Bundled Composer archives

## acsystems/keycloak-php-sdk 4.4.0

The upstream Bitbucket distribution URL returns HTTP 404. This directory keeps
the original, unmodified distribution ZIP so both package and Docker builds can
install the same SDK without depending on the unavailable repository.

- Upstream commit: `29545022aea3f317bdbb0539f160914c6afb2183`
- Original URL: <https://bitbucket.org/acwebdev/keycloak-php-sdk/get/29545022aea3f317bdbb0539f160914c6afb2183.zip>
- SHA-256: `d1a2cb6b18101f6903249e98860016f7b5c0c35a923f88720213d3b2a3bdaba9`
- License: MIT; the original copyright notice and license are inside the ZIP.

The archive was recovered from the Composer cache for the locked distribution;
all 48 files match the installed 4.4.0 package. The package repository in
`composer.json` preserves its runtime requirements and autoload mappings and
verifies the archive's SHA-1 checksum during installation. Only this package is
overridden; other packages continue to resolve from Packagist.

When replacing this archive, verify the source and license, update the package
metadata and checksums, and regenerate `composer.lock` with a targeted Composer
update. A general dependency update is not needed for this workaround.
