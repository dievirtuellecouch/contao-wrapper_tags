<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zmyslny\WrapperTags\EventListener\ContentListener;
use Zmyslny\WrapperTags\Migration\WrapperTagsClassMigration;
use Zmyslny\WrapperTags\Migration\WrapperTagsTypeMigration;
use Zmyslny\WrapperTags\Util\TagNormalizer;
use Zmyslny\WrapperTags\Validation\WrapperStructureValidator;
use Zmyslny\WrapperTags\WrapperTagType;

$autoloadCandidates = [
    \dirname(__DIR__, 3) . '/vendor/autoload.php',
    \dirname(__DIR__) . '/vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

if (!class_exists(TagNormalizer::class)) {
    throw new RuntimeException('Composer autoload could not load the wrapper tags package.');
}

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "%s\nExpected: %s\nActual: %s",
            $message,
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
};

$tests = [];

$tests['normalizes serialized, JSON and associative data'] = static function () use ($assertSame): void {
    $serialized = serialize([[
        'tag' => 'DIV',
        'class' => 'layout',
        'attributes' => [
            ['name' => 'class', 'value' => 'accent'],
            ['name' => 'data-id', 'value' => '42'],
        ],
    ]]);

    $assertSame(
        [[
            'tag' => 'div',
            'class' => 'layout accent',
            'attributes' => [['name' => 'data-id', 'value' => '42']],
        ]],
        TagNormalizer::normalize($serialized),
        'Serialized wrapper data was not normalized.',
    );

    $assertSame(
        'span',
        TagNormalizer::normalize('[{"tag":"span","attributes":[]}]')[0]['tag'] ?? null,
        'JSON wrapper data was not normalized.',
    );

    $assertSame(
        [['name' => 'data-key', 'value' => 'value']],
        TagNormalizer::normalize([
            ['tag' => 'div', 'attributes' => ['data-key' => 'value']],
        ])[0]['attributes'] ?? null,
        'Associative attributes were not normalized.',
    );
};

$tests['filters unsafe rendering data and keeps void flags'] = static function () use ($assertSame): void {
    $renderable = TagNormalizer::normalizeForRendering([
        [
            'tag' => 'img',
            'void' => '1',
            'attributes' => [
                ['name' => 'src', 'value' => 'image.jpg'],
                ['name' => 'on load', 'value' => 'bad'],
            ],
        ],
        ['tag' => 'script><script', 'attributes' => []],
    ], true);

    $assertSame(1, count($renderable), 'Unsafe tag names were not removed.');
    $assertSame(true, $renderable[0]['void'], 'Void flag was not retained.');
    $assertSame(
        [['name' => 'src', 'value' => 'image.jpg']],
        $renderable[0]['attributes'],
        'Unsafe attribute names were not removed.',
    );
};

$tests['validates balanced, mismatched and invisible wrapper structures'] = static function () use ($assertSame): void {
    $validator = new WrapperStructureValidator();
    $startTypes = [WrapperTagType::START, 'element_group'];
    $stopTypes = [WrapperTagType::STOP, 'element_group_stop'];
    $opening = serialize([
        ['tag' => 'div', 'attributes' => [], 'class' => 'outer'],
        ['tag' => 'span', 'attributes' => [], 'class' => 'inner'],
    ]);
    $closing = serialize([
        ['tag' => 'span'],
        ['tag' => 'div'],
    ]);

    $valid = $validator->validate([
        ['id' => 1, 'type' => WrapperTagType::START, 'invisible' => '', 'wt_opening_tags' => $opening],
        ['id' => 2, 'type' => 'text', 'invisible' => ''],
        ['id' => 3, 'type' => WrapperTagType::STOP, 'invisible' => '', 'wt_closing_tags' => $closing],
    ], $startTypes, $stopTypes);

    $assertSame(null, $valid['error'], 'Balanced wrapper tags were reported as invalid.');
    $assertSame(2, $valid['indents'][2]['value'], 'Nested content indentation is incorrect.');

    $invalid = $validator->validate([
        ['id' => 4, 'type' => WrapperTagType::START, 'invisible' => '', 'wt_opening_tags' => serialize([['tag' => 'div']])],
        ['id' => 5, 'type' => WrapperTagType::STOP, 'invisible' => '', 'wt_closing_tags' => serialize([['tag' => 'span']])],
    ], $startTypes, $stopTypes);

    $assertSame(
        'wt.statusOpeningWrongPairing',
        $invalid['error']['key'] ?? null,
        'Mismatched wrapper tags were not detected.',
    );

    $invisible = $validator->validate([
        ['id' => 6, 'type' => WrapperTagType::START, 'invisible' => '1', 'wt_opening_tags' => serialize([['tag' => 'div']])],
        ['id' => 7, 'type' => 'text', 'invisible' => ''],
    ], $startTypes, $stopTypes);

    $assertSame(null, $invisible['error'], 'Invisible wrapper tags changed validation state.');
    $assertSame(0, $invisible['indents'][7]['value'], 'Invisible wrapper tags changed indentation.');
};

$tests['validates and serializes backend widget input'] = static function () use ($assertSame): void {
    $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    $framework = (new ReflectionClass(Contao\CoreBundle\Framework\ContaoFramework::class))
        ->newInstanceWithoutConstructor();
    $translator = new class implements TranslatorInterface {
        public function trans(
            ?string $id,
            array $parameters = [],
            ?string $domain = null,
            ?string $locale = null,
        ): string {
            return (string) $id;
        }

        public function getLocale(): string
        {
            return 'en';
        }
    };
    $listener = new ContentListener(
        $connection,
        $framework,
        $translator,
        new WrapperStructureValidator(),
    );
    $dataContainer = new class extends Contao\DataContainer {
        public function __construct()
        {
        }

        public function getPalette(): string
        {
            return '';
        }

        protected function save(mixed $varValue): void
        {
        }
    };
    $dataContainer->field = 'wt_complete_tags';

    $serialized = $listener->onSaveCallback([[
        'tag' => 'IMG',
        'void' => '1',
        'attributes' => [['name' => 'src', 'value' => ' image.jpg ']],
    ]], $dataContainer);
    $tags = Contao\StringUtil::deserialize($serialized, true);

    $assertSame('img', $tags[0]['tag'] ?? null, 'Backend input tag was not normalized.');
    $assertSame(true, $tags[0]['void'] ?? null, 'Backend input void flag was not retained.');
    $assertSame('image.jpg', $tags[0]['attributes'][0]['value'] ?? null, 'Attribute value was not trimmed.');

    try {
        $listener->onSaveCallback([[
            'tag' => 'div',
            'attributes' => [
                ['name' => 'data-id', 'value' => '1'],
                ['name' => 'DATA-ID', 'value' => '2'],
            ],
        ]], $dataContainer);
    } catch (InvalidArgumentException) {
        return;
    }

    throw new RuntimeException('Duplicate backend attributes were accepted.');
};

$tests['migrates legacy element types transactionally'] = static function () use ($assertSame): void {
    $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    $connection->executeStatement('CREATE TABLE tl_content (id INTEGER PRIMARY KEY, type VARCHAR(64) NOT NULL)');
    $connection->insert('tl_content', ['id' => 1, 'type' => WrapperTagType::LEGACY_START]);
    $connection->insert('tl_content', ['id' => 2, 'type' => WrapperTagType::LEGACY_STOP]);
    $connection->insert('tl_content', ['id' => 3, 'type' => WrapperTagType::LEGACY_COMPLETE]);

    $migration = new WrapperTagsTypeMigration($connection);
    $assertSame(true, $migration->shouldRun(), 'Legacy type migration was not detected.');
    $migration->run();

    $assertSame(
        [WrapperTagType::START, WrapperTagType::STOP, WrapperTagType::COMPLETE],
        $connection->fetchFirstColumn('SELECT type FROM tl_content ORDER BY id'),
        'Legacy wrapper tag types were not migrated.',
    );
    $assertSame(false, $migration->shouldRun(), 'Type migration remained pending after completion.');
};

$tests['migrates legacy CSS classes without overwriting existing data'] = static function () use ($assertSame): void {
    $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    $connection->executeStatement(
        'CREATE TABLE tl_content (
            id INTEGER PRIMARY KEY,
            type VARCHAR(64) NOT NULL,
            cssID TEXT NULL,
            wt_opening_tags BLOB NULL,
            wt_complete_tags BLOB NULL
        )',
    );
    $connection->insert('tl_content', [
        'id' => 1,
        'type' => WrapperTagType::START,
        'cssID' => serialize(['', 'legacy-class']),
        'wt_opening_tags' => serialize([['tag' => 'div', 'class' => '', 'attributes' => []]]),
        'wt_complete_tags' => null,
    ]);
    $connection->insert('tl_content', [
        'id' => 2,
        'type' => WrapperTagType::COMPLETE,
        'cssID' => serialize(['', 'must-not-overwrite']),
        'wt_opening_tags' => null,
        'wt_complete_tags' => serialize([['tag' => 'span', 'class' => 'existing', 'attributes' => []]]),
    ]);

    $migration = new WrapperTagsClassMigration($connection);
    $assertSame(true, $migration->shouldRun(), 'Legacy class migration was not detected.');
    $migration->run();

    $openingTags = Contao\StringUtil::deserialize(
        $connection->fetchOne('SELECT wt_opening_tags FROM tl_content WHERE id = 1'),
        true,
    );
    $completeTags = Contao\StringUtil::deserialize(
        $connection->fetchOne('SELECT wt_complete_tags FROM tl_content WHERE id = 2'),
        true,
    );

    $assertSame('legacy-class', $openingTags[0]['class'] ?? null, 'Legacy CSS class was not moved.');
    $assertSame('existing', $completeTags[0]['class'] ?? null, 'Existing CSS class was overwritten.');
    $assertSame(false, $migration->shouldRun(), 'Class migration remained pending after completion.');
};

$failures = [];

foreach ($tests as $name => $test) {
    try {
        $test();
        fwrite(STDOUT, sprintf("PASS %s\n", $name));
    } catch (Throwable $throwable) {
        $failures[] = $name;
        fwrite(STDERR, sprintf("FAIL %s: %s\n", $name, $throwable->getMessage()));
    }
}

if ([] !== $failures) {
    fwrite(STDERR, sprintf("%d test(s) failed.\n", count($failures)));
    exit(1);
}

fwrite(STDOUT, sprintf("All %d wrapper tag tests passed.\n", count($tests)));
