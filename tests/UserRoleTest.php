<?php

declare(strict_types=1);

namespace Radix\Tests;

use App\Enums\Role;
use App\Models\User;
use PDO;
use PHPUnit\Framework\TestCase;
use Radix\Container\ApplicationContainer;
use Radix\Container\Container;

class UserRoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ApplicationContainer::reset();
        $container = new Container();

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Skapa tabell (SQLite)
        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                avatar TEXT NOT NULL DEFAULT '/images/graphics/avatar.png',
                role TEXT NOT NULL DEFAULT 'user',
                created_at TEXT NULL,
                updated_at TEXT NULL,
                deleted_at TEXT NULL
            );
        ");

        // Registrera PDO i containern
        $container->addShared(PDO::class, fn() => $pdo);
        $container->add('Psr\Container\ContainerInterface', fn() => $container);

        ApplicationContainer::set($container);
    }

    private function makeUser(string $role = 'user'): User
    {
        $pdo = ApplicationContainer::get()->get(\PDO::class);

        // unik e-post per anrop
        static $seq = 0;
        $seq++;
        $email = "test$seq@example.com";

        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password, avatar, role, created_at, updated_at, deleted_at)
            VALUES (:first_name, :last_name, :email, :password, :avatar, :role, NULL, NULL, NULL)
        ");
        $stmt->execute([
            ':first_name' => 'Test',
            ':last_name'  => 'User',
            ':email'      => $email,
            ':password'   => password_hash('secret123', PASSWORD_DEFAULT),
            ':avatar'     => '/images/graphics/avatar.png',
            ':role'       => $role,
        ]);
        $id = (int)$pdo->lastInsertId();

        $u = new User();
        $ref = new \ReflectionClass($u);
        $p = $ref->getProperty('attributes');
        $p->setAccessible(true);
        $p->setValue($u, [
            'id' => $id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'avatar' => '/images/graphics/avatar.png',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
        ]);

        return $u;
    }

    public function testHasAnyRoleMixedTypesAndEdges(): void
    {
        $user = $this->makeUser('user');

        // Blandning av enum och sträng, inkl. irrelevanta roller
        $this->assertTrue($user->hasAnyRole('guest', Role::User, 'editor'));
        $this->assertFalse($user->hasAnyRole('guest', 'editor', 'viewer'));

        // Edge: inga roller angivna
        $this->assertFalse($user->hasAnyRole());

        // Edge: null-liknande värden ska ignoreras (castas bort i PHP variadics)
        // Vi testar med endast irrelevanta strängar
        $this->assertFalse($user->hasAnyRole(''));
        $this->assertFalse($user->hasAnyRole(' ', '  '));
    }

    public function testSetRolePersistsWithSaveAndReload(): void
    {
        // Skapa user med role=user
        $user = $this->makeUser('user');

        // Verifiera initialt
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());

        // Ändra roll och spara (simulerar update i DB)
        $user->setRole('admin');
        $this->assertTrue($user->isAdmin(), 'Objektet i minnet ska spegla ändringen direkt.');
        $this->assertTrue($user->save(), 'Save ska lyckas och uppdatera DB.');

        // Ladda om från DB och verifiera att rollen är persisterad
        $reloaded = \App\Models\User::find($user->getAttribute('id'));
        $this->assertNotNull($reloaded, 'Reloaded user ska finnas.');
        $this->assertTrue($reloaded->isAdmin(), 'Reloaded user ska ha admin.');
        $this->assertFalse($reloaded->isUser());
    }

    public function testSetRoleWithEnumPersistsWithSaveAndReload(): void
    {
        // Skapa user med role=admin
        $user = $this->makeUser('admin');

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isUser());

        // Ändra med enum
        $user->setRole(\App\Enums\Role::User);
        $this->assertTrue($user->isUser(), 'Objektet i minnet ska spegla ändringen direkt.');
        $this->assertTrue($user->save(), 'Save ska lyckas och uppdatera DB.');

        // Ladda om och verifiera
        $reloaded = \App\Models\User::find($user->getAttribute('id'));
        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isUser());
        $this->assertFalse($reloaded->isAdmin());
    }

    public function testHasRoleWithString(): void
    {
        $user = $this->makeUser('admin');
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    public function testHasRoleWithEnum(): void
    {
        $user = $this->makeUser('user');
        $this->assertTrue($user->hasRole(Role::User));
        $this->assertFalse($user->hasRole(Role::Admin));
    }

    public function testIsAdminAndIsUserHelpers(): void
    {
        $admin = $this->makeUser('admin');
        $user  = $this->makeUser('user');

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isUser());

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    public function testHasAnyRole(): void
    {
        $user = $this->makeUser('user');
        $this->assertTrue($user->hasAnyRole('guest', 'user', 'editor'));
        $this->assertTrue($user->hasAnyRole(Role::User, 'editor'));
        $this->assertFalse($user->hasAnyRole('guest', 'editor'));
    }

    public function testHasAtLeastRoleHierarchy(): void
    {
        $admin = $this->makeUser('admin');
        $user  = $this->makeUser('user');

        $this->assertTrue($admin->hasAtLeast('user'));
        $this->assertTrue($admin->hasAtLeast(Role::Admin));
        $this->assertTrue($user->hasAtLeast('user'));
        $this->assertFalse($user->hasAtLeast('admin'));
    }

    public function testSetRoleWithString(): void
    {
        $user = $this->makeUser('user');
        $user->setRole('admin');

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isUser());
    }

    public function testSetRoleWithEnum(): void
    {
        $user = $this->makeUser('admin');
        $user->setRole(Role::User);

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }

    public function testRoleEnumAccessor(): void
    {
        $user = $this->makeUser('admin');
        $this->assertSame(Role::Admin, $user->roleEnum());

        $user2 = $this->makeUser('user');
        $this->assertSame(Role::User, $user2->roleEnum());
    }

    public function testSetRoleWithInvalidValueThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = $this->makeUser('user');
        $user->setRole('superadmin');
    }
}