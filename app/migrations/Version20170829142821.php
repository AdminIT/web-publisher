<?php

namespace SWP\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170829142821 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql('ALTER TABLE swp_article_sources ADD id SERIAL NOT NULL');
        $this->addSql('ALTER TABLE swp_article_sources ADD CONSTRAINT FK_E38D33537294869C FOREIGN KEY (article_id) REFERENCES swp_article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE swp_article_sources ADD CONSTRAINT FK_E38D3353953C1C61 FOREIGN KEY (source_id) REFERENCES swp_article_source (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE swp_article_sources DROP CONSTRAINT swp_article_sources_pkey');
        $this->addSql('ALTER TABLE swp_article_sources ADD PRIMARY KEY (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql('ALTER TABLE swp_article_sources DROP CONSTRAINT FK_E38D33537294869C');
        $this->addSql('ALTER TABLE swp_article_sources DROP CONSTRAINT FK_E38D3353953C1C61');
        $this->addSql('ALTER TABLE swp_article_sources DROP CONSTRAINT swp_article_sources_pkey');
        $this->addSql('ALTER TABLE swp_article_sources DROP id');
        $this->addSql('ALTER TABLE swp_article_sources ADD PRIMARY KEY (article_id, source_id)');
    }
}