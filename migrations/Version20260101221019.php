<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260101221019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make new_hash and previos_hash columns nullable in chain_of_custody table';
    }

    public function up(Schema $schema): void
    {
        // Make new_hash and previos_hash columns nullable
        $this->addSql('ALTER TABLE chain_of_custody MODIFY new_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE chain_of_custody MODIFY previos_hash VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert: make columns NOT NULL again
        $this->addSql('ALTER TABLE chain_of_custody MODIFY new_hash VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE chain_of_custody MODIFY previos_hash VARCHAR(255) NOT NULL');
    }
}
