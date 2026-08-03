<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Migration;

use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Zmyslny\WrapperTags\WrapperTagType;

final class WrapperTagsTypeMigration implements MigrationInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getName(): string
    {
        return 'Migrate legacy wrapper tag content element types to Contao 5.7 types';
    }

    public function shouldRun(): bool
    {
        if (!$this->hasContentTable()) {
            return false;
        }

        return 0 < (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_content WHERE type IN (?, ?, ?)',
            array_keys(WrapperTagType::LEGACY_TO_CURRENT),
        );
    }

    public function run(): MigrationResult
    {
        $updated = $this->connection->transactional(function (Connection $connection): int {
            $affectedRows = 0;

            foreach (WrapperTagType::LEGACY_TO_CURRENT as $legacyType => $currentType) {
                $affectedRows += $connection->update(
                    'tl_content',
                    ['type' => $currentType],
                    ['type' => $legacyType],
                );
            }

            return $affectedRows;
        });

        return new MigrationResult(
            true,
            sprintf('Migrated %d wrapper tag content element(s) to the Contao 5.7 type names.', $updated),
        );
    }

    private function hasContentTable(): bool
    {
        try {
            return $this->connection->createSchemaManager()->tablesExist(['tl_content']);
        } catch (\Throwable) {
            return false;
        }
    }
}
