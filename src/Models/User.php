<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Radix\Database\ORM\Model;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $avatar
 * @property string $role
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class User extends Model
{
    protected string $table = 'users'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id';     // Standard primärnyckel
    public bool $timestamps = true;         // Vill du använda timestamps?
    protected bool $softDeletes = true;
    protected array $fillable = ['id', 'first_name', 'last_name', 'email', 'avatar']; // Tillåtna att massfylla
    protected array $guarded = ['password', 'role', 'deleted_at'];
    //protected array $autoloadRelations = ['status'];

    public function setPasswordAttribute(string $value): void
    {
        // Kontrollera om värdet redan är hashat
        if (!password_get_info($value)['algo']) {
            // Endast hash lösenord som inte redan är hashat
            $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
        } else {
            // Om det redan är hashat, spara det direkt
            $this->attributes['password'] = $value;
        }
    }

    public function isPasswordValid(string $plainPassword): bool
    {
        return isset($this->attributes['password']) && password_verify($plainPassword, $this->attributes['password']);
    }

    // Hantera första bokstaven som stor bokstav för förnamn
    public function setFirstNameAttribute(string $value): void
    {
        $this->attributes['first_name'] = mb_ucfirst(mb_strtolower(trim($value)));
    }

    // Hantera första bokstaven som stor bokstav för efternamn
    public function setLastNameAttribute(string $value): void
    {
        $this->attributes['last_name'] = mb_ucfirst(mb_strtolower(trim($value)));
    }

    // Accessor för att hämta förnamnet i rätt format (om ytterligare hantering behövs vid hämtning)
    public function getFirstNameAttribute(?string $value): ?string
    {
        return $value ? mb_ucfirst(mb_strtolower($value)) : null;
    }


        // Accessor för att hämta efternamnet i rätt format
    public function getLastNameAttribute(?string $value): ?string
    {
        return $value ? mb_ucfirst(mb_strtolower($value)) : null;
    }


    // Hantera e-postlagring i små bokstäver
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    // Accessor för att hämta e-post (texten ska redan vara korrekt formaterad)
    public function getEmailAttribute(?string $value): ?string
    {
        return $value ?? null; // Returnera null om det inte finns
    }

    /**
     * Relation till Status.
     *
     * @return \Radix\Database\ORM\Relationships\HasOne<\App\Models\Status>
     */
    public function status(): \Radix\Database\ORM\Relationships\HasOne
    {
        return $this->hasOne(Status::class, 'user_id', 'id');
    }

    /**
     * Relation till Status.
     *
     * @return \Radix\Database\ORM\Relationships\HasOne<\App\Models\Token>
     */
    public function token():  \Radix\Database\ORM\Relationships\HasOne
    {
        return $this->hasOne(Token::class, 'user_id', 'id');
    }

    public function setOnline(): self
    {
        // Försök använda relationen om den är laddad
        $status = $this->status;

        if (!$status instanceof Status) {
            // Om relationen inte är laddad, ladda den från databasen
            $status = $this->status()->first();
        }

        if ($status) {
            $status->goOnline(); // Markera som online
        } else {
            // Om status saknas, logga eller hantera detta beroende på applikationens behov.
            throw new \RuntimeException('Status saknas för användaren och går inte att sätta Online.');
        }

        return $this;
    }

    public function setOffline(): self
    {
        // Försök använda relationen om den är laddad
        $status = $this->status;

        if (!$status instanceof Status) {
            // Om relationen inte är laddad, ladda den från databasen
            $status = $this->status()->first();
        }

        if ($status) {
            $status->goOffline(); // Markera som offline
        } else {
            // Om status saknas, logga eller hantera detta beroende på applikationens behov.
            throw new \RuntimeException('Status saknas för användaren och går inte att sätta Offline.');
        }

        return $this;
    }

    public function isOnline(): bool
    {
        // Försök använda relationen direkt
        $status = $this->status;

        if (!$status instanceof Status) {
            // Om det inte är laddat, ladda det från databasen
            $status = $this->status()->first();
        }

        // Returnera om användaren är online baserat på Status-objektet
        return $status?->isOnline() ?? false;
    }

    // Rollen som enum
    public function roleEnum(): ?Role
    {
        // Läs direkt från attribut om det finns där (t.ex. efter setRole i minnet)
        if (isset($this->attributes['role']) && is_string($this->attributes['role'])) {
            return Role::tryFrom($this->attributes['role']);
        }

        try {
            $value = $this->fetchGuardedAttribute('role');
        } catch (\InvalidArgumentException) {
            return null;
        }
        return is_string($value) ? Role::tryFrom($value) : null;
    }

    // Sätt roll via enum eller sträng
    public function setRole(Role|string $role): void
    {
        $enum = $role instanceof Role ? $role : Role::tryFromName($role);
        if (!$enum) {
            throw new \InvalidArgumentException('Ogiltig roll: ' . $role);
        }
        $this->attributes['role'] = $enum->value;
    }

    // Exakt match
    public function hasRole(Role|string $role): bool
    {
        $target = $role instanceof Role ? $role : Role::tryFromName($role);
        $current = $this->roleEnum();
        return $target !== null && $current?->value === $target->value;
    }

    // Miniminivå (om du vill ha hierarki; t.ex. admin >= user)
    public function hasAtLeast(Role|string $role): bool
    {
        $target = $role instanceof Role ? $role : Role::tryFromName($role);
        $current = $this->roleEnum();
        return $target !== null && $current !== null && $current->level() >= $target->level();
    }

    // Någon av flera roller
    public function hasAnyRole(Role|string ...$roles): bool
    {
        foreach ($roles as $r) {
            if ($this->hasRole($r)) {
                return true;
            }
        }
        return false;
    }

    // Syntaktiskt socker
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    public function isModerator(): bool
    {
        return $this->hasRole(Role::Moderator);
    }

    public function isEditor(): bool
    {
        return $this->hasRole(Role::Editor);
    }

    public function isSupport(): bool
    {
        return $this->hasRole(Role::Support);
    }

    public function isUser(): bool
    {
        return $this->hasRole(Role::User);
    }
}