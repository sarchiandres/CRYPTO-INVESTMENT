<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $fillable = [
        'cryptocurrency_id',
        'price',
        'volume_24h',
        'percent_change_24h',
        'market_cap',
        'captured_at'
    ];

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }
}
