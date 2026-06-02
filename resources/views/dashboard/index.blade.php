@extends('layouts.app')

@section('content')

<header>
    <h1>₿ CryptoInvestment</h1>
    <div class="global-stats">
        <div><span>Capitalización</span><b id="gMarketCap">—</b></div>
        <div><span>Volumen 24h</span><b id="gVolume">—</b></div>
        <div><span>Dom. BTC</span><b id="gBtcDom">—</b></div>
        <div><span>Monedas activas</span><b id="gActive">—</b></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <span class="live-badge" id="badge">EN VIVO</span>
        <button class="btn" id="btnSnapshot">Guardar datos</button>
    </div>
</header>

<main>

    <div class="search-box">
        <input type="text" id="buscar" placeholder="Buscar criptomoneda...">
        <div class="search-results" id="resultados"></div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Mi Portafolio <small id="total"></small></h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Moneda</th>
                    <th>Precio</th>
                    <th>Cambio 24h</th>
                    <th class="col-vol">Volumen 24h</th>
                    <th class="col-mcap">Cap. Mercado</th>
                    <th>Tendencia</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="tabla">
                <tr><td colspan="8" class="empty">Busca y agrega criptomonedas para comenzar.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card" id="panelGrafico" style="display:none">
        <div class="card-header">
            <div>
                <h2 id="tituloGrafico" style="margin:0">Historial de precio</h2>
                <small id="subtituloGrafico" style="color:#aaa"></small>
            </div>
            <div class="range-tabs">
                <button class="range-tab active" data-r="24h">24 horas</button>
                <button class="range-tab" data-r="7d">7 días</button>
                <button class="range-tab" data-r="30d">30 días</button>
            </div>
        </div>
        <div class="chart-area">
            <canvas id="grafico"></canvas>
        </div>
    </div>

</main>

<div class="toast" id="toast"></div>

@endsection
