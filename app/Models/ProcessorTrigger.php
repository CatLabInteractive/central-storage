<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 *
 */
class ProcessorTrigger extends Model
{
    protected $fillable = [
        'mimetype'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processor()
    {
        return $this->belongsTo(Processor::class);
    }

    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builde $query
     */
    public function addToQuery($query)
    {
        $query->orWhere('assets.mimetype', 'LIKE', $this->getSqlMimetype());
    }

    /**
     * @return string
     */
    protected function getSqlMimetype()
    {
        return str_replace('*', '%', $this->mimetype);
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function check(Asset $asset)
    {
        $mimetype = $asset->mimetype;

        $mimetypeReg = preg_quote($this->mimetype, '/');
        $regex = '/^' . str_replace('\*', '(.*)', $mimetypeReg) . '$/i';

        return preg_match($regex, $mimetype);
    }
}