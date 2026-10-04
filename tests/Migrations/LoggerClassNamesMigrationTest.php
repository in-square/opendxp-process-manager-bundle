<?php

declare(strict_types=1);

namespace InSquare\OpendxpProcessManagerBundle\Tests\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use InSquare\OpendxpProcessManagerBundle\Migrations\Version20261004000000;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class LoggerClassNamesMigrationTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $pdo = $this->db->getNativeConnection();
        // SQLite supplies JSON_EXTRACT/SET/TYPE/VALID; register the three MySQL helpers.
        $pdo->sqliteCreateFunction('LOCATE', static fn ($needle, $value) => ($offset = strpos((string) $value, $needle)) === false ? 0 : $offset + 1);
        $pdo->sqliteCreateFunction('JSON_UNQUOTE', static fn ($value) => $value);
        $pdo->sqliteCreateFunction('JSON_LENGTH', static fn ($value) => $value === null ? null : count(json_decode($value, true, 512, JSON_THROW_ON_ERROR)));
        $this->db->executeStatement('CREATE TABLE bundle_process_manager_configuration (id VARCHAR(190) PRIMARY KEY, executorSettings TEXT, modificationDate INTEGER, active INTEGER)');
        $this->db->executeStatement('CREATE TABLE bundle_process_manager_monitoring_item (id INTEGER PRIMARY KEY, loggers TEXT, actions TEXT, modificationDate INTEGER, reportedDate TEXT, status TEXT)');
    }

    public function testRepairsOnlyKnownDirectClassFieldsAndPreservesOtherData(): void
    {
        $oldEmail = '\\InSquareOpendxpProcessManagerBundle\\Executor\\Logger\\EmailSummary';
        $oldFile = 'InSquareOpendxpProcessManagerBundle\\Executor\\Logger\\File';
        $oldAction = '\\InSquareOpendxpProcessManagerBundle\\Executor\\Action\\Download';
        $settings = [
            'values' => ['text' => $oldEmail, 'class' => $oldEmail],
            'loggers' => [
                ['class' => $oldEmail, 'subject' => $oldEmail, 'metadata' => ['class' => $oldEmail]],
                ['class' => $oldEmail, 'to' => 'recipient@example.test'],
                ['class' => $oldFile],
                ['class' => '\\Custom\\Logger'],
                ['class' => '\\InSquareOpendxpProcessManagerBundle\\Executor\\Logger\\Unknown'],
            ],
            'actions' => [['class' => $oldAction, 'eventData' => ['class' => $oldEmail]]],
        ];
        $this->db->insert('bundle_process_manager_configuration', [
            'id' => 1, 'executorSettings' => json_encode($settings, JSON_THROW_ON_ERROR), 'modificationDate' => 123, 'active' => 0,
        ]);
        $this->db->insert('bundle_process_manager_monitoring_item', [
            'id' => 2, 'loggers' => json_encode($settings['loggers'], JSON_THROW_ON_ERROR),
            'actions' => json_encode($settings['actions'], JSON_THROW_ON_ERROR), 'modificationDate' => 456,
            'reportedDate' => '2026-10-01 12:00:00', 'status' => 'finished',
        ]);

        $beforeConfig = $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_configuration WHERE id = 1');
        $beforeMonitoring = $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_monitoring_item WHERE id = 2');
        $migration = $this->plan();
        // Planning, including dry-run SQL generation, must not perform any repair writes.
        self::assertSame($beforeConfig, $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_configuration WHERE id = 1'));
        self::assertSame($beforeMonitoring, $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_monitoring_item WHERE id = 2'));
        $this->apply($migration);

        $expected = $settings;
        foreach ([0, 1, 2] as $index) {
            $expected['loggers'][$index]['class'] = str_replace('InSquareOpendxpProcessManagerBundle', 'InSquare\\OpendxpProcessManagerBundle', $settings['loggers'][$index]['class']);
        }
        $expected['actions'][0]['class'] = '\\InSquare\\OpendxpProcessManagerBundle\\Executor\\Action\\Download';
        $afterConfig = $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_configuration WHERE id = 1');
        self::assertSame($expected, json_decode($afterConfig['executorSettings'], true, 512, JSON_THROW_ON_ERROR));
        unset($beforeConfig['executorSettings'], $afterConfig['executorSettings']);
        self::assertSame($beforeConfig, $afterConfig);

        $afterMonitoring = $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_monitoring_item WHERE id = 2');
        self::assertSame($expected['loggers'], json_decode($afterMonitoring['loggers'], true, 512, JSON_THROW_ON_ERROR));
        self::assertSame($expected['actions'], json_decode($afterMonitoring['actions'], true, 512, JSON_THROW_ON_ERROR));
        unset($beforeMonitoring['loggers'], $beforeMonitoring['actions'], $afterMonitoring['loggers'], $afterMonitoring['actions']);
        self::assertSame($beforeMonitoring, $afterMonitoring);

        $snapshot = $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_configuration WHERE id = 1');
        $this->apply($this->plan());
        self::assertSame($snapshot, $this->db->fetchAssociative('SELECT * FROM bundle_process_manager_configuration WHERE id = 1'));
    }

    public function testRepairsAllSevenNamesAcrossMoreThanTwoBatches(): void
    {
        $suffixes = [
            'Logger\\EmailSummary', 'Logger\\File', 'Logger\\Application', 'Logger\\Console',
            'Action\\OpenItem', 'Action\\Download', 'Action\\JsEvent',
        ];
        for ($id = 1; $id <= 501; ++$id) {
            $suffix = $suffixes[($id - 1) % count($suffixes)];
            $group = str_starts_with($suffix, 'Logger') ? 'loggers' : 'actions';
            $this->db->insert('bundle_process_manager_configuration', [
                'id' => $id, 'executorSettings' => json_encode([$group => [['class' => '\\InSquareOpendxpProcessManagerBundle\\Executor\\' . $suffix]]], JSON_THROW_ON_ERROR),
                'modificationDate' => $id, 'active' => 1,
            ]);
        }
        $this->apply($this->plan());
        self::assertSame(0, (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM bundle_process_manager_configuration WHERE LOCATE(?, executorSettings) > 0',
            ['InSquareOpendxpProcessManagerBundle']
        ));
        self::assertSame(501, (int) $this->db->fetchOne('SELECT COUNT(*) FROM bundle_process_manager_configuration'));
        self::assertSame(501, (int) $this->db->fetchOne('SELECT modificationDate FROM bundle_process_manager_configuration WHERE id = 501'));
        self::assertSame([], $this->plan()->getSql());
    }

    public function testRepairsTextAndMixedConfigurationIdentifiers(): void
    {
        $ids = ['0', '001', '1', '10', '1-mail', '2', 'newsletter', 'z-report'];
        for ($index = 0; $index < 501; ++$index) {
            $ids[] = sprintf('job-%04d', $index);
        }
        foreach ($ids as $id) {
            $this->db->insert('bundle_process_manager_configuration', [
                'id' => $id,
                'executorSettings' => json_encode(['loggers' => [['class' => '\InSquareOpendxpProcessManagerBundle\Executor\Logger\EmailSummary']]], JSON_THROW_ON_ERROR),
                'modificationDate' => 123,
            ]);
        }
        $this->apply($this->plan());
        self::assertSame(count($ids), (int) $this->db->fetchOne('SELECT COUNT(*) FROM bundle_process_manager_configuration'));
        self::assertSame(0, (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM bundle_process_manager_configuration WHERE LOCATE(?, executorSettings) > 0',
            ['InSquareOpendxpProcessManagerBundle']
        ));
        self::assertSame(count($ids), (int) $this->db->fetchOne('SELECT COUNT(*) FROM bundle_process_manager_configuration WHERE modificationDate = 123'));
        self::assertSame([], $this->plan()->getSql());
    }
    public function testValidUnrelatedAndNullValuesAreNotChanged(): void
    {
        $values = [
            null, '', '{"loggers":[]}',
            '{"loggers":[{"class":"Custom\\\\Logger"}]}',
            '{"loggers":{"class":"\\\\InSquareOpendxpProcessManagerBundle\\\\Executor\\\\Logger\\\\File"}}',
            '{"values":{"text":"InSquareOpendxpProcessManagerBundle"}}',
        ];
        foreach ($values as $index => $value) {
            $this->db->insert('bundle_process_manager_configuration', ['id' => $index + 1, 'executorSettings' => $value]);
        }
        $before = $this->db->fetchAllAssociative('SELECT * FROM bundle_process_manager_configuration ORDER BY id');
        $this->apply($this->plan());
        self::assertSame($before, $this->db->fetchAllAssociative('SELECT * FROM bundle_process_manager_configuration ORDER BY id'));
    }

    public function testUnrelatedMalformedJsonInsideBatchRangeIsUntouched(): void
    {
        foreach ([1, 3] as $id) {
            $this->db->insert('bundle_process_manager_configuration', [
                'id' => $id,
                'executorSettings' => json_encode(['loggers' => [['class' => '\\InSquareOpendxpProcessManagerBundle\\Executor\\Logger\\File']]], JSON_THROW_ON_ERROR),
            ]);
        }
        $this->db->insert('bundle_process_manager_configuration', ['id' => 2, 'executorSettings' => '{unrelated invalid JSON']);
        $this->apply($this->plan());
        self::assertSame('{unrelated invalid JSON', $this->db->fetchOne('SELECT executorSettings FROM bundle_process_manager_configuration WHERE id = 2'));
        self::assertSame(0, (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM bundle_process_manager_configuration WHERE LOCATE(?, executorSettings) > 0',
            ['InSquareOpendxpProcessManagerBundle']
        ));
    }

    public function testMalformedCandidateJsonStopsMigrationBeforeWrites(): void
    {
        $this->db->insert('bundle_process_manager_configuration', ['id' => 1, 'executorSettings' => '{InSquareOpendxpProcessManagerBundle']);
        $this->expectException(AbortMigration::class);
        $this->expectExceptionMessage('Invalid JSON');
        $this->plan();
    }

    public function testDownDoesNotReintroduceInvalidNames(): void
    {
        $migration = new Version20261004000000($this->db, new NullLogger());
        $this->expectException(IrreversibleMigration::class);
        $migration->down(new Schema());
    }

    private function plan(): Version20261004000000
    {
        $migration = new Version20261004000000($this->db, new NullLogger());
        $migration->up($this->db->createSchemaManager()->introspectSchema());

        return $migration;
    }

    private function apply(Version20261004000000 $migration): void
    {
        foreach ($migration->getSql() as $query) {
            $this->db->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
