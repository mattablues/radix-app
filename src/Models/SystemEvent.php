<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;
use Radix\Database\ORM\Relationships\BelongsTo;

/**
 * @property int $id
 * @property string $type
 * @property string $message
 * @property int|null $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property-read \App\Models\User|null $user
 */
class SystemEvent extends Model
{
    protected string $table = 'system_events';
    protected string $primaryKey = 'id';
    public bool $timestamps = true;

    /** @var array<int,string> */
    protected array $fillable = ['type', 'message', 'user_id'];

    /**
     * Relation till användaren som orsakade händelsen.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Hjälpmetod för att få rätt CSS-färg baserat på typ.
     */
    public function getTypeBadgeClass(): string
    {
        return match ($this->type) {
            'system'  => 'bg-blue-50 text-blue-600',
            'warning' => 'bg-amber-50 text-amber-600',
            'error'   => 'bg-red-50 text-red-600',
            default   => 'bg-emerald-50 text-emerald-600', // info
        };
    }

    /**
     * Skapa en logghändelse snabbt och enkelt.
     *
     * @param string $message Själva texten (t.ex. "Användare raderad")
     * @param string $type info, system, warning, eller error
     * @param int|null $userId ID på användaren som utförde handlingen
     */
    public static function log(string $message, string $type = 'info', ?int $userId = null): void
    {
        $event = new self();
        $event->fill([
            'type'    => $type,
            'message' => $message,
            'user_id' => $userId,
        ]);
        $event->save();
    }

    /**
     * Formatera tidstämpeln för vyn.
     */
    public function getCreatedAtAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i', (int) $timestamp);
    }
}
