<?php

namespace App\Models;

use CatLab\Assets\Laravel\Models\Asset;
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
     * @param $key
     * @return Consumer|null
     */
    public static function findFromKey($key)
    {
        return self::where('key', $key)->first();
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function consumerAssets()
    {
        return $this->hasMany(ConsumerAsset::class);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeKey($query)
    {
        return $query->where('key', '=', $query);
    }

    /**
     * @return array
     */
    public function getStatistics()
    {
        $uploaded = $this->consumerAssets()->count();
        $uploadedUnique = $this->consumerAssets()->distinct('asset_id')->count('asset_id');

        $size = $this
            ->consumerAssets()
            ->join('assets', 'consumer_assets.asset_id', '=', 'assets.id')
            ->sum('assets.size');

        $uniqueSize = Asset::whereIn('id', function($builder) {
                return $builder->from('consumer_assets')
                    ->distinct('asset_id')
                    ->where('consumer_id', '=', $this->id)
                    ->select('asset_id');
            })
            ->sum('size');

        return [
            'uploaded' => $uploaded,
            'uploadedUnique' => $uploadedUnique,
            'uniquePercentage' => $this->formatPercentage($uploadedUnique / $uploaded),
            'uploadedSize' => $this->formatBytes($size),
            'uploadedUniqueSize' => $this->formatBytes($uniqueSize),
            'uniqueSizePercentage' => $this->formatPercentage($uniqueSize / $size),
            'savings' => $this->formatPercentage(1 - ($uniqueSize / $size))
        ];
    }

    /**
     * @param $size
     * @param int $precision
     * @return string
     */
    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    /**
     * @param $float
     * @return string
     */
    private function formatPercentage($float)
    {
        return round(($float) * 100) . '%';
    }
}