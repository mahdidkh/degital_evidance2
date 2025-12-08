<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251116123334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE case_work (id INT AUTO_INCREMENT NOT NULL, tittel VARCHAR(255) NOT NULL, statu VARCHAR(255) NOT NULL, discription VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chain_of_coady (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, date_update DATE NOT NULL, new_hash VARCHAR(255) NOT NULL, previos_hash VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evidance (id INT AUTO_INCREMENT NOT NULL, tittel VARCHAR(255) NOT NULL, file_hash VARCHAR(255) NOT NULL, discription VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE case_work');
        $this->addSql('DROP TABLE chain_of_coady');
        $this->addSql('DROP TABLE evidance');
    }
}
