<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505133646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_category_alias ON category_translation (alias)');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_alias_locale ON category_translation (alias, locale)');
        $this->addSql('CREATE INDEX idx_page_alias ON page_translation (alias)');
        $this->addSql('CREATE UNIQUE INDEX uniq_page_alias_locale ON page_translation (alias, locale)');
        $this->addSql('CREATE INDEX idx_product_slug ON product_translation (slug)');
        $this->addSql('CREATE UNIQUE INDEX uniq_product_id_locale ON product_translation (product_id, locale)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_category_alias ON category_translation');
        $this->addSql('DROP INDEX uniq_category_alias_locale ON category_translation');
        $this->addSql('DROP INDEX idx_page_alias ON page_translation');
        $this->addSql('DROP INDEX uniq_page_alias_locale ON page_translation');
        $this->addSql('DROP INDEX idx_product_slug ON product_translation');
        $this->addSql('DROP INDEX uniq_product_id_locale ON product_translation');
    }
}
