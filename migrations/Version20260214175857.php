<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260214175857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, position INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, parent_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_64C19C1462CE4F5 (position), INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category_translation (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(2) NOT NULL, name VARCHAR(64) NOT NULL, description LONGTEXT DEFAULT NULL, alias VARCHAR(128) NOT NULL, category_id INT NOT NULL, INDEX IDX_3F2070412469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_image (id INT AUTO_INCREMENT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, owner VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, product_id INT DEFAULT NULL, page_id INT DEFAULT NULL, INDEX IDX_DA24C0EE4584665A (product_id), INDEX IDX_DA24C0EEC4663E4 (page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_translation (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(2) NOT NULL, title VARCHAR(32) NOT NULL, description LONGTEXT NOT NULL, alias VARCHAR(64) NOT NULL, page_id INT NOT NULL, INDEX IDX_A3D51B1DC4663E4 (page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, item_number VARCHAR(128) DEFAULT NULL, colors JSON NOT NULL, sizes JSON DEFAULT NULL, price INT NOT NULL, top_item TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_translation (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(2) NOT NULL, title VARCHAR(128) NOT NULL, description LONGTEXT NOT NULL, slug VARCHAR(255) NOT NULL, product_id INT NOT NULL, INDEX IDX_1846DB704584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE purchase (id INT AUTO_INCREMENT NOT NULL, order_id VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, payment JSON NOT NULL, product JSON NOT NULL, shipped TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE category_translation ADD CONSTRAINT FK_3F2070412469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE media_image ADD CONSTRAINT FK_DA24C0EE4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE media_image ADD CONSTRAINT FK_DA24C0EEC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE page_translation ADD CONSTRAINT FK_A3D51B1DC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product_translation ADD CONSTRAINT FK_1846DB704584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE category_translation DROP FOREIGN KEY FK_3F2070412469DE2');
        $this->addSql('ALTER TABLE media_image DROP FOREIGN KEY FK_DA24C0EE4584665A');
        $this->addSql('ALTER TABLE media_image DROP FOREIGN KEY FK_DA24C0EEC4663E4');
        $this->addSql('ALTER TABLE page_translation DROP FOREIGN KEY FK_A3D51B1DC4663E4');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product_translation DROP FOREIGN KEY FK_1846DB704584665A');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_translation');
        $this->addSql('DROP TABLE media_image');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE page_translation');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_translation');
        $this->addSql('DROP TABLE purchase');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
