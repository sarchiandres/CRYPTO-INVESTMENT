<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cryptocurrency extends Model
{
    protected $fillable = [
        'cmc_id',
        'name',
        'symbol'
    ];

    public function histories()
    {
        return $this->hasMany(PriceHistory::class);
    }

}
    