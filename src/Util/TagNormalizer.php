<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Util;

use Contao\StringUtil;

final class TagNormalizer
{
    private const TAG_NAME_PATTERN = '/^[a-z][a-z0-9:-]*$/';

    private const ATTRIBUTE_NAME_PATTERN = '/^[A-Za-z]+[\w\-:.]*(\{{2}[\w:]+\}{2}[\w\-:.]*){0,10}$/';

    /**
     * @return list<array{tag: string, class: string, attributes: list<array{name: string, value: string}>, void?: bool}>
     */
    public static function normalize(mixed $raw, bool $includeVoid = false): array
    {
        $tags = null;

        if (\is_string($raw)) {
            $trimmed = ltrim($raw);

            if ('' !== $trimmed && ('[' === $trimmed[0] || '{' === $trimmed[0])) {
                $decoded = json_decode($raw, true);

                if (\is_array($decoded)) {
                    $tags = $decoded;
                }
            }
        }

        $tags ??= StringUtil::deserialize($raw, true);

        if (!\is_array($tags) || (isset($tags['tag']) && \is_string($tags['tag']))) {
            if (\is_array($tags) && isset($tags['tag']) && \is_string($tags['tag'])) {
                $tags = [$tags];
            }
        }

        if (!\is_array($tags)) {
            return [];
        }

        $normalized = [];

        foreach ($tags as $tag) {
            if (!\is_array($tag)) {
                continue;
            }

            $tagName = strtolower(trim((string) ($tag['tag'] ?? '')));

            if ($tagName === '') {
                continue;
            }

            $class = trim((string) ($tag['class'] ?? ''));
            $attributes = self::normalizeAttributes($tag['attributes'] ?? []);

            foreach ($attributes as $index => $attribute) {
                if (strtolower($attribute['name']) === 'class') {
                    $class = trim($class . ' ' . $attribute['value']);
                    unset($attributes[$index]);
                }
            }

            $entry = [
                'tag' => $tagName,
                'class' => $class,
                'attributes' => array_values($attributes),
            ];

            if ($includeVoid) {
                $entry['void'] = !empty($tag['void']);
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    /**
     * Normalize stored data and remove unsafe tag or attribute names before rendering.
     *
     * @return list<array{tag: string, class: string, attributes: list<array{name: string, value: string}>, void?: bool}>
     */
    public static function normalizeForRendering(mixed $raw, bool $includeVoid = false): array
    {
        $tags = self::normalize($raw, $includeVoid);

        foreach ($tags as $tagIndex => &$tag) {
            if (!self::isValidTagName($tag['tag'])) {
                unset($tags[$tagIndex]);

                continue;
            }

            $tag['attributes'] = array_values(array_filter(
                $tag['attributes'],
                static fn (array $attribute): bool => self::isValidAttributeName($attribute['name']),
            ));
        }
        unset($tag);

        return array_values($tags);
    }

    public static function isValidTagName(string $name): bool
    {
        return 1 === preg_match(self::TAG_NAME_PATTERN, $name);
    }

    public static function isValidAttributeName(string $name): bool
    {
        return 1 === preg_match(self::ATTRIBUTE_NAME_PATTERN, $name);
    }

    private static function normalizeAttributes(mixed $attributes): array
    {
        if (!\is_array($attributes)) {
            return [];
        }

        if (self::isAssoc($attributes)) {
            $attributes = array_map(
                static fn (string|int $name, mixed $value): array => [
                    'name' => (string) $name,
                    'value' => (string) $value,
                ],
                array_keys($attributes),
                $attributes
            );
        }

        $normalized = [];

        foreach ($attributes as $attribute) {
            if (!\is_array($attribute)) {
                continue;
            }

            $name = trim((string) ($attribute['name'] ?? ''));
            $value = trim((string) ($attribute['value'] ?? ''));

            if ($name === '' && $value === '') {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return $normalized;
    }

    private static function isAssoc(array $array): bool
    {
        foreach (array_keys($array) as $key) {
            if (!\is_int($key)) {
                return true;
            }
        }

        return false;
    }
}
