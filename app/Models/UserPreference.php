<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'value' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a user preference by key.
     *
     * @return mixed|null
     */
    public static function getPreference(int $userId, string $key)
    {
        $preference = static::where('user_id', $userId)
            ->where('key', $key)
            ->first();

        return $preference ? $preference->value : null;
    }

    /**
     * Set a user preference.
     *
     * @param  mixed  $value
     * @return UserPreference
     */
    public static function setPreference(int $userId, string $key, $value)
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
