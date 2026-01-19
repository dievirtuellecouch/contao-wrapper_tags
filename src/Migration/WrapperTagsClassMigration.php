<?php

namespace Zmyslny\WrapperTags\Migration;

use Contao\StringUtil;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class WrapperTagsClassMigration implements MigrationInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Populate class into wt_opening_tags[0][class] from cssID if missing';
    }

    public function shouldRun(): bool
    {
        try {
            // Ensure table exists
            $schemaManager = method_exists($this->connection, 'createSchemaManager')
                ? $this->connection->createSchemaManager()
                : $this->connection->getSchemaManager();

            if (!$schemaManager->tablesExist(['tl_content'])) {
                return false;
            }

            $columns = $schemaManager->listTableColumns('tl_content');
            $haveOpening = isset($columns['wt_opening_tags']);
            $haveComplete = isset($columns['wt_complete_tags']);
            $haveCssId = isset($columns['cssid']);

            if (!$haveCssId || (!$haveOpening && !$haveComplete)) {
                return false;
            }

            // Check if there is any record that likely needs migration
            $sql = "SELECT id, type, cssID, wt_opening_tags, wt_complete_tags FROM tl_content WHERE type IN ('wt_opening_tags','wt_complete_tags')";
            $rows = $this->connection->fetchAllAssociative($sql);
            foreach ($rows as $row) {
                $css = @StringUtil::deserialize($row['cssID'], true);
                $classFromCssId = is_array($css) && isset($css[1]) ? trim((string) $css[1]) : '';

                $field = $row['type'] === 'wt_complete_tags' ? 'wt_complete_tags' : 'wt_opening_tags';
                $tags = @StringUtil::deserialize($row[$field], true);
                if (!is_array($tags) || empty($tags)) {
                    continue;
                }
                if (!isset($tags[0]['class']) || $tags[0]['class'] === '') {
                    if ($classFromCssId !== '') {
                        return true; // There is at least one record to migrate
                    }
                }
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function run(): MigrationResult
    {
        $updated = 0;
            $sql = "SELECT id, type, cssID, wt_opening_tags, wt_complete_tags FROM tl_content WHERE type IN ('wt_opening_tags','wt_complete_tags')";
            $rows = $this->connection->fetchAllAssociative($sql);
            foreach ($rows as $row) {
                $css = @StringUtil::deserialize($row['cssID'], true);
                $classFromCssId = is_array($css) && isset($css[1]) ? trim((string) $css[1]) : '';
            if ($classFromCssId === '') {
                continue;
            }

            $field = $row['type'] === 'wt_complete_tags' ? 'wt_complete_tags' : 'wt_opening_tags';
            $tags = @StringUtil::deserialize($row[$field], true);
            if (!is_array($tags) || empty($tags)) {
                continue;
            }

            if (!isset($tags[0]['class']) || $tags[0]['class'] === '') {
                $tags[0]['class'] = $classFromCssId;
                $this->connection->update('tl_content', [ $field => serialize($tags) ], ['id' => $row['id']]);
                ++$updated;
            }
        }

        return new MigrationResult(true, sprintf('Populated class for %d record(s).', $updated));
    }
}
