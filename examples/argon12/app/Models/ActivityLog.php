<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'name',
        'role',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function record(User $actor, string $note): void
    {
        static::query()->create([
            'user_id' => $actor->id,
            'name' => $actor->name,
            'role' => $actor->role,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
