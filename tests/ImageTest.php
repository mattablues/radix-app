<?php

declare(strict_types=1);

namespace Radix\Tests;

use GdImage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Radix\File\Image;
use ReflectionClass;
use RuntimeException;

class ImageTest extends TestCase
{
    protected string $testImagePath;
    protected string $watermarkImagePath;

    protected function setUp(): void
    {
        // Skapa en testbild
        $image = imagecreatetruecolor(800, 600);

        $color = imagecolorallocate($image, 255, 0, 0);
        if ($color === false) {
            $this->fail('Kunde inte allokera färg för testbilden.');
        }

        imagefill($image, 0, 0, $color); // Röd färg
        $this->testImagePath = __DIR__ . '/test_image.jpg';
        imagejpeg($image, $this->testImagePath);
        imagedestroy($image);

        // Skapa vattenmärkesbild
        $watermark = imagecreatetruecolor(100, 50);

        $wmColor = imagecolorallocate($watermark, 0, 0, 255);
        if ($wmColor === false) {
            $this->fail('Kunde inte allokera färg för vattenmärkesbilden.');
        }

        imagefill($watermark, 0, 0, $wmColor); // Blå färg
        $this->watermarkImagePath = __DIR__ . '/watermark_image.png';
        imagepng($watermark, $this->watermarkImagePath);
        imagedestroy($watermark);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }

        if (file_exists($this->watermarkImagePath)) {
            unlink($this->watermarkImagePath);
        }

        $files = glob(__DIR__ . '/result_*');
        if ($files !== false) {
            array_map('unlink', $files);
        }
    }

    public function testRotateImage(): void
    {
        $image = new Image($this->testImagePath);

        $image->rotateImage(90);

        $info = $image->getImageInfo();

        // Eftersom rotationen inte ändrar dimensionerna internt
        $this->assertEquals(800, $info['width']);
        $this->assertEquals(600, $info['height']);
    }

    public function testAddWatermark(): void
    {
        $image = new Image($this->testImagePath);

        $resultPath = __DIR__ . '/result_with_watermark.jpg';
        $image->addWatermark($this->watermarkImagePath, 50, 50);
        $image->saveImage($resultPath);

        $this->assertFileExists($resultPath);
    }

    public function testGetImageInfo(): void
    {
        $image = new Image($this->testImagePath);

        $info = $image->getImageInfo();

        // Kontrollera att dimensionerna matchar originalbildens storlek
        $this->assertEquals(800, $info['width'], 'Originalbredden ska vara 800 pixlar.');
        $this->assertEquals(600, $info['height'], 'Originalhöjden ska vara 600 pixlar.');

        // Kontrollera att inga dimensioner för resized bild finns när ingen ändring har gjorts
        $this->assertNull($info['resizedWidth'], 'Den ändrade bredden ska vara null om ingen ändring gjorts.');
        $this->assertNull($info['resizedHeight'], 'Den ändrade höjden ska vara null om ingen ändring gjorts.');

        // Ändra storlek och verifiera
        $image->resizeImage(400, 300);
        $infoAfterResize = $image->getImageInfo();

        $this->assertEquals(400, $infoAfterResize['resizedWidth'], 'Den ändrade bredden efter resize ska vara 400 pixlar.');
        $this->assertEquals(300, $infoAfterResize['resizedHeight'], 'Den ändrade höjden efter resize ska vara 300 pixlar.');
    }

    public function testConstructorValidImage(): void
    {
        $image = new Image($this->testImagePath);
        $this->assertInstanceOf(Image::class, $image);
    }

    public function testSaveThumbDelegatesToSaveImageWithDerivedPathAndDefaultQuality(): void
    {
        // Subklass som loggar saveImage()-anrop i stället för att skriva till disk
        $image = new class ($this->testImagePath) extends Image {
            public ?string $savedPath = null;
            public ?int $savedQuality = null;

            public function saveImage(string $path, ?int $quality = null): void
            {
                $this->savedPath = $path;
                $this->savedQuality = $quality;
            }
        };

        $basePath = __DIR__ . '/thumb_delegate.jpg';

        // Anropa utan quality-argument → ska använda default 100
        $image->saveThumb($basePath);

        // MethodCallRemoval-mutanter gör att dessa förblir null
        $this->assertNotNull($image->savedPath, 'saveThumb() måste anropa saveImage()');
        $this->assertNotNull($image->savedQuality, 'saveThumb() måste vidarebefordra quality till saveImage()');

        $directory = pathinfo($basePath, PATHINFO_DIRNAME);
        $filename = pathinfo($basePath, PATHINFO_FILENAME);
        $ext      = pathinfo($basePath, PATHINFO_EXTENSION);

        $expectedThumbPath = $directory . '/' . $filename . '.thumb.' . $ext;

        $this->assertSame(
            $expectedThumbPath,
            $image->savedPath,
            'saveThumb() ska härleda sitt filnamn enligt "$directory/$filename.thumb.$ext".'
        );

        // IncrementInteger på default-argumentet (100 → 101) gör att denna assertion faller.
        $this->assertSame(
            100,
            $image->savedQuality,
            'Default-quality för saveThumb() ska vara 100.'
        );
    }

    public function testOpenImageRemainsPublicAndReturnsGdImage(): void
    {
        $image = new Image($this->testImagePath);

        $opened = $image->openImage($this->testImagePath);

        $this->assertInstanceOf(GdImage::class, $opened);
    }

    public function testConstructorInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bilden "non_existing_image.jpg" kunde inte hittas.');

        new Image('non_existing_image.jpg');
    }

    public function testConstructorUnsupportedFormat(): void
    {
        $unsupportedPath = __DIR__ . '/unsupported_image.bmp';

        // Skapa en riktig BMP-fil
        $bmpHeader = hex2bin('424D460000000000000036000000280000000100000001000000010018000000000010000000C40E0000C40E00000000000000000000');
        file_put_contents($unsupportedPath, $bmpHeader);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bildformat "image/bmp" stöds inte.');

        try {
            new Image($unsupportedPath);
        } finally {
            unlink($unsupportedPath);
        }
    }

    public function testResizeImage(): void
    {
        $image = new Image($this->testImagePath);

        $image->resizeImage(400, 300);

        $resized = $image->getImageResized();
        $this->assertNotNull($resized, 'Resized image should not be null.');
        $this->assertEquals(400, imagesx($resized));
        $this->assertEquals(300, imagesy($resized));
    }

    public function testResizeImageCrop(): void
    {
        $image = new Image($this->testImagePath);

        $image->resizeImage(400, 300, 'crop');

        $resized = $image->getImageResized();
        $this->assertNotNull($resized, 'Resized image should not be null.');
        $this->assertEquals(400, imagesx($resized));
        $this->assertEquals(300, imagesy($resized));
    }

    public function testSaveImage(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 300);
        $outputPath = __DIR__ . '/resized_image.jpg';

        $image->saveImage($outputPath);

        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));
    }

    public function testSaveImageSupportsUppercaseJpgExtension(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 300);

        $outputPath = __DIR__ . '/resized_image.JPG';
        $image->saveImage($outputPath);

        // UnwrapStrToLower-mutanten gör att detta kastar InvalidArgumentException (okänt filformat "JPG")
        $this->assertFileExists($outputPath);
    }

    public function testSaveImageSupportsJpegExtension(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 300);

        $outputPath = __DIR__ . '/resized_image.jpeg';
        $image->saveImage($outputPath);

        // SharedCaseRemoval-mutanten som tar bort 'jpeg'-caset gör att detta blir "okänt filformat"
        $this->assertFileExists($outputPath);
    }

    public function testSaveThumb(): void
    {
        $image = new Image($this->testImagePath);
        $thumbPath = __DIR__ . '/test_thumb.jpg';

        $image->resizeImage(400, 300); // Se till att resizeImage körs
        $image->saveThumb($thumbPath);

        $expectedThumbPath = __DIR__ . '/test_thumb.thumb.jpg';
        $this->assertFileExists($expectedThumbPath);
        $this->assertGreaterThan(0, filesize($expectedThumbPath));
    }

    public function testSaveImageUnsupportedFormat(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 400);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Okänt filformat "txt".');

        $image->saveImage(__DIR__ . '/test_image.txt');
    }

    public function testCropDoesNotProduceBlackBorders(): void
    {
        $image = new Image($this->testImagePath);

        // Kör crop-vägen
        $image->resizeImage(400, 300, 'crop');

        $resized = $image->getImageResized();
        $this->assertNotNull($resized, 'Resized image should not be null.');

        // Kontrollera några hörnpixlar – de ska i alla fall inte vara "nästan svarta"
        // p.g.a. felaktig destinationsoffset (-1 eller 1).
        $checkPoints = [
            [0, 0],
            [399, 0],
            [0, 299],
            [399, 299],
        ];

        foreach ($checkPoints as [$x, $y]) {
            $rgbIndex = imagecolorat($resized, $x, $y);
            $this->assertIsInt($rgbIndex);

            $colors = imagecolorsforindex($resized, $rgbIndex);
            /** @var array{red:int<0,255>,green:int<0,255>,blue:int<0,255>,alpha:int<0,127>} $colors */

            $red   = $colors['red'];
            $green = $colors['green'];
            $blue  = $colors['blue'];

            // Ursprungliga bilden är starkt röd; JPEG-komprimering kan ge 254 osv,
            // så vi använder trösklar i stället för exakta värden.
            $this->assertGreaterThanOrEqual(200, $red);
            $this->assertLessThanOrEqual(50, $green);
            $this->assertLessThanOrEqual(50, $blue);
        }
    }

    public function testCropIsVerticallyCenteredOnContent(): void
    {
        // Skapa en temporär testbild med tre horisontella färgband:
        // övre = blå, mitten = grön, nedre = röd.
        $width = 800;
        $height = 600;

        $img = imagecreatetruecolor($width, $height);
        $blue = imagecolorallocate($img, 0, 0, 255);
        $green = imagecolorallocate($img, 0, 255, 0);
        $red = imagecolorallocate($img, 255, 0, 0);

        if ($blue === false || $green === false || $red === false) {
            $this->fail('Kunde inte allokera färger för crop-testbilden (vertikal).');
        }

        // Övre 200 px: blå
        imagefilledrectangle($img, 0, 0, $width - 1, 199, $blue);
        // Mitten 200 px: grön
        imagefilledrectangle($img, 0, 200, $width - 1, 399, $green);
        // Nedre 200 px: röd
        imagefilledrectangle($img, 0, 400, $width - 1, 599, $red);

        $path = __DIR__ . '/crop_test_gradient.png';
        imagepng($img, $path);
        imagedestroy($img);

        try {
            $image = new Image($path);

            // Dimensioner valda så att optimalHeight > newHeight
            $image->resizeImage(400, 200, 'crop');

            $resized = $image->getImageResized();
            $this->assertNotNull($resized, 'Resized image should not be null.');

            // Sampla mitten av den beskurna bilden
            $sampleX = (int) (imagesx($resized) / 2);
            $sampleY = (int) (imagesy($resized) / 2);

            $rgbIndex = imagecolorat($resized, $sampleX, $sampleY);
            $this->assertIsInt($rgbIndex);

            $colors = imagecolorsforindex($resized, $rgbIndex);
            /** @var array{red:int<0,255>,green:int<0,255>,blue:int<0,255>,alpha:int<0,127>} $colors */

            $redValue   = $colors['red'];
            $greenValue = $colors['green'];
            $blueValue  = $colors['blue'];

            $this->assertGreaterThan(
                $redValue,
                $greenValue,
                'Mittenpixeln ska vara grönare än röd (vertikal centrering).'
            );
            $this->assertGreaterThan(
                $blueValue,
                $greenValue,
                'Mittenpixeln ska vara grönare än blå (vertikal centrering).'
            );
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testResizeImageExactUsesRequestedDimensions(): void
    {
        $image = new Image($this->testImagePath);

        $image->resizeImage(123, 45, 'exact');

        $resized = $image->getImageResized();
        $this->assertNotNull($resized);
        $this->assertSame(123, imagesx($resized));
        $this->assertSame(45, imagesy($resized));
    }

    public function testResizeImagePortraitKeepsNewHeightAndScalesWidth(): void
    {
        $image = new Image($this->testImagePath);

        // Originalbild 800x600, portrait med newHeight=300
        $image->resizeImage(0, 300, 'portrait');

        $resized = $image->getImageResized();
        $this->assertNotNull($resized);

        // Höjden ska vara exakt 300 enligt portrait-regeln
        $this->assertSame(300, imagesy($resized));

        // Bredden ska vara proportionellt skalad: 300 * (height/width) = 300 * (600/800) = 225
        $this->assertSame(225, imagesx($resized));
    }

    public function testCropIsHorizontallyCenteredOnContent(): void
    {
        // Skapa en temporär testbild med tre vertikala färgband:
        // vänster = blå, mitten = grön, höger = röd.
        $width = 600;
        $height = 400;

        $img = imagecreatetruecolor($width, $height);
        $blue = imagecolorallocate($img, 0, 0, 255);
        $green = imagecolorallocate($img, 0, 255, 0);
        $red = imagecolorallocate($img, 255, 0, 0);

        if ($blue === false || $green === false || $red === false) {
            $this->fail('Kunde inte allokera färger för crop-testbilden (horisontell).');
        }

        // Vänster 200 px: blå
        imagefilledrectangle($img, 0, 0, 199, $height - 1, $blue);
        // Mitten 200 px: grön
        imagefilledrectangle($img, 200, 0, 399, $height - 1, $green);
        // Höger 200 px: röd
        imagefilledrectangle($img, 400, 0, 599, $height - 1, $red);

        $path = __DIR__ . '/crop_test_gradient_horizontal.png';
        imagepng($img, $path);
        imagedestroy($img);

        try {
            $image = new Image($path);

            // Välj dimensioner så att optimalWidth > newWidth, så cropStartX används.
            $image->resizeImage(200, 200, 'crop');

            $resized = $image->getImageResized();
            $this->assertNotNull($resized, 'Resized image should not be null.');

            // Sampla mitten av den beskurna bilden
            $sampleX = (int) (imagesx($resized) / 2);
            $sampleY = (int) (imagesy($resized) / 2);

            $rgbIndex = imagecolorat($resized, $sampleX, $sampleY);
            $this->assertIsInt($rgbIndex);

            $colors = imagecolorsforindex($resized, $rgbIndex);
            /** @var array{red:int<0,255>,green:int<0,255>,blue:int<0,255>,alpha:int<0,127>} $colors */

            $redValue = $colors['red'];
            $greenValue = $colors['green'];
            $blueValue = $colors['blue'];

            // Mitten ska vara mest grön; mutanter som flyttar cropStartX
            // åt vänster/höger ger istället blått eller rött i mitten.
            $this->assertGreaterThan(
                $redValue,
                $greenValue,
                'Mittenpixeln ska vara grönare än röd (horisontell centrering).'
            );
            $this->assertGreaterThan(
                $blueValue,
                $greenValue,
                'Mittenpixeln ska vara grönare än blå (horisontell centrering).'
            );
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testGetOptimalCropDimensionsAreExact(): void
    {
        $image = new Image($this->testImagePath);

        $ref = new ReflectionClass(Image::class);
        $method = $ref->getMethod('getOptimalCrop');
        $method->setAccessible(true);

        // Fall 1: höjd begränsar (heightRatio är min)
        // width=800, height=600, newWidth=200, newHeight=600
        // widthRatio = 800 / 200 = 4
        // heightRatio = 600 / 600 = 1  => optimalRatio = 1
        /** @var array{optimalWidth:int,optimalHeight:int} $dims1 */
        $dims1 = $method->invoke($image, 200, 600);

        $this->assertSame(800, $dims1['optimalWidth'], 'optimalWidth ska vara 800 när höjd begränsar.');
        $this->assertSame(600, $dims1['optimalHeight'], 'optimalHeight ska vara 600 när höjd begränsar.');

        // Denna input dödar Division‑mutanten som ändrar heightRatio till * newHeight,
        // eftersom optimalRatio då blir 4 i stället för 1 → helt andra dimensioner.

        // Fall 2: båda ratio = 2 (båda dimensioner begränsar lika mycket)
        // width=800, height=600, newWidth=400, newHeight=300
        // widthRatio = 800 / 400 = 2
        // heightRatio = 600 / 300 = 2  => optimalRatio = 2
        /** @var array{optimalWidth:int,optimalHeight:int} $dims2 */
        $dims2 = $method->invoke($image, 400, 300);

        $this->assertSame(400, $dims2['optimalWidth'], 'optimalWidth ska vara 400 för 400x300‑crop.');
        $this->assertSame(300, $dims2['optimalHeight'], 'optimalHeight ska vara 300 för 400x300‑crop.');

        // Fall 3: höjd är mycket begränsande
        // width=800, height=600, newWidth=800, newHeight=100
        /** @var array{optimalWidth:int,optimalHeight:int} $dims3 */
        $dims3 = $method->invoke($image, 800, 100);

        $this->assertSame(800, $dims3['optimalWidth'], 'optimalWidth ska vara 800 när newWidth matchar full bredd.');
        $this->assertSame(600, $dims3['optimalHeight'], 'optimalHeight ska vara 600 när höjd skalar upp.');
    }

    public function testGetSizeByAutoLandscapeUsesRoundedScaledHeightLowFraction(): void
    {
        $image = new Image($this->testImagePath);

        $ref = new ReflectionClass(Image::class);
        $widthProp = $ref->getProperty('width');
        $heightProp = $ref->getProperty('height');
        $widthProp->setAccessible(true);
        $heightProp->setAccessible(true);

        // Ställ in dimensioner så att width > height och skalfaktorn ger 2.25
        // width = 4, height = 3, newWidth = 3:
        // scaledHeight = newWidth * (height / width) = 3 * (3/4) = 2.25
        $widthProp->setValue($image, 4);
        $heightProp->setValue($image, 3);

        $method = $ref->getMethod('getSizeByAuto');
        $method->setAccessible(true);

        /** @var array{optimalWidth:int,optimalHeight:int} $dims */
        $dims = $method->invoke($image, 3, 10);

        // round(2.25) = 2.
        // ceil-mutanten ger 3; ReturnRemoval-mutanten ger optimalHeight = newHeight (10).
        $this->assertSame(3, $dims['optimalWidth']);
        $this->assertSame(2, $dims['optimalHeight']);
    }

    public function testGetSizeByAutoLandscapeUsesRoundedScaledHeightHighFraction(): void
    {
        $image = new Image($this->testImagePath);

        $ref = new ReflectionClass(Image::class);
        $widthProp = $ref->getProperty('width');
        $heightProp = $ref->getProperty('height');
        $widthProp->setAccessible(true);
        $heightProp->setAccessible(true);

        // Samma width/height men annan newWidth:
        // width = 4, height = 3, newWidth = 5:
        // scaledHeight = 5 * (3/4) = 3.75
        $widthProp->setValue($image, 4);
        $heightProp->setValue($image, 3);

        $method = $ref->getMethod('getSizeByAuto');
        $method->setAccessible(true);

        /** @var array{optimalWidth:int,optimalHeight:int} $dims */
        $dims = $method->invoke($image, 5, 10);

        // round(3.75) = 4.
        $this->assertSame(5, $dims['optimalWidth']);
        $this->assertSame(4, $dims['optimalHeight']);
    }

    public function testAddWatermarkUsesResizedImageWhenBothOriginalAndResizedExist(): void
    {
        // Special-subklass som sätter olika storlekar på original och resized
        $image = new class extends Image {
            public function __construct()
            {
                // Skapa ett litet original: 2x2
                $orig = imagecreatetruecolor(2, 2);
                if ($orig === false) {
                    throw new RuntimeException('Kunde inte skapa originalbild i test-subklass.');
                }

                // Skapa en större "resized": 10x10
                $resized = imagecreatetruecolor(10, 10);
                if ($resized === false) {
                    throw new RuntimeException('Kunde inte skapa resized-bild i test-subklass.');
                }

                $blue  = imagecolorallocate($resized, 0, 0, 255);
                if ($blue === false) {
                    throw new RuntimeException('Kunde inte allokera färg i resized-bild.');
                }
                imagefill($resized, 0, 0, $blue);

                // Initiera parent-egenskaper manuellt
                $this->image        = $orig;
                $this->width        = imagesx($orig);
                $this->height       = imagesy($orig);
                $this->imageResized = $resized;
            }

            // Ignorera sökvägen; skapa ett litet watermark i stället
            public function openImage(string $filePath): GdImage
            {
                $wm = imagecreatetruecolor(2, 2);
                if ($wm === false) {
                    throw new RuntimeException('Kunde inte skapa watermark i test-subklass.');
                }
                $green = imagecolorallocate($wm, 0, 255, 0);
                if ($green === false) {
                    throw new RuntimeException('Kunde inte allokera färg i watermark.');
                }
                imagefill($wm, 0, 0, $green);
                return $wm;
            }
        };

        // Anropa med watermark placerat i nedre högra hörnet av 10x10-bilden.
        // Original (2x2) är FÖR LITET för (x=9, y=9, w=2, h=2) och imagecopy() ska misslyckas.
        // Resized (10x10) är PRECIS lagom stor för att imagecopy() ska lyckas.
        try {
            $image->addWatermark('ignored-path.png', 9, 9);
        } catch (RuntimeException $e) {
            $this->fail(
                'addWatermark() ska använda imageResized när den finns. '
                . 'Mutanten som väljer $this->image först orsakar RuntimeException: ' . $e->getMessage()
            );
        }

        // Om vi vill kan vi även säkerställa att resized-bilden fortfarande finns.
        $resized = $image->getImageResized();
        $this->assertNotNull($resized);
        $this->assertSame(10, imagesx($resized));
        $this->assertSame(10, imagesy($resized));
    }

    public function testResizeImageTreatsUppercaseOptionSameAsLowercase(): void
    {
        $image = new Image($this->testImagePath);

        // Kör först med "crop"
        $image->resizeImage(400, 300, 'crop');
        $lower = $image->getImageResized();
        $this->assertNotNull($lower);
        $this->assertSame(400, imagesx($lower));
        $this->assertSame(300, imagesy($lower));

        // Skapa ny instans och kör med "CROP"
        $image2 = new Image($this->testImagePath);
        $image2->resizeImage(400, 300, 'CROP');
        $upper = $image2->getImageResized();
        $this->assertNotNull($upper);
        $this->assertSame(400, imagesx($upper));
        $this->assertSame(300, imagesy($upper));
    }

    public function testSaveImageUsesDefaultQualityWhenNull(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 300);

        // Sätt defaultQuality till ett unikt värde
        $ref = new ReflectionClass(Image::class);
        $prop = $ref->getProperty('defaultQuality');
        $prop->setAccessible(true);
        $prop->setValue($image, 37);

        $out = __DIR__ . '/quality_default.jpg';
        $image->saveImage($out, null);
        $this->assertFileExists($out);
        // Vi testar bara att anropet fungerar; mutanten och originalet beter sig lika när quality=null
    }

    public function testSaveImageHonorsExplicitQualityArgument(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 300);

        $outLow = __DIR__ . '/quality_10.jpg';
        $outHigh = __DIR__ . '/quality_90.jpg';

        $image->saveImage($outLow, 10);
        $image->saveImage($outHigh, 90);

        $this->assertFileExists($outLow);
        $this->assertFileExists($outHigh);

        $sizeLow = filesize($outLow);
        $sizeHigh = filesize($outHigh);

        $this->assertIsInt($sizeLow);
        $this->assertIsInt($sizeHigh);
        $this->assertGreaterThan(0, $sizeLow);
        $this->assertGreaterThan(0, $sizeHigh);

        // Normalt ska quality=10 ge mindre fil än quality=90
        $this->assertLessThan(
            $sizeHigh,
            $sizeLow,
            'Låg JPEG-quality ska ge mindre fil än hög quality (dödar AssignCoalesce-mutanten)'
        );
    }

    public function testResizeImageThrowsWhenOptimalWidthIsZero(): void
    {
        // Subklass som fejk:ar getDimensions så att optimalWidth=0, optimalHeight>0
        $image = new class ($this->testImagePath) extends Image {
            protected function getDimensions(int $newWidth, int $newHeight, string $option): array
            {
                return ['optimalWidth' => 0, 'optimalHeight' => 100];
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ogiltiga bilddimensioner: 0 x 100');

        $image->resizeImage(400, 300, 'auto');
    }

    public function testGetSizeByAutoSquareUsesSquareBranch(): void
    {
        $image = new Image($this->testImagePath);

        $ref = new ReflectionClass(Image::class);
        $widthProp = $ref->getProperty('width');
        $heightProp = $ref->getProperty('height');
        $widthProp->setAccessible(true);
        $heightProp->setAccessible(true);

        // Gör bilden "kvadratisk" i interna dimensioner
        $widthProp->setValue($image, 500);
        $heightProp->setValue($image, 500);

        $method = $ref->getMethod('getSizeByAuto');
        $method->setAccessible(true);

        /** @var array{optimalWidth:int,optimalHeight:int} $dims */
        $dims = $method->invoke($image, 400, 300);

        // För en kvadratisk bild ska "square"-grenen: optimalWidth=newWidth, optimalHeight=newHeight
        $this->assertSame(400, $dims['optimalWidth'], 'Square-bilden ska använda newWidth för optimalWidth.');
        $this->assertSame(300, $dims['optimalHeight'], 'Square-bilden ska använda newHeight för optimalHeight.');
    }

    public function testResizeImageThrowsOnUnknownOption(): void
    {
        $image = new Image($this->testImagePath);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Okänt alternativ "foo".');

        $image->resizeImage(400, 300, 'foo');
    }

    public function testResizeImageThrowsWhenOptimalHeightIsZero(): void
    {
        $image = new class ($this->testImagePath) extends Image {
            protected function getDimensions(int $newWidth, int $newHeight, string $option): array
            {
                return ['optimalWidth' => 100, 'optimalHeight' => 0];
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ogiltiga bilddimensioner: 100 x 0');

        $image->resizeImage(400, 300, 'auto');
    }

    public function testConstructorSupportedGifFormat(): void
    {
        // Skapa en enkel GIF-bild för att testa GIF-stödet
        $gifPath = __DIR__ . '/test_image.gif';

        $img = imagecreatetruecolor(10, 10);
        $color = imagecolorallocate($img, 0, 255, 0);
        if ($color === false) {
            $this->fail('Kunde inte allokera färg för GIF-testbilden.');
        }
        imagefill($img, 0, 0, $color);
        imagegif($img, $gifPath);
        imagedestroy($img);

        try {
            $image = new Image($gifPath);
            $this->assertInstanceOf(Image::class, $image);
        } finally {
            if (file_exists($gifPath)) {
                unlink($gifPath);
            }
        }
    }

    public function testConstructorSupportedWebpFormatWhenAvailable(): void
    {
        if (!function_exists('imagewebp') || !function_exists('imagecreatefromwebp')) {
            $this->markTestSkipped('WEBP stöds inte av denna PHP-installation.');
        }

        $webpPath = __DIR__ . '/test_image.webp';

        $img = imagecreatetruecolor(10, 10);
        $color = imagecolorallocate($img, 0, 0, 255);
        if ($color === false) {
            $this->fail('Kunde inte allokera färg för WEBP-testbilden.');
        }
        imagefill($img, 0, 0, $color);
        imagewebp($img, $webpPath);
        imagedestroy($img);

        try {
            $image = new Image($webpPath);
            $this->assertInstanceOf(Image::class, $image);
        } finally {
            if (file_exists($webpPath)) {
                unlink($webpPath);
            }
        }
    }

    public function testResizeImageLandscapeScalesHeightWithFixedWidth(): void
    {
        $image = new Image($this->testImagePath); // 800x600

        // landscape med newWidth=400
        $image->resizeImage(400, 999, 'landscape'); // newHeight ignoreras i landscape

        $resized = $image->getImageResized();
        $this->assertNotNull($resized);

        // Bredden ska vara exakt 400
        $this->assertSame(400, imagesx($resized));

        // Höjden ska skalas proportionellt: 400 * (600/800) = 300
        $this->assertSame(300, imagesy($resized));
    }

    public function testRotateImageUsesResizedImageWhenAvailable(): void
    {
        $image = new Image($this->testImagePath);

        // Först ändra storlek – skapar imageResized (400x300) bredvid originalet (800x600)
        $image->resizeImage(400, 300);

        // Rotera med vinkel 0 – dimensionerna ska fortfarande följa den RESIZADE bilden
        $image->rotateImage(0);

        $rotated = $image->getImageResized();
        $this->assertNotNull($rotated);

        // Coalesce-mutanten som använder $this->image före $this->imageResized ger 800x600 här
        $this->assertSame(400, imagesx($rotated));
        $this->assertSame(300, imagesy($rotated));
    }

    public function testRotateImageUsesBlackBackgroundForNewAreas(): void
    {
        $image = new Image($this->testImagePath);

        // Rotera med 45° så nya hörn skapas och fylls med bakgrundsfärg
        $image->rotateImage(45);

        $rotated = $image->getImageResized();
        $this->assertNotNull($rotated);

        // Sampla ett hörn – bakgrundsfärgen ska vara exakt svart (#000000) när bgColor=0
        $this->assertPixelColor($rotated, 0, 0, 0, 0, 0);
    }

    public function testAddWatermarkUsesExactSourceAndDestinationOffsets(): void
    {
        $image = new Image($this->testImagePath); // röd bakgrund

        // Först resize:a så att imageResized används som basbild
        $image->resizeImage(400, 300);

        // Skapa ett litet mönstrat watermark så att både destination- och source-offsets syns
        $wm = imagecreatetruecolor(2, 2);
        $blue  = imagecolorallocate($wm, 0, 0, 255);
        $red   = imagecolorallocate($wm, 255, 0, 0);
        $green = imagecolorallocate($wm, 0, 255, 0);
        $white = imagecolorallocate($wm, 255, 255, 255);

        if ($blue === false || $red === false || $green === false || $white === false) {
            $this->fail('Kunde inte allokera färger för watermark-mönstret.');
        }

        imagesetpixel($wm, 0, 0, $blue);
        imagesetpixel($wm, 1, 0, $red);
        imagesetpixel($wm, 0, 1, $green);
        imagesetpixel($wm, 1, 1, $white);

        $wmPath = __DIR__ . '/watermark_pattern.png';
        imagepng($wm, $wmPath);
        imagedestroy($wm);

        try {
            // Anropa UTAN x/y → default = (0,0)
            $image->addWatermark($wmPath);

            $resized = $image->getImageResized();
            $this->assertNotNull($resized);

            // Kontrollera att mönstret exakt överlagras i hörnet:
            // (0,0) => blå, (1,0) => röd, (0,1) => grön, (1,1) => vit.
            // Coalesce-mutanten som väljer $this->image före $this->imageResized
            // gör att dessa pixlar *inte* får mönstret → testet faller.
            $this->assertPixelColor($resized, 0, 0, 0, 0, 255);   // blå
            $this->assertPixelColor($resized, 1, 0, 255, 0, 0);   // röd
            $this->assertPixelColor($resized, 0, 1, 0, 255, 0);   // grön
            $this->assertPixelColor($resized, 1, 1, 255, 255, 255);   // vit
        } finally {
            if (file_exists($wmPath)) {
                @unlink($wmPath);
            }
        }
    }

    /**
     * Hjälpmetod för att asserta en pixel-färg exakt.
     */
    private function assertPixelColor(GdImage $img, int $x, int $y, int $r, int $g, int $b): void
    {
        $index = imagecolorat($img, $x, $y);
        $this->assertIsInt($index, "Kunde inte läsa pixel vid ($x, $y)");

        $colors = imagecolorsforindex($img, $index);
        /** @var array{red:int<0,255>,green:int<0,255>,blue:int<0,255>,alpha:int<0,127>} $colors */

        $this->assertSame($r, $colors['red'], "R-fel vid ($x, $y)");
        $this->assertSame($g, $colors['green'], "G-fel vid ($x, $y)");
        $this->assertSame($b, $colors['blue'], "B-fel vid ($x, $y)");
    }
}
