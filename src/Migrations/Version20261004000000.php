<?php

declare(strict_types=1);

namespace InSquare\OpendxpProcessManagerBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use OpenDxp\Migrations\BundleAwareMigration;

/** InSquare repair of class names submitted by the migrated admin forms. */
final class Version20261004000000 extends BundleAwareMigration
{
    private const BATCH_SIZE = 250;

    private const CLASSES = [
        'Executor\\Logger\\EmailSummary',
        'Executor\\Logger\\File',
        'Executor\\Logger\\Application',
        'Executor\\Logger\\Console',
        'Executor\\Action\\OpenItem',
        'Executor\\Action\\Download',
        'Executor\\Action\\JsEvent',
    ];

    protected function getBundleName(): string
    {
        return InSquareOpendxpProcessManagerBundle::BUNDLE_NAME;
    }

    public function getDescription(): string
    {
        return 'Repair seven malformed InSquare logger/action class names in configuration and monitoring data';
    }

    public function up(Schema $schema): void
    {
        $targets = [
            [InSquareOpendxpProcessManagerBundle::TABLE_NAME_CONFIGURATION, 'executorSettings', ['$.loggers', '$.actions']],
            [InSquareOpendxpProcessManagerBundle::TABLE_NAME_MONITORING_ITEM, 'loggers', ['$']],
            [InSquareOpendxpProcessManagerBundle::TABLE_NAME_MONITORING_ITEM, 'actions', ['$']],
        ];

        foreach ($targets as [$table, $column, $paths]) {
            $this->abortIf(
                !$schema->hasTable($table) || !$schema->getTable($table)->hasColumn($column),
                sprintf('Missing required migration column %s.%s.', $table, $column)
            );
            $invalidId = $this->connection->fetchOne(
                "SELECT id FROM $table WHERE LOCATE(?, $column) > 0 AND NOT JSON_VALID($column) ORDER BY id LIMIT 1",
                ['InSquareOpendxpProcessManagerBundle']
            );
            $this->abortIf(
                $invalidId !== false,
                sprintf('Invalid JSON in %s.%s at ID %s; repair the JSON before retrying.', $table, $column, $invalidId)
            );

            $lastId = 0;
            do {
                // Only bounded identifiers are fetched; JSON inspection and updates stay in SQL.
                $ids = $this->connection->fetchFirstColumn(
                    "SELECT id FROM $table WHERE id > ? AND LOCATE(?, $column) > 0 ORDER BY id LIMIT " . self::BATCH_SIZE,
                    [$lastId, 'InSquareOpendxpProcessManagerBundle']
                );
                if ($ids === []) {
                    break;
                }
                $upperId = (int) $ids[array_key_last($ids)];

                $document = "CASE WHEN JSON_VALID($column) THEN $column ELSE 'null' END";
                foreach ($paths as $path) {
                    $length = (int) $this->connection->fetchOne(
                        "SELECT MAX(JSON_LENGTH(JSON_EXTRACT($document, ?))) FROM $table"
                        . " WHERE id > ? AND id <= ? AND JSON_VALID($column)"
                        . " AND LOWER(JSON_TYPE(JSON_EXTRACT($document, ?))) = 'array'",
                        [$path, $lastId, $upperId, $path]
                    );

                    for ($index = 0; $index < $length; ++$index) {
                        $this->planClassUpdate($table, $column, $path, $index, $lastId, $upperId);
                    }
                }
                $lastId = $upperId;
            } while (count($ids) === self::BATCH_SIZE);
        }
    }

    private function planClassUpdate(
        string $table,
        string $column,
        string $path,
        int $index,
        int $lowerId,
        int $upperId
    ): void {
        $document = "CASE WHEN JSON_VALID($column) THEN $column ELSE 'null' END";
        $classPath = $path . '[' . $index . '].class';
        $cases = [];
        $params = [$classPath, $classPath];
        $oldClasses = [];

        foreach (self::CLASSES as $suffix) {
            // Preserve whether the stored class had a leading namespace separator.
            foreach (['', '\\'] as $prefix) {
                $old = $prefix . 'InSquareOpendxpProcessManagerBundle\\' . $suffix;
                $new = $prefix . 'InSquare\\OpendxpProcessManagerBundle\\' . $suffix;
                $cases[] = 'WHEN ? THEN ?';
                $params[] = $old;
                $params[] = $new;
                $oldClasses[] = $old;
            }
        }

        $params = [...$params, $lowerId, $upperId, $path, $classPath, ...$oldClasses];
        $placeholders = implode(', ', array_fill(0, count($oldClasses), '?'));

        // Doctrine queues these statements, so --dry-run never performs repair writes.
        $this->addSql(
            "UPDATE $table SET $column = JSON_SET($column, ?, CASE JSON_UNQUOTE(JSON_EXTRACT($document, ?)) "
            . implode(' ', $cases) . ' END)'
            . " WHERE id > ? AND id <= ? AND JSON_VALID($column)"
            . " AND LOWER(JSON_TYPE(JSON_EXTRACT($document, ?))) = 'array'"
            . " AND JSON_UNQUOTE(JSON_EXTRACT($document, ?)) IN ($placeholders)",
            $params
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Restoring malformed class names would break valid configurations. Restore a reviewed database backup instead.'
        );
    }
}
