<?php

namespace Zmyslny\WrapperTags\Migration;

use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class WrapperTagsSchemaMigration implements MigrationInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Create tl_content columns for wrapper tags (wt_opening_tags, wt_closing_tags, wt_complete_tags)';
    }

    public function shouldRun(): bool
    {
        try {
            $schemaManager = method_exists($this->connection, 'createSchemaManager')
                ? $this->connection->createSchemaManager()
                : $this->connection->getSchemaManager();

            if (!$schemaManager->tablesExist(['tl_content'])) {
                return false;
            }

            $columns = $schemaManager->listTableColumns('tl_content');

            return !isset($columns['wt_opening_tags'])
                || !isset($columns['wt_closing_tags'])
                || !isset($columns['wt_complete_tags']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function run(): MigrationResult
    {
        $added = 0;

        $schemaManager = method_exists($this->connection, 'createSchemaManager')
            ? $this->connection->createSchemaManager()
            : $this->connection->getSchemaManager();

        $columns = $schemaManager->listTableColumns('tl_content');

        // Use BLOB NULL like the original extension
        if (!isset($columns['wt_opening_tags'])) {
            $this->connection->executeStatement('ALTER TABLE tl_content ADD wt_opening_tags BLOB NULL');
            ++$added;
        }

        if (!isset($columns['wt_closing_tags'])) {
            $this->connection->executeStatement('ALTER TABLE tl_content ADD wt_closing_tags BLOB NULL');
            ++$added;
        }

        if (!isset($columns['wt_complete_tags'])) {
            $this->connection->executeStatement('ALTER TABLE tl_content ADD wt_complete_tags BLOB NULL');
            ++$added;
        }

        return new MigrationResult(true, sprintf('Wrapper tags schema ensured, columns added: %d', $added));
    }
}

