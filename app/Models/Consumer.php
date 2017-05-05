<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Consumer
 * @package App\Models
 */
class Consumer extends Model
{
    public $timestamps = true;


    /**
     * Create a new consumer.
     * @param $name
     * @return Consumer
     */
    public static function create($name)
    {
        $consumer = new Consumer();
        $consumer->name = $name;

        $consumer->key = self::createUniqueKey();
        $consumer->secret = str_random(32);

        return $consumer;
    }

    /**
     * @return string
     */
    public static function createUniqueKey()
    {
        do {
            $key = str_random(16);
        } while (
            Consumer::whereKey($key)->count() > 0
        );
        return $key;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeKey($query)
    {
        return $query->where('key', '=', $query);
    }
}