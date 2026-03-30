# Contribution notes

PHPStan checks:
```shell
vendor/bin/phpstan analyse vendor/insquare/opendxp-process-manager-bundle -c vendor/insquare/opendxp-process-manager-bundle/phpstan.neon
```
PHP CS Fixer checks:

```shell
vendor/bin/php-cs-fixer fix dev/bundles/insquare/opendxp-process-manager-bundle/ --config vendor/insquare/opendxp-process-manager-bundle/.php-cs-fixer.dist.php --dry-run --diff
```