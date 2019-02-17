<?php

namespace App\Models;

use CatLab\Assets\Laravel\Helpers\AssetFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PublicCaching
 * @package App\Models
 */
class CachedProxyFile extends Model
{
    protected $table = 'consumer_cached_proxy_file';

    public static function boot()
    {
        parent::boot();

        self::creating(function($model){
            $model->public_url_hash = md5($model->public_url);
            if (!isset($model->expires_at)) {
                $model->expires_at = (new \DateTime())->add(new \DateInterval('P7D'));
            }
        });
    }

    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at'
    ];

    /**
     * @param $consumer
     * @param $url
     * @return CachedProxyFile|null
     */
    public static function getFromUrl($consumer, $url)
    {
        $urlHash = md5($url);

        $cachingObjects = self::query()
            ->where('consumer_id', '=', $consumer->id)
            ->where('public_url_hash', '=', $urlHash)
            ->get();

        foreach ($cachingObjects as $cachingObject) {
            if ($cachingObject->public_url === $url) {
                return $cachingObject;
            }
        }

        return null;
    }

    /**
     * @param Asset $asset
     * @param Consumer $consumer
     * @return CachedProxyFile
     */
    public static function createFromAsset(Asset $asset, Consumer $consumer)
    {
        $cachedProxyFile = new self();
        $cachedProxyFile->consumer()->associate($consumer);
        $cachedProxyFile->asset()->associate($asset);

        return $cachedProxyFile;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(AssetFactory::getAssetClassName());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }
}