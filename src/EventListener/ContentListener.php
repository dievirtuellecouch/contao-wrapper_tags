<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\EventListener;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Date;
use Contao\Image;
use Contao\MemberGroupModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zmyslny\WrapperTags\Util\TagNormalizer;
use Zmyslny\WrapperTags\Validation\WrapperStructureValidator;
use Zmyslny\WrapperTags\WrapperTagType;

final class ContentListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly WrapperStructureValidator $structureValidator,
    ) {
    }

    #[AsCallback(table: 'tl_content', target: 'fields.wt_opening_tags.save', priority: 100)]
    #[AsCallback(table: 'tl_content', target: 'fields.wt_complete_tags.save', priority: 100)]
    public function onSaveCallback(mixed $value, DataContainer $dataContainer): string
    {
        $tags = TagNormalizer::normalize(
            $value,
            'wt_complete_tags' === ($dataContainer->field ?? null),
        );

        foreach ($tags as &$tag) {
            if (!TagNormalizer::isValidTagName($tag['tag'])) {
                throw new \InvalidArgumentException($this->translateError('wt.errorTagName', [$tag['tag']]));
            }

            $attributeNames = [];

            foreach ($tag['attributes'] as $attribute) {
                $name = $attribute['name'];
                $normalizedName = strtolower($name);

                if ('' === $name && '' !== $attribute['value']) {
                    throw new \InvalidArgumentException(
                        $this->translateError('wt.errorAttributeValueWithoutName', [$attribute['value']]),
                    );
                }

                if (isset($attributeNames[$normalizedName])) {
                    throw new \InvalidArgumentException(
                        $this->translateError('wt.errorAttributeNameAlreadyUsed', [$name]),
                    );
                }

                if (!TagNormalizer::isValidAttributeName($name)) {
                    throw new \InvalidArgumentException($this->translateError('wt.errorAttributeName', [$name]));
                }

                if ('' === $attribute['value']) {
                    throw new \InvalidArgumentException(
                        $this->translateError('wt.errorAttributeNameWithoutValue', [$name]),
                    );
                }

                $attributeNames[$normalizedName] = true;
            }
        }
        unset($tag);

        return serialize($tags);
    }

    #[AsCallback(table: 'tl_content', target: 'list.label.label', priority: 100)]
    public function onLabelCallback(array $row, string $label, DataContainer $dataContainer): array|string
    {
        if ('tl_theme' === ($dataContainer->parentTable ?? null)) {
            return $this->generateContentTypeLabel($row);
        }

        $indent = $GLOBALS['WrapperTags']['indents'][(int) $row['id']] ?? null;

        if (\is_array($indent)) {
            $this->setChildRecordClass($indent);
        }

        return $this->generateChildRecord($row);
    }

    /**
     * @deprecated Kept for extensions calling the former callback method directly.
     */
    public function onChildRecordCallback(array $row): array|string
    {
        return $this->generateChildRecord($row);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function onClosingTagsColumnsCallback(): array
    {
        return [
            'tag' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['wt_tag'],
                'inputType' => 'select',
                'options_callback' => [self::class, 'getTags'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        $config = $this->framework->getAdapter(Config::class);
        $allowedTags = $config->get('wt_allowed_tags');

        if (!\is_string($allowedTags) || '' === trim($allowedTags)) {
            $allowedTags = $GLOBALS['TL_CONFIG']['wt_allowed_tags'] ?? '<div><span>';
        }

        $tags = [];

        foreach (StringUtil::trimsplit('><', $allowedTags) as $tag) {
            $tag = strtolower(trim($tag, "<> \t\n\r\0\x0B"));

            if (!TagNormalizer::isValidTagName($tag) || \in_array($tag, $tags, true)) {
                continue;
            }

            $tags[] = $tag;
        }

        foreach (['div', 'span'] as $requiredTag) {
            if (!\in_array($requiredTag, $tags, true)) {
                $tags[] = $requiredTag;
            }
        }

        return $tags;
    }

    #[AsCallback(table: 'tl_content', target: 'list.sorting.header', priority: 100)]
    public function onHeaderCallback(array $header, DataContainer $dataContainer): array
    {
        if (!$this->hasWrapperTagColumns()) {
            return $header;
        }

        $parentId = (int) (($dataContainer->currentPid ?? null) ?: ($dataContainer->id ?? 0));
        $parentTable = (string) (($dataContainer->parentTable ?? null) ?: 'tl_article');

        if (0 === $parentId) {
            return $header;
        }

        $wrapperCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_content WHERE pid = ? AND ptable = ? AND invisible != ? AND type IN (?, ?)',
            [$parentId, $parentTable, '1', WrapperTagType::START, WrapperTagType::STOP],
        );

        if (0 === $wrapperCount) {
            return $header;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, type, wt_opening_tags, wt_closing_tags, invisible
             FROM tl_content
             WHERE pid = ? AND ptable = ?
             ORDER BY sorting ASC',
            [$parentId, $parentTable],
        );

        $validation = $this->structureValidator->validate(
            $rows,
            array_values(array_unique($GLOBALS['TL_WRAPPERS']['start'] ?? [])),
            array_values(array_unique($GLOBALS['TL_WRAPPERS']['stop'] ?? [])),
        );

        $indents = $validation['indents'];
        $useColors = (bool) $this->framework->getAdapter(Config::class)->get('wt_use_colors');
        $offset = $this->getListOffset($dataContainer);
        $firstIndent = array_values($indents)[$offset] ?? reset($indents);

        if (\is_array($firstIndent)) {
            $this->setChildRecordClass($firstIndent + [
                'colorize-class' => $useColors ? 'colorize-wrapper-tags' : '',
            ]);
        }

        $GLOBALS['WrapperTags']['indents'] = $this->offsetIndentsForChildRecordCallback($indents, $useColors);

        if ((bool) $this->framework->getAdapter(Config::class)->get('wt_hide_validation_status')) {
            return $header;
        }

        $title = $this->translator->trans('MSC.wt.statusTitle', [], 'contao_default');
        $status = null === $validation['error']
            ? $this->translator->trans('MSC.wt.statusOk', [], 'contao_default')
            : '<span class="tl_red">' . $this->translateError(
                $validation['error']['key'],
                $validation['error']['parameters'],
            ) . '</span>';

        return $header + [$title => $status];
    }

    /**
     * @param array{type: string, value: int, middle?: bool, colorize-class?: string} $indent
     */
    private function setChildRecordClass(array $indent): void
    {
        $classes = [
            'clear-indent',
            'indent_' . max(0, $indent['value']),
        ];

        if (\in_array($indent['type'], [WrapperTagType::START, WrapperTagType::STOP], true)) {
            $classes[] = 'wrapper-tag';
        }

        if ($indent['value'] > 0) {
            $classes[] = 'indent';
        }

        if ($indent['middle'] ?? false) {
            $classes[] = 'indent-tags-closing-middle';
        }

        if ('' !== ($indent['colorize-class'] ?? '')) {
            $classes[] = $indent['colorize-class'];
        }

        $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_class'] = implode(' ', $classes);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{string, string, string}
     */
    private function generateChildRecord(array $row): array
    {
        $type = $this->generateContentTypeLabel($row);
        $model = $this->framework->createInstance(ContentModel::class);
        $model->setRow($row);

        try {
            $preview = StringUtil::insertTagToSrc(
                $this->framework->getAdapter(Controller::class)->getContentElement($model),
            );
        } catch (\Throwable $throwable) {
            $preview = '<p class="tl_error">' . StringUtil::specialchars($throwable->getMessage()) . '</p>';
        }

        if (!empty($row['sectionHeadline'])) {
            $sectionHeadline = StringUtil::deserialize($row['sectionHeadline'], true);

            if (!empty($sectionHeadline['value']) && !empty($sectionHeadline['unit'])) {
                $preview = sprintf(
                    '<%1$s>%2$s</%1$s>%3$s',
                    $sectionHeadline['unit'],
                    $sectionHeadline['value'],
                    $preview,
                );
            }
        }

        if ('' === trim((string) preg_replace('/<!--(.|\s)*?-->/', '', $preview))) {
            $preview = '';
        }

        return [
            $type,
            $preview,
            !empty($row['invisible']) ? 'unpublished' : 'published',
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function generateContentTypeLabel(array $row): string
    {
        $translationId = 'CTE.' . $row['type'] . '.0';
        $type = $this->translator->trans($translationId, [], 'contao_default');

        if ($translationId === $type) {
            $type = (string) $row['type'];
        }

        if (!empty($row['title'])) {
            $type = $row['title'] . ' <span class="tl_gray">[' . $type . ']</span>';
        }

        if ('alias' === $row['type']) {
            $type .= ' ID ' . ($row['cteAlias'] ?? 0);
        }

        if (!empty($row['protected'])) {
            $groupIds = array_map('intval', StringUtil::deserialize($row['groups'] ?? null, true));
            $groupNames = [];

            if (false !== ($guestIndex = array_search(-1, $groupIds, true))) {
                $groupNames[] = $this->translator->trans('MSC.guests', [], 'contao_default');
                unset($groupIds[$guestIndex]);
            }

            if ([] !== $groupIds) {
                $memberGroupAdapter = $this->framework->getAdapter(MemberGroupModel::class);

                if (null !== ($groups = $memberGroupAdapter->findMultipleByIds($groupIds))) {
                    $groupNames = [...$groupNames, ...$groups->fetchEach('name')];
                }
            }

            $type = $this->framework->getAdapter(Image::class)->getHtml('protected.svg') . ' ' . $type;
            $type .= ' <span class="tl_gray">(' . $this->translator->trans(
                'MSC.protected',
                [],
                'contao_default',
            ) . ($groupNames ? ': ' . implode(', ', $groupNames) : '') . ')</span>';
        }

        if ('headline' === $row['type'] && \is_array($headline = StringUtil::deserialize($row['headline'] ?? null))) {
            $type .= ' (' . ($headline['unit'] ?? '') . ')';
        }

        $config = $this->framework->getAdapter(Config::class);
        $date = $this->framework->getAdapter(Date::class);
        $start = $row['start'] ?? null;
        $stop = $row['stop'] ?? null;

        if ($start && $stop) {
            $type .= ' <span class="tl_gray">(' . $this->translator->trans(
                'MSC.showFromTo',
                [$date->parse($config->get('datimFormat'), $start), $date->parse($config->get('datimFormat'), $stop)],
                'contao_default',
            ) . ')</span>';
        } elseif ($start) {
            $type .= ' <span class="tl_gray">(' . $this->translator->trans(
                'MSC.showFrom',
                [$date->parse($config->get('datimFormat'), $start)],
                'contao_default',
            ) . ')</span>';
        } elseif ($stop) {
            $type .= ' <span class="tl_gray">(' . $this->translator->trans(
                'MSC.showTo',
                [$date->parse($config->get('datimFormat'), $stop)],
                'contao_default',
            ) . ')</span>';
        }

        return $type;
    }

    private function hasWrapperTagColumns(): bool
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();

            if (!$schemaManager->tablesExist(['tl_content'])) {
                return false;
            }

            $columns = $schemaManager->listTableColumns('tl_content');

            return isset($columns['wt_opening_tags'], $columns['wt_closing_tags']);
        } catch (\Throwable) {
            return false;
        }
    }

    private function getListOffset(DataContainer $dataContainer): int
    {
        try {
            $property = (new \ReflectionObject($dataContainer))->getProperty('limit');
            $limit = (string) $property->getValue($dataContainer);

            if ('' === $limit) {
                return 0;
            }

            return max(0, (int) explode(',', $limit, 2)[0]);
        } catch (\ReflectionException) {
            return 0;
        }
    }

    /**
     * @param array<int, array{type: string, value: int, middle?: bool}> $indents
     *
     * @return array<int, array{type: string, value: int, middle?: bool, colorize-class?: string}>
     */
    private function offsetIndentsForChildRecordCallback(array $indents, bool $useColors): array
    {
        if ([] === $indents) {
            return [];
        }

        $nextIndent = end($indents);
        $reversed = array_reverse($indents, true);

        foreach ($reversed as $id => $indent) {
            $currentIndent = $indent;
            $reversed[$id] = $nextIndent + [
                'colorize-class' => $useColors ? 'colorize-wrapper-tags' : '',
            ];
            $nextIndent = $currentIndent;
        }

        return array_reverse($reversed, true);
    }

    /**
     * @param list<int|string> $parameters
     */
    private function translateError(string $key, array $parameters): string
    {
        $message = $this->translator->trans('MSC.' . $key, [], 'contao_default');

        return [] === $parameters ? $message : sprintf($message, ...$parameters);
    }
}
