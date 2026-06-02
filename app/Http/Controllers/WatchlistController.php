<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\PriceHistory;
use App\Services\CoinMarketCapService;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    protected $cmc;

    public function __construct(CoinMarketCapService $cmc)
    {
        $this->cmc = $cmc;
    }

    public function index()
    {
        $cryptos = Cryptocurrency::with(['histories' => function ($q) {
            $q->latest('captured_at')->limit(1);
        }])->get();

        $ids  = $cryptos->pluck('cmc_id')->toArray();
        $live = $this->cmc->quotes($ids);

        $resultado = $cryptos->map(function ($c) use ($live) {
            $item  = $live[$c->cmc_id] ?? null;
            $quote = $item['quote']['USD'] ?? null;
            $ultimo = $c->histories->first();

            return [
                'id'                 => $c->id,
                'cmc_id'             => $c->cmc_id,
                'name'               => $c->name,
                'symbol'             => $c->symbol,
                'price'              => $quote['price'] ?? ($ultimo->price ?? null),
                'percent_change_24h' => $quote['percent_change_24h'] ?? ($ultimo->percent_change_24h ?? null),
                'volume_24h'         => $quote['volume_24h'] ?? ($ultimo->volume_24h ?? null),
                'market_cap'         => $quote['market_cap'] ?? ($ultimo->market_cap ?? null),
            ];
        });

        return response()->json($resultado);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cmc_id' => 'required|integer',
            'name'   => 'required|string',
            'symbol' => 'required|string',
        ]);

        $crypto = Cryptocurrency::firstOrCreate(
            ['cmc_id' => $request->cmc_id],
            ['name' => $request->name, 'symbol' => $request->symbol]
        );

        // guardar precio inicial
        $data  = $this->cmc->quotes([$crypto->cmc_id]);
        $item  = $data[$crypto->cmc_id] ?? null;
        $quote = $item['quote']['USD'] ?? null;

        if ($quote) {
            PriceHistory::create([
                'cryptocurrency_id'  => $crypto->id,
                'price'              => $quote['price'],
                'volume_24h'         => $quote['volume_24h'],
                'percent_change_24h' => $quote['percent_change_24h'],
                'market_cap'         => $quote['market_cap'],
                'captured_at'        => now(),
            ]);
        }

        return response()->json(['mensaje' => 'Agregado', 'crypto' => $crypto], 201);
    }

    public function destroy($id)
    {
        $crypto = Cryptocurrency::findOrFail($id);
        $crypto->delete();

        return response()->json(['mensaje' => 'Eliminado']);
    }

    public function history($id, Request $request)
    {
        $crypto = Cryptocurrency::findOrFail($id);
        $rango  = $request->query('range', '24h');

        if ($rango === '7d') {
            $desde = now()->subDays(7);
        } elseif ($rango === '30d') {
            $desde = now()->subDays(30);
        } else {
            $desde = now()->subHours(24);
        }

        $historial = PriceHistory::where('cryptocurrency_id', $id)
            ->where('captured_at', '>=', $desde)
            ->orderBy('captured_at')
            ->get(['captured_at', 'price', 'volume_24h', 'percent_change_24h', 'market_cap']);

        return response()->json([
            'crypto'   => ['id' => $crypto->id, 'name' => $crypto->name, 'symbol' => $crypto->symbol],
            'history'  => $historial,
        ]);
    }

    public function snapshot()
    {
        $guardados = $this->cmc->snapshot();

        return response()->json(['mensaje' => "Snapshot guardado: $guardados registros"]);
    }

    public function search(Request $request)
    {
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $resultados = $this->cmc->search($query);

        return response()->json(array_values($resultados));
    }
}
