<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123222457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page ADD title_de VARCHAR(255) NOT NULL, ADD alias_de VARCHAR(255) NOT NULL, ADD title_en VARCHAR(255) NOT NULL, ADD alias_en VARCHAR(255) NOT NULL, ADD description_en LONGTEXT NOT NULL, DROP title, DROP alias, CHANGE description description_de LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page ADD title VARCHAR(255) NOT NULL, ADD description LONGTEXT NOT NULL, ADD alias VARCHAR(255) NOT NULL, DROP title_de, DROP alias_de, DROP description_de, DROP title_en, DROP alias_en, DROP description_en');
    }
}
