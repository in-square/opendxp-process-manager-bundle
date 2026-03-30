<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use OpenDxp\Migrations\BundleAwareMigration;

class Version20230207000000 extends BundleAwareMigration
{
    protected function getBundleName(): string
    {
        return InSquareOpendxpProcessManagerBundle::BUNDLE_NAME;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $configurationTable = $schema->getTable('bundle_process_manager_configuration');

        if (!$configurationTable->hasColumn('restrictToPermissions')) {
            $this->addSql(
                'ALTER TABLE `bundle_process_manager_configuration` ADD `restrictToPermissions` MEDIUMTEXT DEFAULT ""'
            );
            \OpenDxp\Cache::clearTags(['system', 'resource']);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
