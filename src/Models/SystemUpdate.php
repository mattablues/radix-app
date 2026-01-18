<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;

/**
 * @property int $id
 * @property string $version
 * @property string $title
 * @property string $description
 * @property string $released_at
 * @property bool $is_major
 * @property string $created_at
 * @property string $updated_at
 */
class SystemUpdate extends Model
{
    protected string $table = 'system_updates';
    protected string $primaryKey = 'id';
    public bool $timestamps = true;

    /** @var array<int,string> */
    protected array $fillable = ['version', 'title', 'description', 'released_at', 'is_major'];

    /**
     * Säkerställ att is_major alltid behandlas som en boolean.
     */
    public function getIsMajorAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    /**
     * Accessor för att formatera releasedatumet snyggt (t.ex. "Januari 2026").
     */
    public function getReleasedAtFormattedAttribute(): string
    {
        $releasedAt = $this->attributes['released_at'] ?? 'now';
        $timestamp = strtotime(is_string($releasedAt) ? $releasedAt : 'now');

        // Om strtotime misslyckas, fall tillbaka till nuvarande tid
        $date = $timestamp ?: time();

        // Enkel svensk översättning av månader
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Mars', 4 => 'April',
            5 => 'Maj', 6 => 'Juni', 7 => 'Juli', 8 => 'Augusti',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'December',
        ];

        return $months[(int) date('n', $date)] . ' ' . date('Y', $date);
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

        return $timestamp !== false ? date('Y-m-d H:i', $timestamp) : null;
    }
}
