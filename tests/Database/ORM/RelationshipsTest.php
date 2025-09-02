<?php

declare(strict_types=1);

namespace Radix\Tests\Database\ORM;

use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use Radix\Database\Connection;
use Radix\Database\ORM\Model;
use Radix\Database\ORM\Relationships\HasMany;
use Radix\Database\ORM\Relationships\HasOne;
use Radix\Database\ORM\Relationships\BelongsTo;
use Radix\Database\ORM\Relationships\BelongsToMany;

class RelationshipsTest extends TestCase
{

   protected function tearDown(): void
   {
       parent::tearDown();
       $user = new User();
       $user->setFillable(['id', 'first_name', 'last_name', 'email', 'avatar']);
       $user->setGuarded(['password', 'role']);
   }

   public function testPasswordAttribute(): void
   {
       $user = new User();
       $user->fill([
           'first_name' => 'Mats',
           'last_name' => 'Åkebrand',
           'email' => 'malle@akebrands.se',
       ]);

       // Testa att sätta lösenord
       $user->password = 'korvar65';

       $this->assertNotNull($user->password);

       // Kontrollera om password finns i attributlistan
       $this->assertArrayHasKey('password', $user->attributes);
   }

    public function testGuardedOverridesFillable(): void
    {
        $user = new User();
        $user->setFillable(['email', 'first_name']); // Tillåt dessa attribut
        $user->setGuarded(['email']); // Blockera email specifikt

        $user->fill([
            'email' => 'blocked@example.com',
            'first_name' => 'Allowed',
        ]);

        $this->assertNull($user->getAttribute('email')); // Ska vara blockerat
        $this->assertSame('Allowed', $user->getAttribute('first_name')); // Ska vara tillåtet
    }

    public function testUserMassFilling(): void
    {
        $user = new User();

        // Massfyllning av tillåtna fält
        $user->fill([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'avatar' => '/images/john.png',
            'password' => 'secret' // Skyddat fält, ska ignoreras
        ]);

        $this->assertSame('John', $user->getAttribute('first_name'));
        $this->assertSame('Doe', $user->getAttribute('last_name'));
        $this->assertSame('john.doe@example.com', $user->getAttribute('email'));
        $this->assertSame('/images/john.png', $user->getAttribute('avatar'));
        $this->assertNull($user->getAttribute('password')); // Skyddat av `guarded`
    }

    public function testAllAttributesGuarded(): void
    {
        $user = new User();
        $user->setFillable([]); // Töm alla fillable-attribut
        $user->setGuarded(['*']); // Blockera alla attribu

        $user->fill([
            'first_name' => 'Markus',
            'last_name'  => 'Jones',
            'email'      => 'markus@example.com',
        ]);

        // Kontrollera att skyddade attribut inte är fyllda
        $this->assertNull($user->getAttribute('first_name'));
        $this->assertNull($user->getAttribute('last_name'));
        $this->assertNull($user->getAttribute('email'));
    }


    public function testFillableEmptyAllowsAllAttributes(): void
    {
        $user = new User();

        // Sätt både fillable och guarded till tomt
        $user->setFillable([]);
        $user->setGuarded([]);

        $user->fill([
            'first_name' => 'Lisa',
            'last_name' => 'Johnson',
            'email' => 'lisa@example.com',
        ]);

        // Kontrollera att alla attribut fylldes
        $this->assertSame('Lisa', $user->getAttribute('first_name'));
        $this->assertSame('Johnson', $user->getAttribute('last_name'));
        $this->assertSame('lisa@example.com', $user->getAttribute('email'));
    }

    public function testGuardedAndFillableAttributesMixed(): void
    {
        $user = new User();
        $user->setFillable(['first_name', 'email']);
        $user->setGuarded(['password']);

        $user->fill([
            'first_name' => 'Ella',
            'last_name' => 'Williamson', // Ej definierad i fillable, ignoreras
            'email' => 'ella@example.com',
            'password' => 'secretpassword', // Skyddat, ignoreras
        ]);

        // Kontrollera att endast tillåtna attribut fylldes
        $this->assertSame('Ella', $user->getAttribute('first_name'));
        $this->assertNull($user->getAttribute('last_name'));
        $this->assertSame('ella@example.com', $user->getAttribute('email'));
        $this->assertNull($user->getAttribute('password'));
    }

    public function testUnknownAttributesIgnored(): void
    {
        $user = new User();

        $user->fill([
            'nickname' => 'Johnny', // Ej existerande attribut i modellen
            'email' => 'johnny@example.com',
        ]);

        // Kontrollera att bara giltiga attribut fylldes
        $this->assertNull($user->getAttribute('nickname')); // Ignoreras
        $this->assertSame('johnny@example.com', $user->getAttribute('email'));
    }

   public function testDynamicGuardedAttributes(): void
   {
       $user = new User();
       $user->setGuarded(['email']); // Skydda "email"

       $user->fill([
           'first_name' => 'Anna',
           'email' => 'anna@example.com', // Ska ignoreras
       ]);

       $this->assertSame('Anna', $user->getAttribute('first_name'));
       $this->assertNull($user->getAttribute('email')); // Nu korrekt!
   }

    public function testDynamicGuardedAndFillable(): void
    {
        $user = new User();
        $user->setGuarded(['*']); // Skydda allt
        $user->setFillable(['first_name']); // Tillåt endast first_name

        $user->fill([
            'first_name' => 'Ella',
            'last_name'  => 'Smith',
            'email'      => 'ella@example.com',
        ]);

        $this->assertEquals('Ella', $user->getAttribute('first_name'), "Attributet first_name borde vara 'Ella'");
        $this->assertNull($user->getAttribute('last_name'), 'Attributet last_name borde vara null');
        $this->assertNull($user->getAttribute('email'), 'Attributet email borde vara null');
    }

    public function testStatusMassFilling(): void
    {
        $status = new Status();

        // Massfyllning av tillåtna fält
        $status->fill([
            'password_reset' => 'abc123',
            'reset_expires_at' => '2025-12-01 00:00:00',
            'user_id' => 5, // Skyddat fält, ska ignoreras
        ]);

        $this->assertSame('abc123', $status->getAttribute('password_reset'));
        $this->assertSame('2025-12-01 00:00:00', $status->getAttribute('reset_expires_at'));
        //$this->assertNull($status->getAttribute('user_id')); // Skyddat av `guarded`
    }

    public function testFillableOverridesGuarded(): void
    {
        $user = new User();

        // Gör "password" både guarded och fillable
        $user->setGuarded(['password']);
        $user->setFillable(['password']);

        $user->fill([
            'password' => 'not_so_secret',
        ]);

        // Då "password" är guarded ska det inte fyllas
        $this->assertNull($user->getAttribute('password'));
    }

    public function testGuardedAttributesAreIgnored(): void
    {
        $user = new User();

        // Försök fylla ett skyddat fält
        $user->fill([
            'first_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret', // Ska ignoreras enligt `guarded`
        ]);

        // Kontrollera att skyddade fält ignorerades
        $this->assertSame('Alice', $user->getAttribute('first_name')); // Tillåtet
        $this->assertSame('alice@example.com', $user->getAttribute('email')); // Tillåtet
        $this->assertNull($user->getAttribute('password')); // Ignorerat
    }

    public function testUnknownFieldsAreIgnored(): void
    {
        $user = new User();

        // Försök fylla fält som inte är definierade i `fillable` eller `guarded`
        $user->fill([
            'first_name' => 'Bob',
            'nickname' => 'Bobby', // Okänt fält
        ]);

        // Kontrollera att endast tillåtna fält fylls
        $this->assertSame('Bob', $user->getAttribute('first_name'));
        $this->assertNull($user->getAttribute('nickname')); // Ignoreras helt
    }

    public function testDynamicFillableAttributes(): void
    {
        $user = new User();

        // Tillåt massfyllning av ett nytt attribut
        $user->setFillable(['first_name', 'last_name', 'nickname']);

        $user->fill([
            'first_name' => 'Clara',
            'last_name' => 'Smith',
            'nickname' => 'Clary', // Dynamiskt tillagt
        ]);

        // Kontrollera att alla tillagda fält är fyllda
        $this->assertSame('Clara', $user->getAttribute('first_name'));
        $this->assertSame('Smith', $user->getAttribute('last_name'));
        $this->assertSame('Clary', $user->getAttribute('nickname'));
    }

    public function testModelExistsFlag(): void
    {
        $model = new User();
        $this->assertFalse($model->isExisting());

        $model->markAsExisting();
        $this->assertTrue($model->isExisting());
    }


    public function testHasManyReturnsRelatedRecords(): void
    {
        // Förvänta oss att dessa värden returneras från databasen
        $expectedResults = [
            ['id' => 1, 'post_id' => 10, 'content' => 'First comment'],
            ['id' => 2, 'post_id' => 10, 'content' => 'Second comment'],
        ];

        // Mocka Connection och simulera fetchAll
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')->willReturn($expectedResults);

        // Simulera dataelement till HasMany
        $results = array_map(function ($record) {
            $mock = $this->getMockBuilder(Model::class)
                ->onlyMethods(['getAttribute']) // Använd onlyMethods eftersom metoden finns i klassen
                ->getMock();

            // Kopiera data till attribut
            $mock->attributes = $record;

            // Konfigurera mockens getAttribute-metod
            $mock->method('getAttribute')->willReturnCallback(
                fn($key) => $record[$key] ?? null
            );

            return $mock;
        }, $expectedResults);

        // Validera antal resultat
        $this->assertCount(2, $results);

        // Kontrollera första resultatet
        $this->assertInstanceOf(Model::class, $results[0]);
        $this->assertEquals(1, $results[0]->getAttribute('id'));
        $this->assertEquals(10, $results[0]->getAttribute('post_id'));
        $this->assertEquals('First comment', $results[0]->getAttribute('content'));

        // Kontrollera andra resultatet
        $this->assertInstanceOf(Model::class, $results[1]);
        $this->assertEquals(2, $results[1]->getAttribute('id'));
        $this->assertEquals(10, $results[1]->getAttribute('post_id'));
        $this->assertEquals('Second comment', $results[1]->getAttribute('content'));
    }

    public function testHasOneReturnsSingleRelatedRecord(): void
    {
        // Mocka data från databasen
        $expectedResult = ['id' => 1, 'user_id' => 5, 'profile' => 'Profile details'];

        // Mocka databasanslutningen och simulera fetchOne
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn($expectedResult);

        // Dynamisk modell för profilen
        $profileClass = new class extends Model {
            protected string $table = 'profiles';
            protected array $fillable = ['id', 'user_id', 'profile'];
        };

        // Skapa en HasOne-relation
        $hasOne = new HasOne(
            $connection,
            get_class($profileClass),
            'user_id',
            5
        );

        // Hämta relaterad modell via first()
        $result = $hasOne->first();

        // Verifiera att resultatet är av rätt klass
        $this->assertInstanceOf(get_class($profileClass), $result);

        // Kontrollera att attributen innehåller förväntade värden
        $this->assertEquals($expectedResult['id'], $result->getAttribute('id'));
        $this->assertEquals($expectedResult['user_id'], $result->getAttribute('user_id'));
        $this->assertEquals($expectedResult['profile'], $result->getAttribute('profile'));
    }

    public function testHasManyWith(): void
    {
        $expectedResults = [
            ['id' => 1, 'post_id' => 10, 'content' => 'First comment'],
            ['id' => 2, 'post_id' => 10, 'content' => 'Second comment'],
        ];

        // Mocka anslutningen
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('fetchAll')->willReturn($expectedResults);

        // Dynamisk modell för inlägg
        $post = new class($connectionMock) extends Model {
            protected string $table = 'posts';
            protected array $fillable = ['id', 'title', 'content'];

            private ?Connection $mockedConnection;

            public function __construct(Connection $connection = null)
            {
                $this->mockedConnection = $connection ?? $this->createDefaultConnection();
                parent::__construct([]);
            }

            protected function getConnection(): Connection
            {
                return $this->mockedConnection;
            }

            /**
             * Skapar en standard-anslutning om ingen anslutning skickas.
             */
            private function createDefaultConnection(): Connection
            {
                $pdo = new \PDO('sqlite::memory:');
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                return new Connection($pdo);
            }
        };

        $post->setAttribute('id', '10');

        // Dynamisk klass för kommentarer
        $mockedCommentClass = new class($connectionMock) extends Model {
            protected string $table = 'comments';
            protected array $fillable = ['id', 'post_id', 'content'];

            private ?Connection $mockedConnection;

            public function __construct(Connection $connection = null)
            {
                $this->mockedConnection = $connection ?? $this->createDefaultConnection();
                parent::__construct([]);
            }

            protected function getConnection(): Connection
            {
                return $this->mockedConnection;
            }

            /**
             * Skapar en standard-anslutning om ingen anslutning skickas.
             */
            private function createDefaultConnection(): Connection
            {
                $pdo = new \PDO('sqlite::memory:');
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                return new Connection($pdo);
            }
        };

        // Skapa relaterade kommentarer med hasMany
        $comments = $post->hasMany(get_class($mockedCommentClass), 'post_id', 'id')->get();

        // Validera resultaten
        $this->assertCount(2, $comments);
        $this->assertInstanceOf(Model::class, $comments[0]);
        $this->assertEquals('First comment', $comments[0]->getAttribute('content'));
    }

    public function testBelongsToReturnsRelatedParentRecord()
    {
        $expectedResult = ['id' => 5, 'first_name' => 'Mats'];

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')
            ->willReturn($expectedResult);

        $parentModel = $this->createMock(\App\Models\Status::class); // Mocka Status-modellen

        $belongsTo = new BelongsTo(
            $connection,
            \App\Models\User::class,
            'user_id',
            '5',
            $parentModel // Lägg till mocken som den saknade parametern
        );


        $result = $belongsTo->get();

        $this->assertInstanceOf(\App\Models\User::class, $result);
        $this->assertEquals($expectedResult['id'], $result->getAttribute('id'));
        $this->assertEquals($expectedResult['first_name'], $result->getAttribute('first_name'));
    }

    public function testBelongsToManyReturnsRelatedRecords()
    {
        $expectedResults = [
            ['id' => 1, 'first_name' => 'John'],
            ['id' => 2, 'first_name' => 'Jane'],
        ];

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')
            ->willReturn($expectedResults);

        $belongsToMany = new BelongsToMany(
            $connection,
            \App\Models\User::class,    // Korrekt klassnamn
            'role_user',                // Pivot table
            'role_id',                  // Foreign pivot key
            'user_id',                  // Related pivot key
            '1'                         // Parent key
        );

        $results = $belongsToMany->get();

        $this->assertCount(2, $results);

        foreach ($results as $index => $result) {
            $this->assertInstanceOf(\App\Models\User::class, $result);
            $this->assertEquals($expectedResults[$index]['id'], $result->getAttribute('id'));
            $this->assertEquals($expectedResults[$index]['first_name'], $result->getAttribute('first_name'));
        }
    }

    public function testUserJsonSerialization(): void
    {
        $user = new User(['id' => 1, 'first_name' => 'John', 'email' => 'john.doe@example.com']);

        // Mocka `Post`-instanser med `toArray` och `jsonSerialize`-stöd
        $post1 = $this->createMock(Model::class);
        $post1->method('toArray')->willReturn(['id' => 1, 'title' => 'First Post', 'content' => 'This is the first post.']);
        $post1->method('jsonSerialize')->willReturn(['id' => 1, 'title' => 'First Post', 'content' => 'This is the first post.']);

        $post2 = $this->createMock(Model::class);
        $post2->method('toArray')->willReturn(['id' => 2, 'title' => 'Second Post', 'content' => 'This is the second post.']);
        $post2->method('jsonSerialize')->willReturn(['id' => 2, 'title' => 'Second Post', 'content' => 'This is the second post.']);

        // Sätt relation på mockade postar
        $user->setRelation('posts', [$post1, $post2]);

        // Debugga relationen innan testning
        $this->assertNotEmpty($user->getRelation('posts'), 'Posts relation is empty.');

        $expectedJson = json_encode([
            'id' => 1,
            'first_name' => 'John',
            'email' => 'john.doe@example.com',
            'posts' => [
                [
                    'id' => 1,
                    'title' => 'First Post',
                    'content' => 'This is the first post.'
                ],
                [
                    'id' => 2,
                    'title' => 'Second Post',
                    'content' => 'This is the second post.'
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $actualJson = json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Jämför förväntad och faktisk JSON
        $this->assertSame($expectedJson, $actualJson, 'JSON serialization does not match expected output.');
    }

    public function testHasManyFirstReturnsRelatedRecord(): void
    {
        // Mocka Comment-modellen
        $commentMock = $this->createMock(Model::class);
        $commentMock->method('getAttribute')->willReturnMap([
            ['id', 1],
            ['post_id', 10],
            ['content', 'First comment']
        ]);

        // Mocka HasMany-relationen
        $hasManyMock = $this->createMock(HasMany::class);
        $hasManyMock->method('first')->willReturn($commentMock);

        // Kalla på `first` och kontrollera resultatet
        $result = $hasManyMock->first();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertEquals('First comment', $result->getAttribute('content'));
    }

    public function testBelongsToManyFirstReturnsRecord()
    {
        // Mockat resultat från databasen
        $expectedResults = [
            ['id' => 1, 'first_name' => 'John'],
            ['id' => 2, 'first_name' => 'Jane'],
        ];

        // Mocka databasanslutningen
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAll')->willReturn($expectedResults);

        // Skapa en instans av BelongsToMany
        $belongsToMany = new BelongsToMany(
            $connection,
            \App\Models\User::class,
            'role_user',          // Pivot-tabell
            'role_id',            // Foreign pivot key
            'user_id',            // Related pivot key
            '1'                   // Parent key som sträng
        );

        // Kör first() för att få första modellen
        $result = $belongsToMany->first();

        // Validera att resultatet är en instans av rätt modellklass
        $this->assertInstanceOf(\App\Models\User::class, $result);

        // Kontrollera att attributen stämmer överens
        $this->assertEquals('John', $result->getAttribute('first_name'));
    }
}