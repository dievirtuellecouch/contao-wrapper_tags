<?php

declare(strict_types=1);

use Contao\ContentModel;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zmyslny\WrapperTags\Controller\ContentElement\WrapperTagCompleteController;
use Zmyslny\WrapperTags\Controller\ContentElement\WrapperTagStartController;
use Zmyslny\WrapperTags\Controller\ContentElement\WrapperTagStopController;
use Zmyslny\WrapperTags\EventListener\ContentListener;
use Zmyslny\WrapperTags\WrapperTagType;

$projectDirectory = dirname(__DIR__, 3);
$composerLoader = require $projectDirectory . '/vendor/autoload.php';
$composerLoader->addPsr4('Zmyslny\\WrapperTags\\', \dirname(__DIR__) . '/src/', true);

$_SERVER['DISABLE_HTTP_CACHE'] = '1';
$kernel = ContaoKernel::fromInput($projectDirectory, new ArrayInput([]));
$kernel->boot();
$container = $kernel->getContainer();
$container->get('contao.framework')->initialize();

/** @var RequestStack $requestStack */
$requestStack = $container->get('request_stack');
$request = Request::create('https://example.test/');
$request->attributes->set('_scope', 'frontend');
$requestStack->push($request);

$cases = [
    [
        WrapperTagStartController::class,
        'wt_opening_tags',
        serialize([[
            'tag' => 'section',
            'class' => 'test-wrapper another-class',
            'attributes' => [['name' => 'data-id', 'value' => '42']],
        ]]),
        '<section data-id="42" class="test-wrapper another-class">',
    ],
    [
        WrapperTagStopController::class,
        'wt_closing_tags',
        serialize([['tag' => 'section']]),
        '</section>',
    ],
    [
        WrapperTagCompleteController::class,
        'wt_complete_tags',
        serialize([
            ['tag' => 'span', 'class' => 'complete', 'attributes' => [], 'void' => ''],
            ['tag' => 'img', 'class' => '', 'attributes' => [['name' => 'src', 'value' => 'image.jpg']], 'void' => '1'],
        ]),
        '<span class="complete"></span><img src="image.jpg">',
    ],
];

foreach ($cases as [$serviceId, $field, $value, $expected]) {
    $model = new ContentModel();
    $model->setRow([
        'id' => 999,
        'type' => 'test',
        'customTpl' => '',
        'cssID' => '',
        $field => $value,
    ]);

    $response = $container->get($serviceId)($request, $model, 'main');
    $actual = preg_replace('/\s+/', '', $response->getContent());
    $normalizedExpected = preg_replace('/\s+/', '', $expected);

    if ($normalizedExpected !== $actual) {
        throw new RuntimeException(sprintf(
            "Unexpected output for %s.\nExpected: %s\nActual: %s",
            $serviceId,
            $normalizedExpected,
            $actual,
        ));
    }

    fwrite(STDOUT, sprintf("PASS %s\n", $serviceId));
}

$requestStack->pop();
$backendRequest = Request::create('https://example.test/contao?do=article');
$backendRequest->attributes->set('_scope', 'backend');
$requestStack->push($backendRequest);

$editorModel = new ContentModel();
$editorModel->setRow([
    'id' => 999,
    'type' => 'wrapper_tag_complete',
    'customTpl' => '',
    'cssID' => '',
    'wt_complete_tags' => serialize([[
        'tag' => 'span',
        'class' => 'complete',
        'attributes' => [],
        'void' => '',
    ]]),
]);
$editorResponse = $container->get(WrapperTagCompleteController::class)(
    $backendRequest,
    $editorModel,
    'main',
);

if (
    !str_contains($editorResponse->getContent(), 'class="tl_content"')
    || !str_contains($editorResponse->getContent(), '&lt;span')
) {
    throw new RuntimeException('The wrapper tag editor view was not rendered correctly.');
}

fwrite(STDOUT, "PASS wrapper tag editor view\n");

$listener = $container->get(ContentListener::class);
$allowedTags = $listener->getTags();

if (!in_array('div', $allowedTags, true) || !in_array('span', $allowedTags, true)) {
    throw new RuntimeException('The configured wrapper tag options are incomplete.');
}

if ('tag' !== array_key_first($listener->onClosingTagsColumnsCallback())) {
    throw new RuntimeException('The closing tag MultiColumnWizard columns are invalid.');
}

$connection = $container->get('database_connection');
$wrapperRow = $connection->fetchAssociative(
    'SELECT * FROM tl_content WHERE type = ? ORDER BY id LIMIT 1',
    [WrapperTagType::START],
);

if (false !== $wrapperRow) {
    $dataContainer = (new ReflectionClass(Contao\DC_Table::class))->newInstanceWithoutConstructor();

    foreach ([
        [Contao\DataContainer::class, 'intCurrentPid', (int) $wrapperRow['pid']],
        [Contao\DataContainer::class, 'intId', (int) $wrapperRow['pid']],
        [Contao\DC_Table::class, 'ptable', (string) $wrapperRow['ptable']],
        [Contao\DC_Table::class, 'limit', ''],
    ] as [$class, $property, $value]) {
        $reflectionProperty = (new ReflectionClass($class))->getProperty($property);
        $reflectionProperty->setValue($dataContainer, $value);
    }

    $header = $listener->onHeaderCallback([], $dataContainer);

    if ([] === ($GLOBALS['WrapperTags']['indents'] ?? []) || [] === $header) {
        throw new RuntimeException('Backend wrapper structure validation did not produce a result.');
    }

    if ([] === $listener->onLabelCallback($wrapperRow, '', $dataContainer)) {
        throw new RuntimeException('Backend wrapper label rendering returned no output.');
    }
}

fwrite(STDOUT, "PASS wrapper tag backend callbacks\n");

$requestStack->pop();
$kernel->shutdown();
