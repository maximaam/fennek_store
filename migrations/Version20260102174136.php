<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260102174136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE name_de name_de VARCHAR(128) NOT NULL, CHANGE name_en name_en VARCHAR(128) NOT NULL, CHANGE alias_de alias_de VARCHAR(128) NOT NULL, CHANGE alias_en alias_en VARCHAR(128) NOT NULL, CHANGE description_de description_de LONGTEXT DEFAULT NULL, CHANGE description_en description_en LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_image CHANGE image_name image_name VARCHAR(255) DEFAULT NULL, CHANGE owner owner VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE page ADD image_id INT DEFAULT NULL, CHANGE title_de title_de VARCHAR(255) NOT NULL, CHANGE alias_de alias_de VARCHAR(255) NOT NULL, CHANGE description_de description_de LONGTEXT NOT NULL, CHANGE title_en title_en VARCHAR(255) NOT NULL, CHANGE alias_en alias_en VARCHAR(255) NOT NULL, CHANGE description_en description_en LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB6203DA5256D FOREIGN KEY (image_id) REFERENCES media_image (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_140AB6203DA5256D ON page (image_id)');
        $this->addSql('ALTER TABLE product CHANGE item_number item_number VARCHAR(128) DEFAULT NULL, CHANGE title_de title_de VARCHAR(255) NOT NULL, CHANGE title_en title_en VARCHAR(255) NOT NULL, CHANGE description_de description_de LONGTEXT NOT NULL, CHANGE description_en description_en LONGTEXT NOT NULL, CHANGE colors colors LONGTEXT NOT NULL, CHANGE sizes sizes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE body body LONGTEXT NOT NULL, CHANGE headers headers LONGTEXT NOT NULL, CHANGE queue_name queue_name VARCHAR(190) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE name_de name_de VARCHAR(128) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE name_en name_en VARCHAR(128) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE alias_de alias_de VARCHAR(128) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE alias_en alias_en VARCHAR(128) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE description_de description_de LONGTEXT DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE description_en description_en LONGTEXT DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`');
        $this->addSql('ALTER TABLE media_image CHANGE image_name image_name VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE owner owner VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`');
        $this->addSql('ALTER TABLE messenger_messages CHANGE body body LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE headers headers LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE queue_name queue_name VARCHAR(190) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB6203DA5256D');
        $this->addSql('DROP INDEX UNIQ_140AB6203DA5256D ON page');
        $this->addSql('ALTER TABLE page DROP image_id, CHANGE title_de title_de VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE alias_de alias_de VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE description_de description_de LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE title_en title_en VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE alias_en alias_en VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE description_en description_en LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`');
        $this->addSql('ALTER TABLE product CHANGE item_number item_number VARCHAR(128) DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE title_de title_de VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE title_en title_en VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE description_de description_de LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE description_en description_en LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE colors colors LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, CHANGE sizes sizes LONGTEXT DEFAULT NULL COLLATE `utf8mb4_uca1400_ai_ci`');
    }
}
