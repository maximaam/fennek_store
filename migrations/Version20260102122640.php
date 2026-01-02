<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260102122640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_image ADD page_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_image ADD CONSTRAINT FK_DA24C0EEC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('CREATE INDEX IDX_DA24C0EEC4663E4 ON media_image (page_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_image DROP FOREIGN KEY FK_DA24C0EEC4663E4');
        $this->addSql('DROP INDEX IDX_DA24C0EEC4663E4 ON media_image');
        $this->addSql('ALTER TABLE media_image DROP page_id');
    }
}
