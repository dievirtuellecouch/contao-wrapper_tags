<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags\Util;

use Contao\StringUtil;

final class TagNormalizer
{
    public static function normalize(mixed $raw, bool $includeVoid = false): array
    {
        $tags = StringUtil::deserialize($raw, true);

        if (!\is_array($tags) || (isset($tags['tag']) && \is_string($tags['tag']))) {
            $decoded = null;

            if (\is_string($raw)) {
                $trimmed = ltrim($raw);

                if ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
                    $decoded = json_decode($raw, true);
                }
            }

            if (\is_array($decoded)) {
                $tags = $decoded;
            } elseif (\is_array($tags) && isset($tags['tag']) && \is_string($tags['tag'])) {
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
                if ($class === '' && strtolower($attribute['name']) === 'class') {
                    $class = $attribute['value'];
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

            $name = preg_replace('/\s+/', '', (string) ($attribute['name'] ?? ''));
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
