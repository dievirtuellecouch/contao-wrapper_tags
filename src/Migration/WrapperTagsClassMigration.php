<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Migration;

use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Zmyslny\WrapperTags\WrapperTagType;

final class WrapperTagsClassMigration implements MigrationInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getName(): string
    {
        return 'Move legacy wrapper CSS classes into the wrapper tag data';
    }

    public function shouldRun(): bool
    {
        foreach ($this->getRows() as $row) {
            if ($this->needsMigration($row)) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $updated = $this->connection->transactional(function (Connection $connection): int {
            $affectedRows = 0;

            foreach ($this->getRows() as $row) {
                $class = $this->getLegacyClass($row);
                $field = $this->getTagField((string) $row['type']);
                $tags = StringUtil::deserialize($row[$field] ?? null, true);

                if ('' === $class || !\is_array($tags) || [] === $tags || !\is_array($tags[0] ?? null)) {
                    continue;
                }

                if ('' !== trim((string) ($tags[0]['class'] ?? ''))) {
                    continue;
                }

                $tags[0]['class'] = $class;
                $connection->update('tl_content', [$field => serialize($tags)], ['id' => $row['id']]);
                ++$affectedRows;
            }

            return $affectedRows;
        });

        return new MigrationResult(
            true,
            sprintf('Moved legacy CSS classes for %d wrapper tag content element(s).', $updated),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getRows(): array
    {
        if (!$this->hasRequiredColumns()) {
            return [];
        }

        return $this->connection->fetchAllAssociative(
            'SELECT id, type, cssID, wt_opening_tags, wt_complete_tags
             FROM tl_content
             WHERE type IN (?, ?, ?, ?)',
            [
                WrapperTagType::START,
                WrapperTagType::COMPLETE,
                WrapperTagType::LEGACY_START,
                WrapperTagType::LEGACY_COMPLETE,
            ],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function needsMigration(array $row): bool
    {
        $class = $this->getLegacyClass($row);
        $field = $this->getTagField((string) $row['type']);
        $tags = StringUtil::deserialize($row[$field] ?? null, true);

        return '' !== $class
            && \is_array($tags)
            && [] !== $tags
            && \is_array($tags[0] ?? null)
            && '' === trim((string) ($tags[0]['class'] ?? ''));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function getLegacyClass(array $row): string
    {
        $cssId = StringUtil::deserialize($row['cssID'] ?? null, true);

        return \is_array($cssId) ? trim((string) ($cssId[1] ?? '')) : '';
    }

    private function getTagField(string $type): string
    {
        return \in_array($type, [WrapperTagType::COMPLETE, WrapperTagType::LEGACY_COMPLETE], true)
            ? 'wt_complete_tags'
            : 'wt_opening_tags';
    }

    private function hasRequiredColumns(): bool
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist(['tl_content'])) {
                return false;
            }

            $columns = $schemaManager->listTableColumns('tl_content');

            return isset(
                $columns['cssid'],
                $columns['wt_opening_tags'],
                $columns['wt_complete_tags'],
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
