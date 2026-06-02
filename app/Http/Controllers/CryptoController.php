<?php

namespace App\Http\Controllers;

use App\Services\CoinMarketCapService;

class CryptoController extends Controller
{
    public function listings(
        CoinMarketCapService $service
    ) {
        return response()->json(
            $service->listings()
        );
    }

    public function global(
        CoinMarketCapService $service
    ) {
        return response()->json(
            $service->global()
        );
    }
}
