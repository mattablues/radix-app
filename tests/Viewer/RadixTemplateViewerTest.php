<?php

declare(strict_types=1);

namespace Radix\Tests\Viewer;

use PHPUnit\Framework\TestCase;
use Radix\Viewer\RadixTemplateViewer;
use RuntimeException;

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
        $this->createDirectoryIfNotExists($this->tempViewsPath . '/components'); // Komponentspecifik katalog

        // Definiera ROOT_PATH om ej definierad
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $this->tempRootPath);
        }

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
        file_put_contents($componentPath, '
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
        file_put_contents($templatePath, '
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
        // Steg 1: Simulera cache-lagring
        $mockTemplateName = 'invalidate_test.template';
        $templatePath = $this->tempViewsPath . 'invalidate_test/template.ratio.php';

        // Skapa mappen för invalidate_test om den inte finns
        $this->createDirectoryIfNotExists(dirname($templatePath));
        file_put_contents($templatePath, 'Hello {{ $name }}');

        // Reflektion för att komma åt den privata metoden resolveTemplatePath
        $reflection = new \ReflectionClass($this->viewer);
        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        // Reflektion för att komma åt den privata metoden generateCacheKey
        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        // Justera cachePath för att inkludera "cache/views/"
        $cachePathProperty = $reflection->getProperty('cachePath');
        $cachePathProperty->setAccessible(true);
        $adjustedCachePath = $this->tempRootPath . 'cache/views/';
        $cachePathProperty->setValue($this->viewer, $adjustedCachePath);

        // Skapa cache-mappen om den inte finns
        $this->createDirectoryIfNotExists($adjustedCachePath);

        // Generera den fullständiga sökvägen med hjälp av reflektionen
        $resolvedTemplatePath = $resolveTemplatePath->invoke($this->viewer, 'invalidate_test/template');
        $data = ['name' => 'InitialName'];

        // Anropa generateCacheKey med reflektion
        $cacheKey = $generateCacheKey->invoke($this->viewer, $resolvedTemplatePath, $data);
        $cachedFile = "{$adjustedCachePath}{$cacheKey}.php";

        // Rendera template och säkerställ att cachen skapas
        $this->viewer->render('invalidate_test/template', $data);
        $this->assertFileExists($cachedFile, "DEBUG: Cache file not created at expected path: {$cachedFile}");

        // Steg 2: Invalidera cachen
        $this->viewer->invalidateCache('invalidate_test/template', $data);
        $this->assertFileDoesNotExist($cachedFile, "DEBUG: Cache file was not deleted at path: {$cachedFile}");

        // Steg 3: Rendera om (med uppdaterad data)
        $updatedData = ['name' => 'UpdatedName'];
        $output = $this->viewer->render('invalidate_test/template', $updatedData);

        // Kontrollera att rätt data renderas
        $this->assertNotSame('Hello InitialName', $output);
        $this->assertSame('Hello UpdatedName', $output);
    }

    public function testRenderThrowsExceptionIfTemplateNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template file not found');

        $this->viewer->render('nonexistent_view');
    }

    // tests/Feature/ViewTest.php
    public function testAlpineSyntaxIsRenderedCorrectly()
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

        mkdir(dirname($layoutPath), 0755, true);
        file_put_contents($layoutPath, '<html>{% yield body %}</html>');
        file_put_contents($childPath, '{% extends "layouts/main.ratio.php" %}{% block body %}<h1>Hello!</h1>{% endblock %}');

        $output = $this->viewer->render('home');
        $this->assertSame('<html><h1>Hello!</h1></html>', $output);
    }

    public function testSlotsWorkWithAlpineSyntax()
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
        // Steg 1: Skapa en korrekt temporär cache-katalog
        $mockCacheDir = $this->tempRootPath . 'cache/views/';
        if (!is_dir($mockCacheDir)) {
            mkdir($mockCacheDir, 0755, true);
        }

        // Reflektion för att komma åt property och metoder
        $reflection = new \ReflectionClass($this->viewer);
        $cachePathProperty = $reflection->getProperty('cachePath');
        $cachePathProperty->setAccessible(true);

        // Justera cache-sökvägen till rätt katalog
        $cachePathProperty->setValue($this->viewer, $mockCacheDir);

        $resolveTemplatePath = $reflection->getMethod('resolveTemplatePath');
        $resolveTemplatePath->setAccessible(true);

        $generateCacheKey = $reflection->getMethod('generateCacheKey');
        $generateCacheKey->setAccessible(true);

        // Steg 2: Generera cacheKey som i RadixTemplateViewer
        $mockTemplateName = 'test_template_key';
        $resolvedTemplatePath = $resolveTemplatePath->invoke($this->viewer, $mockTemplateName);
        $data = []; // Tom test-data
        $mockCacheKey = $generateCacheKey->invoke($this->viewer, $resolvedTemplatePath, $data);
        $mockCacheFile = $mockCacheDir . $mockCacheKey . '.php';

        // Steg 3: Skapa en mock-cache-fil
        file_put_contents($mockCacheFile, '<div>Cached Content</div>');

        // Steg 4: Kontrollera att cachen används genom att köra render
        $output = $this->viewer->render($mockTemplateName, $data);

        // Kontrollera att innehållet kommer från cache
        $this->assertSame('<div>Cached Content</div>', $output, 'Cache användes inte korrekt.');
    }

    public function testDebugModeLogsMessages(): void
    {
        $templatePath = $this->tempViewsPath . 'debug_view.ratio.php';
        file_put_contents($templatePath, '<h1>{{ $message }}</h1>');

        $this->viewer->enableDebugMode(true);

        // Fånga output från debug-loggning
        ob_start();
        $output = $this->viewer->render('debug_view', ['message' => 'Debug Mode Works']);
        $debugOutput = ob_get_clean();

        $this->assertSame('<h1>Debug Mode Works</h1>', $output);
        $this->assertStringContainsString("[DEBUG] Attempting to render template: debug_view", $debugOutput);
        $this->assertStringContainsString("[DEBUG] Template resolved to: debug_view.ratio.php", $debugOutput);
    }

    public function testGlobalFiltersAreApplied(): void
    {
        $templatePath = $this->tempViewsPath . 'filter_view.ratio.php';
        file_put_contents($templatePath, '<p>{{ $message }}</p>');

        // Registrera ett globalt filter som gör texten versaler
        $this->viewer->registerFilter('uppercase', function ($value) {
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

    private function createDirectoryIfNotExists(string $path): void
    {
        if (!is_dir($path)) {
            $result = @mkdir($path, 0755, true); // Lägg till suppress-logg
            if (!$result && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Misslyckades med att skapa katalog: %s', $path));
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    $this->deleteDirectory($filePath);
                } else {
                    unlink($filePath);
                }
            }
        }

        rmdir($dir);
    }

    private function normalizeOutput(string $output): string
    {
        // Tar bort överflödiga radbrytningar och mellanslag mellan HTML-taggar
        return preg_replace('/\s*(<[^>]+>)\s*/', '$1', trim($output));
    }
}