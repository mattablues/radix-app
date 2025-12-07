<?php

declare(strict_types=1);

namespace Radix\Tests;

final class UploadMkdirSpy
{
    public static ?int $lastPermissions = null;

    /** @var list<string> */
    public static array $failPaths = [];

    public static function reset(): void
    {
        self::$lastPermissions = null;
        self::$failPaths = [];
    }
}

use PHPUnit\Framework\TestCase;
use Radix\File\Upload;
use Radix\Support\Validator;
use Radix\Tests\Support\TestableValidator;
use RuntimeException;

class UploadTest extends TestCase
{
    protected string $uploadDirectory;

    protected function setUp(): void
    {
        // Skapa tillfällig uppladdningsmapp
        $this->uploadDirectory = __DIR__ . '/uploads';
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0o755, true);
        }

        // Skapa en mockad bildfil för att simulera en uppladdning
        $image = imagecreatetruecolor(100, 100);

        $color = imagecolorallocate($image, 255, 0, 0);
        if ($color === false) {
            $this->fail('Kunde inte allokera färg för testbilden.');
        }

        imagefill($image, 0, 0, $color); // Röd bild
        imagejpeg($image, $this->uploadDirectory . '/test_image.jpg');
        imagedestroy($image);
    }

    /**
     * Mocka Radix\File\mkdir för att kunna spåra rättigheter och simulera fel.
     */
    protected function mockUploadMkdir(): void
    {
        UploadMkdirSpy::reset();

        if (!function_exists('Radix\File\mkdir')) {
            eval('namespace Radix\File; function mkdir($directory, $permissions = 0777, $recursive = false, $context = null) {
                \\Radix\\Tests\\UploadMkdirSpy::$lastPermissions = $permissions;
                if (in_array($directory, \\Radix\\Tests\\UploadMkdirSpy::$failPaths, true)) {
                    return false;
                }
                /** @var resource|null $context */
                return \\mkdir($directory, $permissions, $recursive, $context);
            }');
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->uploadDirectory);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testConstructorCreatesUploadDirectoryWith0755PermissionsWhenMissing(): void
    {
        $this->mockUploadMkdir();

        $dir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'upload_perm_' . uniqid('', true);

        // Säkerställ att katalogen inte finns innan
        if (is_dir($dir)) {
            @rmdir($dir);
        }

        $file = [
            'name' => 'dummy.txt',
            'type' => 'text/plain',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_OK,
            'size' => 0,
        ];

        $upload = new Upload($file, $dir);
        $this->assertInstanceOf(Upload::class, $upload);
        $this->assertDirectoryExists($dir, 'Katalogen ska ha skapats av konstruktorn.');

        // På Unix kan vi också verifiera rättigheter och permissions-argumentet
        if (DIRECTORY_SEPARATOR === '/') {
            $perms = fileperms($dir) & 0o777;
            $this->assertSame(0o755, $perms, sprintf('Katalogrättigheter ska vara 0755, fick 0%o', $perms));
            $this->assertSame(0o755, UploadMkdirSpy::$lastPermissions, 'mkdir() ska ha anropats med 0755.');
        }
    }

    public function testGenerateFileNameUsesBaseNameAndLowercasedExtension(): void
    {
        $file = [
            'name' => 'My IMAGE.JPG',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400,
        ];

        $upload = new class ($file, $this->uploadDirectory) extends Upload {
            /**
             * @param array<string,mixed> $file
             */
            public function __construct(array $file, string $uploadDirectory)
            {
                parent::__construct($file, $uploadDirectory);
            }

            public function exposedGenerateFileName(): string
            {
                return $this->generateFileName();
            }
        };

        $generated = $upload->exposedGenerateFileName();

        // Ska sluta med .jpg (lowercased extension)
        $this->assertStringEndsWith('.jpg', $generated, 'Filen ska använda lowercased extension.');

        // Hitta sista punkten och separera bas/extension
        $lastDot = strrpos($generated, '.');
        $this->assertNotFalse($lastDot, 'Filen ska innehålla en punkt innan extensionen.');

        $base = substr($generated, 0, $lastDot);
        $ext  = substr($generated, $lastDot + 1);

        $this->assertSame('jpg', $ext, 'Extension ska vara exakt "jpg".');
        $this->assertNotSame('', $base, 'Basdelen av filnamnet får inte vara tom.');
    }

    public function testGenerateFileNameFallsBackWhenNameMissingOrNotString(): void
    {
        // Fall 1: tom sträng
        $fileEmpty = [
            'name' => '',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400,
        ];

        $uploadEmpty = new class ($fileEmpty, $this->uploadDirectory) extends Upload {
            /**
             * @param array<string,mixed> $file
             */
            public function __construct(array $file, string $uploadDirectory)
            {
                parent::__construct($file, $uploadDirectory);
            }

            public function exposedGenerateFileName(): string
            {
                return $this->generateFileName();
            }
        };

        $nameEmpty = $uploadEmpty->exposedGenerateFileName();

        // Fallback: inget namn => inget bild-extension-suffix
        $this->assertDoesNotMatchRegularExpression(
            '/\.(jpg|jpeg|png|gif|webp)$/i',
            $nameEmpty,
            'Fallback-filen ska inte sluta med bild-extension när name är tom.'
        );

        // Fall 2: icke-sträng name
        $fileNonString = [
            'name' => 123, // ogiltig typ
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400,
        ];

        $uploadNonString = new class ($fileNonString, $this->uploadDirectory) extends Upload {
            /**
             * @param array<string,mixed> $file
             */
            public function __construct(array $file, string $uploadDirectory)
            {
                parent::__construct($file, $uploadDirectory);
            }

            public function exposedGenerateFileName(): string
            {
                return $this->generateFileName();
            }
        };

        $nameNonString = $uploadNonString->exposedGenerateFileName();

        $this->assertDoesNotMatchRegularExpression(
            '/\.(jpg|jpeg|png|gif|webp)$/i',
            $nameNonString,
            'Fallback-filen ska inte sluta med bild-extension när name inte är sträng.'
        );
    }

    public function testNullableFileUpload(): void
    {
        $data = [
            'avatar' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE, // Ingen fil skickades
                'size' => 0,
            ],
        ];

        $rules = [
            'avatar' => 'nullable|file_size:2|file_type:image/jpeg,image/png',
        ];

        $validator = new Validator($data, $rules);

        // Validering ska passera om ingen fil skickades och fältet är nullable
        $this->assertTrue($validator->validate(), 'Valideringen ska passera eftersom avatar är nullable.');
    }



    /**
     * Mocka move_uploaded_file för att kopiera filer istället för att använda native-behaviour under test.
     */
    protected function mockMoveUploadedFile(): void
    {
        if (!function_exists('Radix\File\move_uploaded_file')) {
            eval('namespace Radix\File; function move_uploaded_file($from, $to) {
                if (!file_exists($from)) {
                    return false;
                }
                return copy($from, $to);
            }');
        }
    }

    public function testValidateFileTypeAndSizeIndividually(): void
    {
        $mockFile = [
            'name' => 'example.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'size' => 102400, // 100 KB
            'error' => UPLOAD_ERR_OK, // Mocka som en korrekt uppladdad fil
        ];

        $validator = new TestableValidator($mockFile, []);

        // Testa filtyp individuellt
        $isTypeValid = $validator->testFileType($mockFile, 'image/jpeg,image/png');
        $this->assertTrue($isTypeValid, 'MIME-typ-valideringen ska godkänna image/jpeg.');

        // Testa filstorlek individuellt
        $isSizeValid = $validator->testFileSize($mockFile, '2'); // 2 MB
        $this->assertTrue($isSizeValid, 'Filstorleksvalideringen ska godkänna 100 KB.');
    }

    public function testValidateValidFile(): void
    {
        $data = [
            'avatar' => [
                'name' => 'test_image.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
                'error' => UPLOAD_ERR_OK,
                'size' => 102400, // 100 KB
            ],
        ];

        $rules = [
            'avatar' => 'nullable|file_size:2|file_type:image/jpeg,image/png',
        ];

        $validator = new Validator($data, $rules);

        // Validering ska passera för en giltig fil
        $this->assertTrue($validator->validate(), 'Filvalideringen ska godkänna en giltig fil.');
    }


    public function testValidateInvalidFileType(): void
    {
        $this->mockMoveUploadedFile();

        $file = [
            'name' => 'test_invalid.pdf',
            'type' => 'application/pdf', // Felaktig MIME-typ
            'tmp_name' => $this->uploadDirectory . '/test_invalid.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400, // 100 KB
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $isValid = $upload->validate([
            'avatar' => 'file_type:image/jpeg,image/png',
        ]);

        $this->assertFalse($isValid, 'Filvalideringen ska misslyckas för en ogiltig MIME-typ.');
        $this->assertNotEmpty($upload->getErrors(), 'Felmeddelanden ska genereras.');
    }


    public function testValidateExceededFileSize(): void
    {
        $this->mockMoveUploadedFile();

        $file = [
            'name' => 'test_large_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 5120000, // 5 MB
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $isValid = $upload->validate([
            'avatar' => 'file_size:2', // Max 2 MB
        ]);

        $this->assertFalse($isValid, 'Filvalideringen ska misslyckas för en för stor fil.');
    }

    public function testSaveValidFile(): void
    {
        $this->mockMoveUploadedFile();

        // Mockad giltig upload-fil
        $file = [
            'name' => 'test_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400, // 100 KB
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $savedPath = $upload->save();
        $this->assertFileExists($savedPath, 'Filen ska sparas korrekt på målplatsen.');
    }

    public function testSaveUsesTrimmedUploadDirectoryWithSingleDirectorySeparator(): void
    {
        $this->mockMoveUploadedFile();

        // Lägg till trailing slash i uploadDirectory för att testa rtrim-logiken
        $dirWithSlash = rtrim($this->uploadDirectory, '/\\') . '/';

        $file = [
            'name' => 'explicit_name.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400,
        ];

        $upload = new Upload($file, $dirWithSlash);

        $savedPath = $upload->save('myfile.jpg');

        // Upload::save använder alltid '/' som separator internt
        $expected = rtrim($dirWithSlash, '/\\') . '/myfile.jpg';

        // UnwrapRtrim-mutanter och ConcatOperandRemoval ger fel sökväg (saknar/duplicerar separator)
        $this->assertSame($expected, $savedPath, 'save() ska använda rtrim(uploadDirectory) + "/" + filnamn.');
        $this->assertFileExists($savedPath);
    }

    public function testErrorHandling(): void
    {
        $this->mockMoveUploadedFile();

        // Mocka en fil utan uppladdning
        $file = [
            'name' => 'test_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $isValid = $upload->validate([
            'type' => 'file_type:image/jpeg,image/png',
        ]);

        $this->assertFalse($isValid, 'Valideringen ska misslyckas om ingen giltig fil laddas upp.');

        $this->assertNotEmpty($upload->getErrors(), 'Felmeddelanden ska genereras.');
    }

    public function testSaveThrowsOnInvalidTmpNameWhenNotString(): void
    {
        $this->mockMoveUploadedFile();

        $file = [
            'name' => 'test_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => 123, // ogiltig typ
            'error' => UPLOAD_ERR_OK,
            'size' => 102400,
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ogiltigt tmp_name för uppladdad fil.');

        $upload->save();
    }
}
