<?php

namespace App\Services;

use App\Models\Cryptocurrency;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\Http;

class CoinMarketCapService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('coinmarketcap.api_key');
    }

    public function listings()
    {
        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $this->apiKey
        ])->get(config('coinmarketcap.base_url') . '/cryptocurrency/listings/latest', [
            'limit' => 100,
            'sort'  => 'market_cap'
        ]);

        return $response->json();
    }

    public function global()
    {
        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $this->apiKey
        ])->get(config('coinmarketcap.base_url') . '/global-metrics/quotes/latest');

        return $response->json();
    }

    public function quotes(array $ids)
    {
        if (empty($ids)) return [];

        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $this->apiKey
        ])->get(config('coinmarketcap.base_url') . '/cryptocurrency/quotes/latest', [
            'id' => implode(',', $ids)
        ]);

        return $response->json()['data'] ?? [];
    }

    public function search(string $query)
    {
        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => $this->apiKey
        ])->get(config('coinmarketcap.base_url') . '/cryptocurrency/map', [
            'listing_status' => 'active',
            'keyword'        => $query
        ]);

        return $response->json()['data'] ?? [];
    }

    public function snapshot()
    {
        $cryptos = Cryptocurrency::all();

        if ($cryptos->isEmpty()) return 0;

        $ids  = $cryptos->pluck('cmc_id')->toArray();
        $data = $this->quotes($ids);
        $guardados = 0;

        foreach ($cryptos as $crypto) {
            $item  = $data[$crypto->cmc_id] ?? null;
            $quote = $item['quote']['USD'] ?? null;

            if (!$quote) continue;

            PriceHistory::create([
                'cryptocurrency_id'  => $crypto->id,
                'price'              => $quote['price'] ?? 0,
                'volume_24h'         => $quote['volume_24h'] ?? 0,
                'percent_change_24h' => $quote['percent_change_24h'] ?? 0,
                'market_cap'         => $quote['market_cap'] ?? 0,
                'captured_at'        => now(),
            ]);

            $guardados++;
        }

        return $guardados;
    }
}
