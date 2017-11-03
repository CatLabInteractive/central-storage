<?php

namespace App\Models;

use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Combination
 * @package App\Models
 */
class Combination extends Model
{
    protected $fillable = [
        'hash'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}