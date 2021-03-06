<?php

namespace App\Models;

use App\Helpers\StatisticsHelper;
use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        $consumer->secret = Str::random(32);

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
            $key = Str::random(16);
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function processors()
    {
        return $this->hasMany(Processor::class)
            ->with('triggers')
            ->with('config')
        ;
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
            'uniquePercentage' => StatisticsHelper::formatPercentage($uploadedUnique / max(1, $uploaded)),
            'uploadedSize' => StatisticsHelper::formatBytes($size),
            'uploadedUniqueSize' => StatisticsHelper::formatBytes($uniqueSize),
            'uniqueSizePercentage' => StatisticsHelper::formatPercentage($uniqueSize / max(1, $size)),
            'savings' => StatisticsHelper::formatPercentage(1 - ($uniqueSize / max(1, $size))),
            'spaceSaved' => StatisticsHelper::formatBytes($size - $uniqueSize)
        ];
    }
}
