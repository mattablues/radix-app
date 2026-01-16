<?php

declare(strict_types=1);

namespace Radix\Tests\Viewer;

use PHPUnit\Framework\TestCase;
use Radix\Viewer\RadixTemplateViewer;
use ReflectionClass;
use RuntimeException;

class TestViewLogger extends \Radix\Support\Logger
{
    /** @var list<string> */
    public array $messages = [];

    public function __construct()
    {
        // Hoppa över förälderns konstruktor för att undvika fil-I/O
    }

    /**
     * @param string $message
     * @param array<string,mixed> $context
     */
    public function debug($message, array $context = []): void
    {
        $this->messages[] = (string) $message;
    }
}

final class MarkableObject
{
    public bool $marked = false;
}

class RadixTemplateViewerTest extends TestCase
{
    private RadixTemplateViewer $viewer;
    private string $tempRootPath;
    private string $tempViewsPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Skapa temporär katalog för templates
        $this->tempRootPath = sys_get_temp_dir() . '/radix_test/';
        $this->tempViewsPath = $this->tempRootPath . 'views/';

        $this->createDirectoryIfNotExists($this->tempViewsPath);
        $this->createDirectoryIfNotExists($this->tempViewsPath . '/components');

        // Definiera ROOT_PATH om ej definierad
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $this->tempRootPath);
        }

        // Rikta CACHE_PATH till testets temporära cachekatalog
        putenv('CACHE_PATH=' . $this->tempRootPath . 'cache/views');

        $this->viewer = new RadixTemplateViewer($this->tempViewsPath);
        $this->viewer->enableDebugMode(false);
    }

    protected function tearDown(): void
    {
        // Rensa temporära kataloger och filer
        $this->deleteDirectory($this->tempRootPath);
        parent::tearDown();
    }

    public function testAdvancedAlertComponent(): void
    {
        $componentPath = "{$this->tempViewsPath}components/alert.ratio.php";
        file_put_contents(
            $componentPath,
            '
            <div class="alert alert-{{ $type }} {{ $class ?? \'\' }}" style="{{ $style ?? \'\' }}">
                {% if(isset($header)) : %}
                    <div class="alert-header">{{ $header }}</div>
                {% endif; %}
                <div class="alert-body">{{ $slot }}</div>
                {% if(isset($footer)) : %}
                    <div class="alert-footer">{{ $footer }}</div>
                {% endif; %}
            </div>'
        );

        $this->assertFileExists($componentPath, '[DEBUG] Komponentfilen saknas: ' . $componentPath);

        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents(
            $templatePath,
            '
            <x-alert type="warning" class="my-4" style="color: red;">
                <x-slot:header>This is the header</x-slot:header>
                This is the alert content.
                <x-slot:footer>This is the footer</x-slot:footer>
            </x-alert>'
        );

        $output = $this->viewer->render('test_template');

        // Förväntad HTML-output, utan fokus på mönster av radbrytningar
        $expectedOutput = '<div class="alert alert-warning my-4" style="color: red;">
            <div class="alert-header">This is the header</div>
            <div class="alert-body">This is the alert content.</div>
            <div class="alert-footer">This is the footer</div>
        </div>';

        // Normalisera både förväntad och faktisk output
        $normalizedExpectedOutput = preg_replace('/\s+/', ' ', trim($expectedOutput));
        $normalizedOutput = preg_replace('/\s+/', ' ', trim($output));

        // Jämför normaliserad output
        $this->assertSame(
            $normalizedExpectedOutput,
            $normalizedOutput,
            '[DEBUG] Alert-komponenten renderades inte korrekt.'
        );
    }

    public function testItInjectsBlocksIntoDataArray(): void
    {
        // 1. Skapa en layout som använder variablerna
        $layoutPath = $this->tempViewsPath . 'layout.ratio.php';
        file_put_contents($layoutPath, 'ID:{{ $pageId }} CLASS:{{ $pageClass }} SEARCH:{{ $searchId }}');

        // 2. Skapa en mall som ärver layouten och definierar blocken med extra mellanslag/radbrytningar
        $templatePath = $this->tempViewsPath . 'inject-test.ratio.php';
        file_put_contents(
            $templatePath,
            '{% extends "layout.ratio.php" %}
             {% block pageId %}
                777 
             {% endblock %}
             {% block pageClass %}  my-class  {% endblock %}
             {% block searchId %}	search-99	{% endblock %}'
        );

        $result = $this->viewer->render('inject-test');

        // Verifiera att värdena injicerats korrekt OCH att de har trimmats.
        // Om trim() tas bort kommer strängen innehålla radbrytningar och tabbar,
        // vilket gör att denna assertion failar.
        $this->assertStringContainsString('ID:777 CLASS:my-class SEARCH:search-99', $result);
    }

    public function testItSetsDefaultEmptyStringForMissingInjectableBlocks(): void
    {
        // En mall utan block och utan extends (så vi slipper syntaxfel från kvarlämnade block)
        $templatePath = $this->tempViewsPath . 'empty-inject.ratio.php';
        file_put_contents(
            $templatePath,
            'VALUES:{{ $pageId }}{{ $pageClass }}{{ $searchId }}'
        );

        $result = $this->viewer->render('empty-inject');

        // Detta dödar LogicalNot-mutanten.
        // Om variablerna inte sätts till '' kommer eval() kasta ett fel (Undefined variable).
        $this->assertStringContainsString('VALUES:', $result);
    }

    public function testComponentWithNamedSlotsAndDynamicAttributes(): void
    {
        $componentPath = "{$this->tempViewsPath}components/card_with_slots.ratio.php";
        file_put_contents(
            $componentPath,
            '<div class="card {{ $class }}" style="{{ $style }}">
                <div class="header">{{ $header }}</div>
                <div class="content">{{ $slot }}</div>
                <div class="footer">{{ $footer }}</div>
            </div>'
        );

        $this->assertFileExists($componentPath, '[DEBUG] Komponentfilen saknas: ' . $componentPath);

        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents($templatePath, '
            <x-card_with_slots class="highlight" style="color: red;">
                <x-slot:header>Header Content</x-slot:header>
                Main Content Here
                <x-slot:footer>Footer Content</x-slot:footer>
            </x-card_with_slots>
        ');

        $output = $this->viewer->render('test_template');

        $expectedOutput = '<div class="card highlight" style="color: red;"><div class="header">Header Content</div><div class="content">Main Content Here</div><div class="footer">Footer Content</div></div>';

        // Använd normalizeOutput för att jämföra formateringen
        $this->assertSame(
            $this->normalizeOutput($expectedOutput),
            $this->normalizeOutput($output),
            'Named slots och dynamiska attribut fungerar inte korrekt.'
        );
    }

    public function testEmptySlot(): void
    {
        // Skapa en enkel kort komponent
        $cardPath = "{$this->tempViewsPath}components/card.ratio.php";
        file_put_contents(
            $cardPath,
            '<div class="card">
                <div class="header">{{ $header }}</div>
                <div class="content">{{ $slot }}</div>
            </div>'
        );

        $this->assertFileExists($cardPath, '[DEBUG] Komponentfilen saknas: ' . $cardPath);

        $templatePath = "{$this->tempViewsPath}empty_slot_test_template.ratio.php";
        file_put_contents(
            $templatePath,
            '<x-card>
                <x-slot:header></x-slot:header>
                Main content here.
            </x-card>'
        );

        $output = $this->viewer->render('empty_slot_test_template');

        // Förväntad HTML-output
        $expectedOutput = '<div class="card"><div class="header"></div><div class="content">Main content here.</div></div>';

        // Jämför normaliserad output
        $this->assertSame(
            $this->normalizeOutput($expectedOutput),
            $this->normalizeOutput($output),
            '[DEBUG] Empty slot rendered incorrectly.'
        );
    }

    public function testDynamicAttributes(): void
    {
        $alertPath = "{$this->tempViewsPath}components/alert.ratio.php";
        file_put_contents(
            $alertPath,
            '<div class="alert <?php echo $type; ?>">{{ $slot }}</div>' // Dynamiska PHP-attribut i komponenten
        );

        $templatePath = "{$this->tempViewsPath}dynamic_attribute_test_template.ratio.php";
        file_put_contents(
            $templatePath,
            '<x-alert type="info">Content with dynamic attributes.</x-alert>'
        );

        $output = $this->viewer->render('dynamic_attribute_test_template', ['type' => 'info']);

        // Förväntad output direkt från templatemotorn
        $expectedOutput = '<div class="alert info">Content with dynamic attributes.</div>';

        // Testa med exakt output utan normalisering
        $this->assertSame(
            $expectedOutput,
            $output,
            '[DEBUG] Dynamic attributes rendered incorrectly.'
        );
    }

    public function testGlobalDataRendering(): void
    {
        $cardPath = "{$this->tempViewsPath}components/card.ratio.php";
        file_put_contents(
            $cardPath,
            '<div class="card">
                <div class="header">{{ $header }}</div>
                <div class="content">{{ $slot }}</div>
            </div>'
        );

        $this->assertFileExists($cardPath, '[DEBUG] Komponentfilen saknas: ' . $cardPath);

        $templatePath = "{$this->tempViewsPath}global_data_test_template.ratio.php";
        file_put_contents(
            $templatePath,
            '<x-card>
                <x-slot:header>This is a global title</x-slot:header>
                Main content with global data.
            </x-card>'
        );

        // Registrera globala data
        $this->viewer->shared('header', 'This is a global title');

        // Rendera output
        $output = $this->viewer->render('global_data_test_template');

        // Förväntad HTML-output
        $expectedOutput = '<div class="card"><div class="header">This is a global title</div><div class="content">Main content with global data.</div></div>';

        // Normalisera förväntad och faktisk output
        $this->assertSame(
            $this->normalizeOutput($expectedOutput),
            $this->normalizeOutput($output),
            '[DEBUG] Global data rendered incorrectly.'
        );
    }

    public function testNestedComponentsWithSlots(): void
    {
        $cardPath = "{$this->tempViewsPath}components/card.ratio.php";
        file_put_contents(
            $cardPath,
            '<div class="card">
                <div class="header">{{ $header }}</div>
                <div class="content">{{ $slot }}</div>
            </div>'
        );

        $alertPath = "{$this->tempViewsPath}components/alert.ratio.php";
        file_put_contents(
            $alertPath,
            '<div class="alert">{{ $slot }}</div>'
        );

        $templatePath = "{$this->tempViewsPath}nested_test_template.ratio.php";
        file_put_contents(
            $templatePath,
            '<x-card>
                <x-slot:header>
                    <x-alert>This is an alert in the header</x-alert>
                </x-slot:header>
                Nested content here.
            </x-card>'
        );

        $output = $this->viewer->render('nested_test_template');

        // Förväntad HTML-output exakt som den genereras
        $expectedOutput = '<div class="card">
                <div class="header">&lt;div class=&quot;alert&quot;&gt;This is an alert in the header&lt;/div&gt;</div>
                <div class="content">Nested content here.</div>
            </div>';

        // Jämförelse utan normalisering
        $this->assertSame(
            $expectedOutput,
            $output,
            '[DEBUG] Nested components with slots rendered incorrectly.'
        );
    }

    public function testCacheInvalidation(): void
    {
        // Tvinga cache-läge
        $originalEnv = getenv('APP_ENV');
        putenv('APP_ENV=production');

        // Steg 1: Simulera cache-lagring
        $templatePath = $this->tempViewsPath . 'invalidate_test/template.ratio.php';
        $this->createDirectoryIfNotExists(dirname($templatePath));
        file_put_contents($templatePath, 'Hello {{ $name }}');

        $reflection = new ReflectionClass($this->viewer);
        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);
        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        // Pekar cache till tempRootPath/cache/views/
        $cachePathProperty = $reflection->getProperty('cachePath');
        $cachePathProperty->setAccessible(true);
        $adjustedCachePath = $this->tempRootPath . 'cache/views/';
        $cachePathProperty->setValue($this->viewer, $adjustedCachePath);
        $this->createDirectoryIfNotExists($adjustedCachePath);

        $resolvedTemplatePath = $resolveTemplatePath->invoke($this->viewer, 'invalidate_test/template');
        $data = ['name' => 'InitialName'];
        $cacheKey = $generateCacheKey->invoke($this->viewer, $resolvedTemplatePath, $data);

        if (!is_string($cacheKey)) {
            $this->fail('cacheKey() must return string.');
        }

        /** @var string $cacheKey */
        $cachedFile = "$adjustedCachePath{$cacheKey}.php";

        // Rendera → cache ska skapas
        $this->viewer->render('invalidate_test/template', $data);
        $this->assertFileExists($cachedFile, "DEBUG: Cache file not created at expected path: {$cachedFile}");

        // Steg 2: Invalidera cachen
        $this->viewer->invalidateCache('invalidate_test/template', $data);
        $this->assertFileDoesNotExist($cachedFile, "DEBUG: Cache file was not deleted at path: {$cachedFile}");

        // Steg 3: Rendera om (med uppdaterad data)
        file_put_contents($templatePath, 'Hello {{ $name }}'); // säkerställ att template finns kvar
        $updatedData = ['name' => 'UpdatedName'];
        $output = $this->viewer->render('invalidate_test/template', $updatedData);
        $this->assertSame('Hello UpdatedName', $output);

        // Återställ APP_ENV
        if ($originalEnv === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $originalEnv);
        }
    }

    public function testRenderThrowsExceptionIfTemplateNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template file not found');

        $this->viewer->render('nonexistent_view');
    }

    // tests/Feature/ViewTest.php
    public function testAlpineSyntaxIsRenderedCorrectly(): void
    {
        // Simulera rendering av vyn
        $templatePath = $this->tempViewsPath . 'example.ratio.php';
        file_put_contents($templatePath, '
            <div x-data="{ count: 0 }">
                <button x-on:click="count++">Click me</button>
                <span x-text="count"></span>
            </div>
        ');

        $html = $this->viewer->render('example');

        // Kontrollera att HTML-output innehåller Alpine.js-syntax
        $this->assertStringContainsString('x-data="{ count: 0 }"', $html);
        $this->assertStringContainsString('x-on:click="count++"', $html);
        $this->assertStringContainsString('x-text="count"', $html);
    }

    public function testRenderReturnsRenderedTemplate(): void
    {
        // Skapa en temporär template
        $templatePath = $this->tempViewsPath . 'temp_view.ratio.php';
        file_put_contents($templatePath, 'Hello {{ $name }}!');

        $output = $this->viewer->render('temp_view', ['name' => 'World']);
        $this->assertSame('Hello World!', $output);
    }

    public function testSharedDataIsAvailableGlobally(): void
    {
        // Skapa en temporär template
        $templatePath = $this->tempViewsPath . 'shared_data.ratio.php';
        file_put_contents($templatePath, 'Global variable: {{ $globalVar }}');

        $this->viewer->shared('globalVar', 'GlobalValue'); // Registrera global variabel

        // Verifiera att datan injiceras korrekt
        $output = $this->viewer->render('shared_data');
        $this->assertSame('Global variable: GlobalValue', $output, '[DEBUG] Global variabeln är otillgänglig.');
    }

    public function testGlobalDataAndSlotsWorkTogether(): void
    {
        // Skapa komponentfil
        $componentPath = "{$this->tempViewsPath}components/test_component.ratio.php";
        file_put_contents($componentPath, '<div class="container">{{ $globalVar }} - {{ slot }}</div>');

        // Registrera global variabel
        $this->viewer->shared('globalVar', 'GlobalValue');

        // Skapa template med komponent och slot
        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents($templatePath, '<x-test_component>Slot Content</x-test_component>');

        // Rendera templaten
        $output = $this->viewer->render('test_template');

        $expectedOutput = '<div class="container">GlobalValue - Slot Content</div>';
        $this->assertSame($expectedOutput, $output, 'Globala data eller slot fungerar inte i komponenter.');
    }

    public function testExtendsDirectiveCombinesTemplates(): void
    {
        // Skapa layout och child templates
        $layoutPath = $this->tempViewsPath . 'layouts/main.ratio.php';
        $childPath = $this->tempViewsPath . 'home.ratio.php';

        mkdir(dirname($layoutPath), 0o755, true);
        file_put_contents($layoutPath, '<html>{% yield body %}</html>');
        file_put_contents($childPath, '{% extends "layouts/main.ratio.php" %}{% block body %}<h1>Hello!</h1>{% endblock %}');

        $output = $this->viewer->render('home');
        $this->assertSame('<html><h1>Hello!</h1></html>', $output);
    }

    public function testSlotsWorkWithAlpineSyntax(): void
    {
        // Skapa en temporär komponent med Alpine.js och slot
        $componentPath = "{$this->tempViewsPath}components/alert.ratio.php";
        file_put_contents($componentPath, '
            <div class="alert" x-data="{ show: true }">
                <button x-on:click="show = !show">Toggle</button>
                <div x-show="show">{{ slot }}</div>
            </div>
        ');

        // Kontrollera att komponentfilen skapades korrekt
        $this->assertFileExists($componentPath);

        // Skapa huvudtemplate som använder komponenten och skickar in en slot
        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents($templatePath, '<x-alert>Slot Content Here</x-alert>');

        // Rendera din huvudtemplate
        $output = $this->viewer->render('test_template');

        // Kontrollera att både Alpine.js och slot-innehållet är korrekt renderade
        $this->assertStringContainsString('<div class="alert" x-data="{ show: true }">', $output);
        $this->assertStringContainsString('<button x-on:click="show = !show">Toggle</button>', $output);
        $this->assertStringContainsString('<div x-show="show">Slot Content Here</div>', $output);
    }

    public function testCachedTemplateIsUsedIfAvailable(): void
    {
        // Tvinga cache-läge
        $originalEnv = getenv('APP_ENV');
        putenv('APP_ENV=production');

        $mockCacheDir = $this->tempRootPath . 'cache/views/';
        if (!is_dir($mockCacheDir)) {
            mkdir($mockCacheDir, 0o755, true);
        }

        $reflection = new ReflectionClass($this->viewer);
        $cachePathProperty = $reflection->getProperty('cachePath');
        $cachePathProperty->setAccessible(true);
        $cachePathProperty->setValue($this->viewer, $mockCacheDir);

        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        $mockTemplateName = 'test_template_key';

        // Skapa en minimal template-fil som render() kan hitta
        $resolvedTemplatePath = $resolveTemplatePath->invoke($this->viewer, $mockTemplateName);

        if (!is_string($resolvedTemplatePath)) {
            $this->fail('resolvedTemplatePath() must return string.');
        }

        /** @var string $resolvedTemplatePath */
        $templateFullPath = $this->tempViewsPath . $resolvedTemplatePath;

        if (!is_dir(dirname($templateFullPath))) {
            mkdir(dirname($templateFullPath), 0o755, true);
        }
        file_put_contents($templateFullPath, '<div>ORIGINAL</div>');

        $data = [];
        $mockCacheKey = $generateCacheKey->invoke($this->viewer, $resolvedTemplatePath, $data);

        if (!is_string($mockCacheKey)) {
            $this->fail('generateCacheKey() must return string.');
        }

        /** @var string $mockCacheKey */
        $mockCacheFile = $mockCacheDir . $mockCacheKey . '.php';

        // Skapa cachefilen som ska användas
        file_put_contents($mockCacheFile, '<div>Cached Content</div>');

        $output = $this->viewer->render($mockTemplateName, $data);
        $this->assertSame('<div>Cached Content</div>', $output, 'Cache användes inte korrekt.');

        // Återställ APP_ENV
        if ($originalEnv === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $originalEnv);
        }
    }

    public function testGenerateCacheKeyTreatsEmptyVersionAsDefaultVersion(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        $templateLogicalName = 'version_default_test';
        /** @var string $resolved */
        $resolved = $resolveTemplatePath->invoke($this->viewer, $templateLogicalName);

        $templatePath = $this->tempViewsPath . $resolved;
        $this->createDirectoryIfNotExists(dirname($templatePath));
        file_put_contents($templatePath, 'Hello {{ $name }}');

        $data = ['name' => 'User'];

        // Tom versionssträng ska vara ekvivalent med 'default_version'
        /** @var string $keyEmpty */
        $keyEmpty = $generateCacheKey->invoke($this->viewer, $resolved, $data, '');
        /** @var string $keyDefault */
        $keyDefault = $generateCacheKey->invoke($this->viewer, $resolved, $data, 'default_version');

        $this->assertIsString($keyEmpty);
        $this->assertIsString($keyDefault);
        $this->assertSame(
            $keyEmpty,
            $keyDefault,
            'generateCacheKey ska behandla tom versionssträng som "default_version".'
        );
    }

    public function testGenerateCacheKeyDependsOnRunIdValueWhenSet(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        $templateLogicalName = 'runid_diff_test';
        /** @var string $resolved */
        $resolved = $resolveTemplatePath->invoke($this->viewer, $templateLogicalName);

        $templatePath = $this->tempViewsPath . $resolved;
        $this->createDirectoryIfNotExists(dirname($templatePath));
        file_put_contents($templatePath, 'Hello {{ $name }}');

        $data = ['name' => 'User'];

        // Två olika RADIX_RUN_ID ska ge två olika nycklar
        putenv('RADIX_RUN_ID=run_1');
        /** @var string $key1 */
        $key1 = $generateCacheKey->invoke($this->viewer, $resolved, $data);

        putenv('RADIX_RUN_ID=run_2');
        /** @var string $key2 */
        $key2 = $generateCacheKey->invoke($this->viewer, $resolved, $data);

        // Städa upp
        putenv('RADIX_RUN_ID');

        $this->assertIsString($key1);
        $this->assertIsString($key2);
        $this->assertNotSame(
            $key1,
            $key2,
            'generateCacheKey ska påverkas av värdet i RADIX_RUN_ID.'
        );
    }

    public function testGlobalFiltersAreApplied(): void
    {
        $templatePath = $this->tempViewsPath . 'filter_view.ratio.php';
        file_put_contents($templatePath, '<p>{{ $message }}</p>');

        // Registrera ett globalt filter som gör texten versaler
        $this->viewer->registerFilter('uppercase', function (string $value): string {
            return strtoupper($value);
        });

        $output = $this->viewer->render('filter_view', ['message' => 'hello']);
        $this->assertSame('<p>HELLO</p>', $output);
    }

    public function testRenderComponentWithAttributesAndSlot(): void
    {
        $componentPath = "{$this->tempViewsPath}components/alert.ratio.php";
        $this->createDirectoryIfNotExists(dirname($componentPath));
        file_put_contents($componentPath, '<div class="alert {{ $type }}">{{ $slot }}</div>');

        $this->assertFileExists($componentPath, '[DEBUG] Komponentfilen saknas: ' . $componentPath);

        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents($templatePath, '<x-alert type="warning">This is an alert</x-alert>');

        $output = $this->viewer->render('test_template');
        $expectedOutput = '<div class="alert warning">This is an alert</div>';

        // Lägg till diff-verktyg för felutskrifter
        $this->assertSame(
            $expectedOutput,
            $output,
            sprintf(
                "[DEBUG] Mismatch mellan förväntad och faktisk output.\nFörväntat:\n%s\nFaktiskt:\n%s",
                htmlspecialchars($expectedOutput),
                htmlspecialchars($output)
            )
        );
    }

    public function testRenderComponentWithoutSlot(): void
    {
        // Placera komponentfilen i views/components/
        $componentPath = "{$this->tempViewsPath}components/button.ratio.php";
        $this->createDirectoryIfNotExists(dirname($componentPath));
        file_put_contents($componentPath, '<button class="btn {{ $type }}">{{ $label }}</button>');

        // Kontrollera att filen skapades
        $this->assertFileExists($componentPath, '[DEBUG] Komponentfilen saknas: ' . $componentPath);

        // Skapa huvudtemplate
        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents($templatePath, '<x-button type="primary" label="Click Me"></x-button>');

        // Rendera template
        $output = $this->viewer->render('test_template');

        // Kontrollera renderingens resultat
        $expectedOutput = '<button class="btn primary">Click Me</button>';
        $this->assertSame($expectedOutput, $output, '[DEBUG] Rendering av <x-button> är felaktig.');
    }

    public function testNestedComponentsRenderCorrectly(): void
    {
        $wrapperPath = "{$this->tempViewsPath}components/wrapper.ratio.php";
        file_put_contents(
            $wrapperPath,
            '<div class="wrapper">{{ slot }}</div>'
        );

        $alertPath = "{$this->tempViewsPath}components/alert.ratio.php";
        file_put_contents(
            $alertPath,
            '<div class="alert {{ $type }}">{{ slot }}</div>'
        );

        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents(
            $templatePath,
            '<x-wrapper><x-alert type="info">Nested content</x-alert></x-wrapper>'
        );

        $output = $this->viewer->render('test_template');

        // Ändra den förväntade outputen till en kompakt sträng
        $expectedOutput = '<div class="wrapper"><div class="alert info">Nested content</div></div>';

        $this->assertSame(
            $expectedOutput,
            trim($output),
            'Nested components render incorrectly in wrapper.'
        );
    }

    public function testComponentWithDynamicAttributes(): void
    {
        // Placera komponentfilen i views/components/
        $componentPath = "{$this->tempViewsPath}components/card.ratio.php";
        $this->createDirectoryIfNotExists(dirname($componentPath));
        file_put_contents($componentPath, '<div class="card {{ $class }}" style="{{ $style }}">{{ $slot }}</div>');

        // Kontrollera att filen skapades
        $this->assertFileExists($componentPath, '[DEBUG] Komponentfilen saknas: ' . $componentPath);

        // Skapa huvudtemplate
        $templatePath = "{$this->tempViewsPath}test_template.ratio.php";
        file_put_contents($templatePath, '<x-card class="highlight" style="color: red;">This is a card</x-card>');

        // Rendera template
        $output = $this->viewer->render('test_template');

        // Kontrollera renderingens resultat
        $expectedOutput = '<div class="card highlight" style="color: red;">This is a card</div>';
        $this->assertSame($expectedOutput, $output, '[DEBUG] Rendering av <x-card> är felaktig.');
    }

    public function testEvaluateTemplateThrowsRuntimeExceptionWithClearMessage(): void
    {
        $templatePath = $this->tempViewsPath . 'broken.ratio.php';
        file_put_contents($templatePath, '<h1>{{ $message }}</h1><?php throw new \Exception("Boom"); ?>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template evaluation failed: Boom');

        $this->viewer->render('broken', ['message' => 'Hello']);
    }

    public function testMissingComponentThrowsClearRuntimeException(): void
    {
        $templatePath = $this->tempViewsPath . 'uses_missing_component.ratio.php';
        file_put_contents($templatePath, '<x-nonexistent>Content</x-nonexistent>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Komponent fil saknas:');

        $this->viewer->render('uses_missing_component');
    }

    public function testCacheDisabledInDevelopmentEnv(): void
    {
        // Ställ om APP_ENV till development och säkerställ att cache inte används
        $originalEnv = getenv('APP_ENV');
        putenv('APP_ENV=development');

        $template = $this->tempViewsPath . 'nocache.ratio.php';
        file_put_contents($template, 'Value: {{ $val }}');

        $out1 = $this->viewer->render('nocache', ['val' => 'A']);
        $this->assertSame('Value: A', $out1);

        // Ändra template-innehåll – om cache är avstängd ska vi få nytt resultat direkt
        file_put_contents($template, 'Value: {{ $val }}-X');

        $out2 = $this->viewer->render('nocache', ['val' => 'B']);
        $this->assertSame('Value: B-X', $out2);

        // Återställ APP_ENV
        if ($originalEnv === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $originalEnv);
        }
    }

    public function testClearOldCacheFilesRemovesOnlyTooOldFilesAndSkipsDirectoriesAndBoundary(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        // Sätt cachePath till en separat tempkatalog för just det här testet
        $cachePathProperty = $reflection->getProperty('cachePath');
        $cachePathProperty->setAccessible(true);

        $cacheDir = $this->tempRootPath . 'cache/views/';
        $this->createDirectoryIfNotExists($cacheDir);
        $cachePathProperty->setValue($this->viewer, $cacheDir);

        $now = time();
        $maxAge = 10;

        // 1) Fil äldre än maxAge -> ska tas bort
        $oldFile = $cacheDir . 'old.php';
        file_put_contents($oldFile, 'old');
        touch($oldFile, $now - 11);

        // 2) Fil exakt på gränsen -> ska INTE tas bort (dödar GreaterThan-mutanten)
        $borderFile = $cacheDir . 'border.php';
        file_put_contents($borderFile, 'border');
        touch($borderFile, $now - 10);

        // 3) Ny fil -> ska INTE tas bort
        $youngFile = $cacheDir . 'young.php';
        file_put_contents($youngFile, 'young');
        touch($youngFile, $now - 5);

        // 4) Undermapp med fil -> katalogen ska ignoreras (dödar IfNegation-mutanten)
        $subDir = $cacheDir . 'subdir';
        $this->createDirectoryIfNotExists($subDir);
        $insideFile = $subDir . '/inside.php';
        file_put_contents($insideFile, 'inside');
        touch($insideFile, $now - 20);

        // Anropa privata clearOldCacheFiles(int $maxAgeInSeconds, ?int $now) via reflection
        $clearMethod = $reflection->getMethod('clearOldCacheFiles');
        $clearMethod->setAccessible(true);
        $clearMethod->invoke($this->viewer, $maxAge, $now);

        // Om continue->break eller LogicalOrNegation-mutanten aktiveras
        // kommer ingen riktig fil rensas -> old.php finns kvar -> testet failar.

        // 1) Gammal fil ska vara borttagen
        $this->assertFileDoesNotExist(
            $oldFile,
            'Filer äldre än maxAge ska tas bort av clearOldCacheFiles().'
        );

        // 2) Fil exakt på gränsen ska finnas kvar
        $this->assertFileExists(
            $borderFile,
            'Filer med ålder exakt lika med maxAge ska inte tas bort.'
        );

        // 3) Ny fil ska finnas kvar
        $this->assertFileExists(
            $youngFile,
            'Filer yngre än maxAge ska inte tas bort.'
        );

        // 4) Undermapp och dess fil ska finnas kvar
        $this->assertDirectoryExists(
            $subDir,
            'Kataloger i cache-katalogen ska inte tas bort av clearOldCacheFiles().'
        );
        $this->assertFileExists(
            $insideFile,
            'Filer i undermappar ska lämnas orörda av clearOldCacheFiles().'
        );
    }

    public function testStringFilterIsNotAppliedToNonStringValues(): void
    {
        // Registrera ett filter som ENDAST accepterar strängar
        $this->viewer->registerFilter('string_only', function (string $value): string {
            // Om detta någonsin får en icke-sträng kommer PHP att kasta TypeError,
            // vilket gör att mutanten (&& -> ||) avslöjas.
            return 'FILTERED:' . $value;
        });

        // Template som använder en array-nyckel från $arr
        $templatePath = $this->tempViewsPath . 'string_filter_non_string.ratio.php';
        file_put_contents(
            $templatePath,
            'Value: {{ $arr["name"] }}'
        );

        // $arr är en ARRAY – filtret med type 'string' ska INTE appliceras på hela arrayen
        $output = $this->viewer->render('string_filter_non_string', [
            'arr' => ['name' => 'John'],
        ]);

        // Om filtret felaktigt appliceras på arrayen kastas TypeError och testet failar.
        $this->assertSame('Value: John', $output);
    }

    public function testApplyFiltersKeepsAllDataEntries(): void
    {
        // Filter som gör alla strängvärden versaler
        $this->viewer->registerFilter('uppercase', function (string $value): string {
            return strtoupper($value);
        });

        $templatePath = $this->tempViewsPath . 'multi_filter_view.ratio.php';
        file_put_contents(
            $templatePath,
            'A: {{ $a }}, B: {{ $b }}'
        );

        $output = $this->viewer->render('multi_filter_view', [
            'a' => 'foo',
            'b' => 'bar',
        ]);

        // ArrayOneItem-mutanten skulle kapa bort ena nyckeln ur $data efter filtrering.
        $this->assertSame('A: FOO, B: BAR', $output);
    }

    public function testClearOldCacheFilesRespectsDefaultMaxAgeBoundary(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        $cachePathProperty = $reflection->getProperty('cachePath');
        $cachePathProperty->setAccessible(true);

        $cacheDir = $this->tempRootPath . 'cache/default_boundary/';
        $this->createDirectoryIfNotExists($cacheDir);
        $cachePathProperty->setValue($this->viewer, $cacheDir);

        $now = time();

        // Fil exakt 86400 sekunder gammal → ska INTE tas bort med default 86400
        $borderFile = $cacheDir . 'border_default.php';
        file_put_contents($borderFile, 'border');
        touch($borderFile, $now - 86400);

        // Fil 86401 sekunder gammal → ska tas bort med default 86400
        $oldFile = $cacheDir . 'old_default.php';
        file_put_contents($oldFile, 'old');
        touch($oldFile, $now - 86401);

        // Anropa clearOldCacheFiles() UTAN argument → använder default-värdet
        $clearMethod = $reflection->getMethod('clearOldCacheFiles');
        $clearMethod->setAccessible(true);
        $clearMethod->invoke($this->viewer);

        // Original + IncrementInteger (86400/86401) lämnar border-filen kvar
        $this->assertFileExists(
            $borderFile,
            'Fil som är exakt 86400 sekunder gammal ska inte tas bort med default maxAge.'
        );

        // Original + DecrementInteger (86399) ska ta bort oldFile,
        // men IncrementInteger (86401) skulle FELAKTIGT lämna den kvar.
        $this->assertFileDoesNotExist(
            $oldFile,
            'Fil äldre än 86400 sekunder ska tas bort med default maxAge.'
        );
    }

    public function testApplyFiltersRespectValueTypesForStringArrayAndObject(): void
    {
        $reflection = new ReflectionClass($this->viewer);
        $applyFilters = $reflection->getMethod('applyFilters');
        $applyFilters->setAccessible(true);

        // 1) String-filter – ska bara appliceras på strängar
        $this->viewer->registerFilter(
            'string_filter',
            function (string $value): string {
                return 'S:' . $value;
            },
            'string'
        );

        // 2) Array-filter – ska bara appliceras på arrays
        $this->viewer->registerFilter(
            'array_filter',
            function (array $value): array {
                $value['__filtered'] = true;
                return $value;
            },
            'array'
        );

        // 3) Object-filter – ska bara appliceras på objekt
        $this->viewer->registerFilter(
            'object_filter',
            /**
             * @param MarkableObject $value
             */
            function (MarkableObject $value): MarkableObject {
                $value->marked = true;
                return $value;
            },
            'object'
        );

        $obj = new MarkableObject();

        $input = [
            'str' => 'hello',
            'arr' => ['x' => 1],
            'obj' => $obj,
        ];

        /** @var array<string,mixed> $result */
        $result = $applyFilters->invoke($this->viewer, $input);

        // Stringfilter ska ha applicerats ENDAST på strängvärdet
        $this->assertSame('S:hello', $result['str']);

        // Arrayfilter ska ha applicerats ENDAST på array-värdet
        $this->assertIsArray($result['arr']);
        $this->assertArrayHasKey('__filtered', $result['arr']);
        $this->assertTrue($result['arr']['__filtered']);

        // Objectfilter ska ha applicerats ENDAST på objekt-värdet
        $this->assertInstanceOf(MarkableObject::class, $result['obj']);
        /** @var MarkableObject $markedObj */
        $markedObj = $result['obj'];
        $this->assertTrue($markedObj->marked);

        // Viktigt: om LogicalAnd-mutanten gör att object-grenen
        // körs även när expectedType != 'object', så kommer t.ex.
        // string_filter att få ett objekt och kasta TypeError ->
        // detta test kommer att fallera och döda mutanten.
    }

    public function testGenerateCacheKeyDependsOnBothTemplateDataAndAssetTimestamps(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        // 1) Skapa en enkel template-fil
        $templateLogicalName = 'cache_key_test';
        /** @var string $resolved */
        $resolved = $resolveTemplatePath->invoke($this->viewer, $templateLogicalName);

        $templatePath = $this->tempViewsPath . $resolved;
        $this->createDirectoryIfNotExists(dirname($templatePath));
        file_put_contents($templatePath, 'Hello {{ $name }}');

        // 2) Skapa CSS/JS under ROOT_PATH/public
        $publicCssDir = ROOT_PATH . '/public/css';
        $publicJsDir  = ROOT_PATH . '/public/js';
        $this->createDirectoryIfNotExists($publicCssDir);
        $this->createDirectoryIfNotExists($publicJsDir);

        $cssPath = $publicCssDir . '/app.css';
        $jsPath  = $publicJsDir . '/app.js';

        file_put_contents($cssPath, 'body {}');
        file_put_contents($jsPath, 'console.log("x");');

        // Sätt stabil tidsstämpel
        $t1 = time();
        touch($cssPath, $t1);
        touch($jsPath, $t1);

        // 3) Nyckel med första datamängden – pagination page = 1
        $data1 = [
            'name' => 'User',
            'pagination' => ['page' => 1],
        ];
        /** @var string $key1 */
        $key1 = $generateCacheKey->invoke($this->viewer, $resolved, $data1);

        // 4) Ändra endast pagination (relevantParts) och beräkna nyckel igen – page = 2
        $data2 = [
            'name' => 'User',
            'pagination' => ['page' => 2],
        ];
        /** @var string $key2 */
        $key2 = $generateCacheKey->invoke($this->viewer, $resolved, $data2);

        $this->assertNotSame(
            $key1,
            $key2,
            'generateCacheKey ska påverkas av template-data (t.ex. pagination).'
        );

        // 5) Samma data som key2, men ändra bara CSS-mtime (additionalHashes)
        $t2 = $t1 + 10;
        touch($cssPath, $t2);
        // JS lämnas oförändrad – det räcker att en av dem ändras
        /** @var string $key3 */
        $key3 = $generateCacheKey->invoke($this->viewer, $resolved, $data2);

        $this->assertNotSame(
            $key2,
            $key3,
            'generateCacheKey ska påverkas av CSS/JS-tidsstämplar (additionalHashes).'
        );
    }

    public function testGenerateCacheKeyChangesWhenVersionChanges(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        $templateLogicalName = 'version_key_test';
        /** @var string $resolved */
        $resolved = $resolveTemplatePath->invoke($this->viewer, $templateLogicalName);

        $templatePath = $this->tempViewsPath . $resolved;
        $this->createDirectoryIfNotExists(dirname($templatePath));
        file_put_contents($templatePath, 'Hello {{ $name }}');

        $data = ['name' => 'User'];

        /** @var string $keyDefault */
        $keyDefault = $generateCacheKey->invoke($this->viewer, $resolved, $data, '');
        /** @var string $keyV1 */
        $keyV1 = $generateCacheKey->invoke($this->viewer, $resolved, $data, 'v1');

        $this->assertIsString($keyDefault);
        $this->assertIsString($keyV1);
        $this->assertNotSame(
            $keyDefault,
            $keyV1,
            'generateCacheKey ska påverkas av versionssträngen.'
        );
    }

    public function testGenerateCacheKeyIncludesRunIdWhenSet(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        $templateLogicalName = 'runid_key_test';
        /** @var string $resolved */
        $resolved = $resolveTemplatePath->invoke($this->viewer, $templateLogicalName);

        $templatePath = $this->tempViewsPath . $resolved;
        $this->createDirectoryIfNotExists(dirname($templatePath));
        file_put_contents($templatePath, 'Hello {{ $name }}');

        $data = ['name' => 'User'];

        // Ingen RADIX_RUN_ID
        putenv('RADIX_RUN_ID');
        /** @var string $keyWithoutRunId */
        $keyWithoutRunId = $generateCacheKey->invoke($this->viewer, $resolved, $data);

        // Med RADIX_RUN_ID
        putenv('RADIX_RUN_ID=test-run-123');
        /** @var string $keyWithRunId */
        $keyWithRunId = $generateCacheKey->invoke($this->viewer, $resolved, $data);

        // Städa upp env
        putenv('RADIX_RUN_ID');

        $this->assertIsString($keyWithoutRunId);
        $this->assertIsString($keyWithRunId);
        $this->assertNotSame(
            $keyWithoutRunId,
            $keyWithRunId,
            'generateCacheKey ska påverkas av RADIX_RUN_ID när den är satt.'
        );
    }

    public function testClearOldCacheFilesHonorsExplicitNowParameter(): void
    {
        $reflection = new ReflectionClass($this->viewer);

        $cachePathProperty = $reflection->getProperty('cachePath');
        $cachePathProperty->setAccessible(true);

        $cacheDir = $this->tempRootPath . 'cache/honors_now/';
        $this->createDirectoryIfNotExists($cacheDir);
        $cachePathProperty->setValue($this->viewer, $cacheDir);

        // Skapa en fil som ENDAST rensas om clearOldCacheFiles använder det explicita $now-värdet
        $file = $cacheDir . 'keep.php';
        file_put_contents($file, 'x');

        // Sätt mtime så att filen är yngre än maxAge relativt vårt "fakeNow"
        $fakeNow = 1000;
        touch($file, $fakeNow - 5); // ålder 5 sekunder relativt fakeNow
        $maxAge = 10;

        $clearMethod = $reflection->getMethod('clearOldCacheFiles');
        $clearMethod->setAccessible(true);

        // Om implementationen använder $now-parametern korrekt (1000),
        // ska filen INTE rensas (5 <= 10).
        // Mutanten ($now = time() ?? $now) kommer istället att använda time(),
        // vilket ger en mycket större ålder och därmed rensar filen.
        $clearMethod->invoke($this->viewer, $maxAge, $fakeNow);

        $this->assertFileExists(
            $file,
            'clearOldCacheFiles() ska respektera explicit $now-parameter och inte använda time() istället.'
        );
    }

    public function testApplyFiltersWithoutFiltersReturnsDataUnchanged(): void
    {
        $reflection = new ReflectionClass($this->viewer);
        $applyFilters = $reflection->getMethod('applyFilters');
        $applyFilters->setAccessible(true);

        $input = ['a' => 'one', 'b' => 'two'];

        /** @var array<string,mixed> $result */
        $result = $applyFilters->invoke($this->viewer, $input);

        // ArrayOneItem-mutanten skulle plocka bort alla utom första nyckeln
        $this->assertSame($input, $result);
    }

    public function testGetSearchKeyReturnsTermAndPageWhenSearchArrayPresent(): void
    {
        $reflection = new ReflectionClass($this->viewer);
        $getSearchKey = $reflection->getMethod('getSearchKey');
        $getSearchKey->setAccessible(true);

        $data = [
            'search' => [
                'term' => 'foo',
                'current_page' => 3,
            ],
        ];

        /** @var array<string,mixed> $result */
        $result = $getSearchKey->invoke($this->viewer, $data);

        // Rätt beteende: term + current_page returneras
        $this->assertSame(
            ['term' => 'foo', 'current_page' => 3],
            $result
        );
    }

    public function testGetSearchKeyDefaultsCurrentPageToOneWhenMissing(): void
    {
        $reflection = new ReflectionClass($this->viewer);
        $getSearchKey = $reflection->getMethod('getSearchKey');
        $getSearchKey->setAccessible(true);

        // current_page saknas → ska defaulta till 1
        $data = [
            'search' => [
                'term' => 'bar',
                // ingen current_page
            ],
        ];

        /** @var array<string,mixed> $result */
        $result = $getSearchKey->invoke($this->viewer, $data);

        $this->assertSame('bar', $result['term'] ?? null);
        $this->assertSame(
            1,
            $result['current_page'] ?? null,
            'current_page ska defaulta till 1 när den saknas.'
        );
    }

    public function testGetPaginationKeyReturnsEmptyArrayWhenPaginationMissingOrInvalid(): void
    {
        $reflection = new ReflectionClass($this->viewer);
        $getPaginationKey = $reflection->getMethod('getPaginationKey');
        $getPaginationKey->setAccessible(true);

        // 1) Ingen pagination-nyckel alls
        $noPagination = $getPaginationKey->invoke($this->viewer, []);
        $this->assertSame(
            [],
            $noPagination,
            'Utan pagination-nyckel ska getPaginationKey returnera tom array.'
        );

        // 2) pagination finns men är inte array
        $invalidPagination = $getPaginationKey->invoke($this->viewer, ['pagination' => 'not-an-array']);
        $this->assertSame(
            [],
            $invalidPagination,
            'Om pagination inte är en array ska getPaginationKey returnera tom array.'
        );
    }

    public function testGetPaginationKeyDefaultsPageToOneWhenMissing(): void
    {
        $reflection = new ReflectionClass($this->viewer);
        $getPaginationKey = $reflection->getMethod('getPaginationKey');
        $getPaginationKey->setAccessible(true);

        $data = [
            'pagination' => [
                // ingen 'page' -> ska defaulta till 1
            ],
        ];

        /** @var array<string,int> $result */
        $result = $getPaginationKey->invoke($this->viewer, $data);

        $this->assertSame(
            ['page' => 1],
            $result,
            'page ska defaulta till 1 när den saknas i pagination.'
        );
    }

    public function testGetPaginationKeyCastsNumericStringPageToInt(): void
    {
        $reflection = new ReflectionClass($this->viewer);
        $getPaginationKey = $reflection->getMethod('getPaginationKey');
        $getPaginationKey->setAccessible(true);

        $data = [
            'pagination' => [
                'page' => '5', // numerisk sträng
            ],
        ];

        /** @var array<string,int> $result */
        $result = $getPaginationKey->invoke($this->viewer, $data);

        // Vi kräver att 'page' är en INT 5, inte en sträng '5'
        $this->assertSame(
            ['page' => 5],
            $result,
            'Numeric string för page ska castas till int 5.'
        );
    }

    public function testDebugLogsMessagesOnlyWhenDebugModeEnabled(): void
    {
        $this->viewer->enableDebugMode(true);

        $reflection = new ReflectionClass($this->viewer);
        $loggerProp = $reflection->getProperty('logger');
        $loggerProp->setAccessible(true);

        $testLogger = new TestViewLogger();
        $loggerProp->setValue($this->viewer, $testLogger);

        $debugMethod = $reflection->getMethod('debug');
        $debugMethod->setAccessible(true);
        $debugMethod->invoke($this->viewer, 'TEST-MESSAGE');

        // När debug=true ska debug() logga något
        $this->assertNotEmpty(
            $testLogger->messages,
            'debug() ska logga när debug-läget är aktiverat.'
        );
        $this->assertSame('TEST-MESSAGE', $testLogger->messages[0] ?? null);
    }

    public function testDebugDoesNotLogWhenDebugModeDisabled(): void
    {
        $this->viewer->enableDebugMode(false);

        $reflection = new ReflectionClass($this->viewer);
        $loggerProp = $reflection->getProperty('logger');
        $loggerProp->setAccessible(true);

        $testLogger = new TestViewLogger();
        $loggerProp->setValue($this->viewer, $testLogger);

        $debugMethod = $reflection->getMethod('debug');
        $debugMethod->setAccessible(true);
        $debugMethod->invoke($this->viewer, 'SHOULD-NOT-LOG');

        // När debug=false ska debug() INTE logga något
        $this->assertSame(
            [],
            $testLogger->messages,
            'debug() ska inte logga när debug-läget är av.'
        );
    }

    private function createDirectoryIfNotExists(string $path): void
    {
        if (!is_dir($path)) {
            $ok = @mkdir($path, 0o755, true);
            if (!$ok && !is_dir($path)) {
                throw new RuntimeException('Kunde inte skapa katalog: ' . $path);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($p)) {
                $this->deleteDirectory($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }

    private function normalizeOutput(string $output): string
    {
        // Tar bort överflödiga radbrytningar och mellanslag mellan HTML-taggar
        $normalized = preg_replace('/\s*(<[^>]+>)\s*/', '$1', trim($output));

        // preg_replace kan returnera null vid regex-fel, säkerställ alltid string
        if ($normalized === null) {
            return '';
        }

        return $normalized;
    }
}
