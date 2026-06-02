let lista = [], seleccionado = null, rango = '24h', chart = null, seg = 60;

const COLORES = {BTC:'#f59e0b',ETH:'#627eea',BNB:'#f3ba2f',SOL:'#9945ff',XRP:'#00aae4',DOGE:'#c2a633'};
const color = s => COLORES[s] || `hsl(${s.charCodeAt(0)*47%360},60%,55%)`;
const csrf  = () => document.querySelector('meta[name="csrf-token"]').content;

function formatearDinero(n) {
    if (!n) return '—';
    if (n >= 1e12) return '$'+(n/1e12).toFixed(2)+'T';
    if (n >= 1e9)  return '$'+(n/1e9).toFixed(2)+'B';
    if (n >= 1e6)  return '$'+(n/1e6).toFixed(2)+'M';
    return '$'+Number(n >= 1000 ? n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}) : n.toFixed(n>=1?4:6));
}

const api = {
    get:  url => fetch('/api'+url).then(r=>r.json()),
    post: (url,d) => fetch('/api'+url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify(d)}).then(r=>r.json()),
    del:  url => fetch('/api'+url,{method:'DELETE',headers:{'X-CSRF-TOKEN':csrf()}}).then(r=>r.json())
};

function mostrarAviso(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.className = 'toast show';
    clearTimeout(t._t); t._t = setTimeout(()=>t.className='toast', 3000);
}

async function cargarMercado() {
    const d = await api.get('/crypto/global');
    const q = d?.data?.quote?.USD||{}, m = d?.data||{};
    document.getElementById('gMarketCap').textContent = formatearDinero(q.total_market_cap);
    document.getElementById('gVolume').textContent    = formatearDinero(q.total_volume_24h);
    document.getElementById('gBtcDom').textContent    = m.btc_dominance ? m.btc_dominance.toFixed(1)+'%' : '—';
    document.getElementById('gActive').textContent    = m.active_cryptocurrencies?.toLocaleString()||'—';
}

async function cargarPortafolio() {
    lista = await api.get('/watchlist');
    const tbody = document.getElementById('tabla');
    document.getElementById('total').textContent = lista.length ? '('+lista.length+')' : '';

    if (!lista.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">Busca y agrega criptomonedas para comenzar.</td></tr>';
        return;
    }

    tbody.innerHTML = lista.map((c,i) => {
        const chg = c.percent_change_24h, cls = chg >= 0 ? 'pos':'neg';
        return `<tr data-id="${c.id}" class="${seleccionado===c.id?'activo':''}" onclick="verDetalle(${c.id})">
            <td>${i+1}</td>
            <td><span class="coin-icon" style="background:${color(c.symbol)}">${c.symbol.slice(0,3)}</span>
                <b>${c.name}</b> <small style="color:#777">${c.symbol}</small></td>
            <td>${formatearDinero(c.price)}</td>
            <td class="${cls}">${chg!=null?(chg>=0?'+':'')+Number(chg).toFixed(2)+'%':'—'}</td>
            <td class="col-vol">${formatearDinero(c.volume_24h)}</td>
            <td class="col-mcap">${formatearDinero(c.market_cap)}</td>
            <td><canvas id="sp${c.id}" width="80" height="28"></canvas></td>
            <td><button class="btn-remove" onclick="event.stopPropagation();eliminar(${c.id},'${c.name}')">✕</button></td>
        </tr>`;
    }).join('');

    lista.forEach(c => {
        const cv = document.getElementById('sp'+c.id); if (!cv) return;
        const ctx = cv.getContext('2d');
        const pct = c.percent_change_24h||0, base = (c.price||1)/(1+pct/100);
        const pts = Array.from({length:11},(_,i)=>base+(c.price-base)*(i/10)+(Math.random()-.5)*.01*base);
        const min = Math.min(...pts), rng = Math.max(...pts)-min||1;
        ctx.beginPath();
        pts.forEach((v,i)=>{ const x=i/10*80, y=28-((v-min)/rng)*24-2; i?ctx.lineTo(x,y):ctx.moveTo(x,y); });
        ctx.strokeStyle=color(c.symbol); ctx.lineWidth=1.5; ctx.stroke();
    });

    if (seleccionado && lista.find(c=>c.id===seleccionado)) mostrarGrafico(seleccionado);
    else if (lista.length) verDetalle(lista[0].id);
}

window.verDetalle = id => {
    seleccionado = id;
    document.querySelectorAll('#tabla tr[data-id]').forEach(tr=>tr.classList.toggle('activo',+tr.dataset.id===id));
    mostrarGrafico(id);
};

window.eliminar = async (id, nombre) => {
    await api.del('/watchlist/'+id);
    mostrarAviso(nombre+' eliminado');
    if (seleccionado===id) { seleccionado=null; document.getElementById('panelGrafico').style.display='none'; }
    cargarPortafolio();
};

window.agregar = async (cmcId, nombre, simbolo) => {
    if (lista.find(c=>c.cmc_id===cmcId)) { mostrarAviso(nombre+' ya está en el portafolio'); return; }
    await api.post('/watchlist',{cmc_id:cmcId,name:nombre,symbol:simbolo});
    mostrarAviso(nombre+' agregado ✓');
    document.getElementById('buscar').value='';
    document.getElementById('resultados').classList.remove('open');
    cargarPortafolio();
};

async function mostrarGrafico(id) {
    const c = lista.find(x=>x.id===id); if (!c) return;
    document.getElementById('panelGrafico').style.display='';
    document.getElementById('tituloGrafico').textContent    = c.name+' ('+c.symbol+')';
    document.getElementById('subtituloGrafico').textContent = 'Precio actual: '+formatearDinero(c.price);
    if (chart) { chart.destroy(); chart=null; }

    const {history} = await api.get('/watchlist/'+id+'/history?range='+rango);
    let labels=[], precios=[];

    if (history?.length > 1) {
        labels  = history.map(h=>new Date(h.captured_at).toLocaleDateString('es-CO'));
        precios = history.map(h=>+h.price);
    } else {
        const span = rango==='7d'?604800000:rango==='30d'?2592000000:86400000;
        const pct=(c.percent_change_24h||0)/100, base=(c.price||1)/(1+pct);
        for (let i=0;i<=30;i++) {
            labels.push(new Date(Date.now()-span+span*i/30).toLocaleDateString('es-CO'));
            precios.push(+(base+(c.price-base)*(i/30)+(Math.random()-.5)*.015*base).toFixed(4));
        }
    }

    chart = new Chart(document.getElementById('grafico'),{
        type:'line',
        data:{labels,datasets:[{data:precios,borderColor:color(c.symbol),backgroundColor:color(c.symbol)+'33',borderWidth:2,fill:true,tension:0.3,pointRadius:0}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
            scales:{x:{ticks:{color:'#777',maxTicksLimit:6}},y:{ticks:{color:'#777',callback:v=>formatearDinero(v)}}}}
    });
}

// Búsqueda
let timerBusqueda;
document.getElementById('buscar').addEventListener('input', function() {
    clearTimeout(timerBusqueda);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('resultados').classList.remove('open'); return; }
    document.getElementById('resultados').innerHTML = '<div style="padding:10px;color:#777;font-size:12px">Buscando...</div>';
    document.getElementById('resultados').classList.add('open');
    timerBusqueda = setTimeout(async () => {
        const data = await api.get('/search?q='+encodeURIComponent(q));
        document.getElementById('resultados').innerHTML = data.length
            ? data.map(c=>`<div class="search-item"><span class="sym">${c.symbol}</span><span style="flex:1">${c.name}</span>
                <button class="btn-add" onclick="agregar(${c.id},'${c.name.replace(/'/g,"\\'")}','${c.symbol}')">Agregar</button></div>`).join('')
            : '<div style="padding:10px;color:#777;font-size:12px">Sin resultados</div>';
    }, 400);
});
document.addEventListener('click', e=>{ if(!e.target.closest('.search-box')) document.getElementById('resultados').classList.remove('open'); });

// Tabs rango
document.querySelectorAll('.range-tab').forEach(btn=>btn.addEventListener('click',function(){
    document.querySelectorAll('.range-tab').forEach(b=>b.classList.remove('active'));
    this.classList.add('active'); rango=this.dataset.r;
    if (seleccionado) mostrarGrafico(seleccionado);
}));

// Snapshot
document.getElementById('btnSnapshot').addEventListener('click', async function() {
    this.textContent = 'Guardando...';
    const r = await api.post('/watchlist/snapshot');
    mostrarAviso(r.mensaje||'Datos guardados');
    this.textContent = 'Guardar datos';
    if (seleccionado) mostrarGrafico(seleccionado);
});

// Auto-actualización cada 60s
setInterval(async ()=>{
    document.getElementById('badge').textContent = 'EN VIVO  '+(--seg)+'s';
    if (seg<=0) { seg=60; await cargarMercado(); await cargarPortafolio(); }
}, 1000);

cargarMercado();
cargarPortafolio();
