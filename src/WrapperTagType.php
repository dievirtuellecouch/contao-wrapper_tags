<?php

declare(strict_types=1);

namespace Zmyslny\WrapperTags;

final class WrapperTagType
{
    public const START = 'wrapper_tag_start';
    public const STOP = 'wrapper_tag_stop';
    public const COMPLETE = 'wrapper_tag_complete';

    public const LEGACY_START = 'wt_opening_tags';
    public const LEGACY_STOP = 'wt_closing_tags';
    public const LEGACY_COMPLETE = 'wt_complete_tags';

    public const LEGACY_TO_CURRENT = [
        self::LEGACY_START => self::START,
        self::LEGACY_STOP => self::STOP,
        self::LEGACY_COMPLETE => self::COMPLETE,
    ];

    private function __construct()
    {
    }
}
