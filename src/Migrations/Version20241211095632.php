<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use OpenDxp\Migrations\BundleAwareMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20241211095632 extends BundleAwareMigration
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
        $this->addSql('ALTER TABLE `bundle_process_manager_monitoring_item` MODIFY COLUMN `currentStep` int(10)');
        $this->addSql('ALTER TABLE `bundle_process_manager_monitoring_item` MODIFY COLUMN `totalSteps` int(10)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `bundle_process_manager_monitoring_item` MODIFY COLUMN `currentStep` smallint(5)');
        $this->addSql('ALTER TABLE `bundle_process_manager_monitoring_item` MODIFY COLUMN `totalSteps` smallint(5)');
    }
}
