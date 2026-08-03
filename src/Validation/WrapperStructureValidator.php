<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Validation;

use Zmyslny\WrapperTags\Util\TagNormalizer;
use Zmyslny\WrapperTags\WrapperTagType;

final class WrapperStructureValidator
{
    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string>               $startTypes
     * @param list<string>               $stopTypes
     *
     * @return array{
     *     indents: array<int, array{type: string, value: int, middle?: bool}>,
     *     error: array{key: string, parameters: list<int|string>}|null
     * }
     */
    public function validate(array $rows, array $startTypes, array $stopTypes): array
    {
        $indents = [];
        $stack = [];
        $indentLevel = 0;
        $error = null;

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $type = (string) ($row['type'] ?? '');
            $isVisible = '1' !== (string) ($row['invisible'] ?? '');

            if (\in_array($type, $startTypes, true)) {
                $indents[$id] = ['type' => $type, 'value' => $indentLevel];

                if (!$isVisible) {
                    continue;
                }

                if (WrapperTagType::START !== $type) {
                    $stack[] = ['id' => $id, 'type' => $type];
                    ++$indentLevel;

                    continue;
                }

                $openingTags = TagNormalizer::normalize($row['wt_opening_tags'] ?? null);

                if ([] === $openingTags) {
                    $error ??= ['key' => 'wt.dataCorrupted', 'parameters' => []];

                    continue;
                }

                foreach ($openingTags as $tag) {
                    $stack[] = [
                        'id' => $id,
                        'type' => WrapperTagType::START,
                        'tag' => $tag['tag'],
                    ];
                    ++$indentLevel;
                }

                continue;
            }

            if (\in_array($type, $stopTypes, true)) {
                if (WrapperTagType::STOP !== $type) {
                    $opening = end($stack);

                    if ($isVisible && false !== $opening) {
                        if (WrapperTagType::START === $opening['type']) {
                            $error ??= [
                                'key' => 'wt.statusOpeningWrongPairingWithOther',
                                'parameters' => [$opening['tag'], $opening['id'], $type, $id],
                            ];
                        }

                        array_pop($stack);
                        $indentLevel = max(0, $indentLevel - 1);
                    }

                    $indents[$id] = ['type' => $type, 'value' => $indentLevel];

                    continue;
                }

                $closingTags = TagNormalizer::normalize($row['wt_closing_tags'] ?? null);
                $indents[$id] = [
                    'type' => $type,
                    'value' => $isVisible ? max(0, $indentLevel - max(1, count($closingTags))) : $indentLevel,
                ];

                if (!$isVisible) {
                    continue;
                }

                if ([] === $closingTags) {
                    $error ??= ['key' => 'wt.dataCorrupted', 'parameters' => []];

                    continue;
                }

                $lastOpeningId = null;

                foreach ($closingTags as $closingTag) {
                    $opening = end($stack);

                    if (false === $opening) {
                        $error ??= [
                            'key' => 'wt.statusClosingNoOpening',
                            'parameters' => [$closingTag['tag'], $id],
                        ];

                        break;
                    }

                    if (WrapperTagType::START !== $opening['type']) {
                        $error ??= [
                            'key' => 'wt.statusClosingWrongPairingWithOther',
                            'parameters' => [$closingTag['tag'], $id, $opening['type'], $opening['id']],
                        ];

                        break;
                    }

                    if ($opening['tag'] !== $closingTag['tag']) {
                        $error ??= [
                            'key' => 'wt.statusOpeningWrongPairing',
                            'parameters' => [$opening['tag'], $opening['id'], $closingTag['tag'], $id],
                        ];

                        break;
                    }

                    $lastOpeningId = $opening['id'];
                    array_pop($stack);
                    $indentLevel = max(0, $indentLevel - 1);
                }

                $remainingOpening = end($stack);

                if (
                    null !== $lastOpeningId
                    && false !== $remainingOpening
                    && WrapperTagType::START === $remainingOpening['type']
                    && $lastOpeningId === $remainingOpening['id']
                ) {
                    $indents[$id]['middle'] = true;
                }

                continue;
            }

            $indents[$id] = ['type' => $type, 'value' => $indentLevel];
        }

        if (null === $error) {
            for ($index = count($stack) - 1; $index >= 0; --$index) {
                if (WrapperTagType::START !== $stack[$index]['type']) {
                    continue;
                }

                $error = [
                    'key' => 'wt.statusOpeningNoClosing',
                    'parameters' => [$stack[$index]['tag'], $stack[$index]['id']],
                ];

                break;
            }
        }

        return [
            'indents' => $indents,
            'error' => $error,
        ];
    }
}
