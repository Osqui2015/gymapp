// Simula exactamente el flujo del frontend
const http = require('http');

let cookieJar = {};
function addCookies(headers) {
    const sc = headers['set-cookie'] || [];
    sc.forEach(c => {
        const [pair] = c.split(';');
        const [k, v] = pair.split('=');
        cookieJar[k.trim()] = v;
    });
}
function cookieHeader() {
    return Object.entries(cookieJar).map(([k,v]) => k+'='+v).join('; ');
}
function req(opts, body) {
    return new Promise((resolve, reject) => {
        opts.headers = opts.headers || {};
        opts.headers['Cookie'] = cookieHeader();
        const r = http.request(opts, res => {
            addCookies(res.headers);
            let d = '';
            res.on('data', c => d += c);
            res.on('end', () => resolve({ status: res.statusCode, headers: res.headers, body: d }));
        });
        r.on('error', reject);
        if (body) r.write(body);
        r.end();
    });
}

(async () => {
    // 1. Get login page to grab CSRF token
    const r2 = await req({ hostname:'127.0.0.1', port:8000, path:'/login', method:'GET' });
    const m = r2.body.match(/name="_token"[^>]+value="([^"]+)"/);
    const token = m ? m[1] : '';
    console.log('login page:', r2.status, 'token-len:', token.length);

    // 2. POST login
    const body = 'nick=admin&password=password&_token=' + token;
    const r3 = await req({ hostname:'127.0.0.1', port:8000, path:'/login', method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','Content-Length':Buffer.byteLength(body)}
    }, body);
    console.log('login POST:', r3.status, 'cookies-after:', Object.keys(cookieJar).length);

    // 3. Get /api/rutinas (exact same call as fetchRutinas)
    const r4 = await req({ hostname:'127.0.0.1', port:8000, path:'/api/rutinas', method:'GET',
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
    console.log('rutinas:', r4.status, 'bytes:', r4.body.length);
    try {
        const data = JSON.parse(r4.body);
        console.log('count:', data.length);
        const niveles = {};
        data.forEach(r => { niveles[r.nivel] = (niveles[r.nivel] || 0) + 1; });
        console.log('Por nivel:', JSON.stringify(niveles));

        // Verificar data shape para RutinaAcordeon
        const first = data[0];
        console.log('First rutina keys:', Object.keys(first).join(','));
        console.log('First rutina modalidad/dia:', first.modalidad, '/', first.dia);
        console.log('First rutina nivel length:', first.nivel.length, 'value:', JSON.stringify(first.nivel));

        // Simulate agrupar
        const agrupadas = {};
        data.forEach(r => {
            if (!r.nivel) {
                console.log('  WARNING: rutina sin nivel:', r);
                return;
            }
            if (!agrupadas[r.nivel]) agrupadas[r.nivel] = { modalidades: {} };
            if (!agrupadas[r.nivel].modalidades[r.modalidad]) {
                agrupadas[r.nivel].modalidades[r.modalidad] = { nombre: r.modalidad, dias: {} };
            }
            if (!agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia]) {
                agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia] = { nombre: r.dia, ejercicios: [] };
            }
            agrupadas[r.nivel].modalidades[r.modalidad].dias[r.dia].ejercicios.push(r);
        });
        console.log('Agrupadas keys:', Object.keys(agrupadas));
        console.log('Principiante modalidades:', Object.keys(agrupadas['Principiante'].modalidades));
        console.log('Principiante "2 Días" dias:', Object.keys(agrupadas['Principiante'].modalidades['2 Días'].dias));

        // Test defaultRutinas logic
        const result = {};
        Object.keys(agrupadas).forEach(nivel => {
            if (nivel !== 'Personalizada') result[nivel] = agrupadas[nivel];
        });
        console.log('defaultRutinas keys:', Object.keys(result));
        console.log('  Principiante truthy:', !!result['Principiante']);
        console.log('  Principiante.modalidades truthy:', !!result['Principiante']?.modalidades);

        // Test nivelesOrden membership
        const nivelesOrden = ['Principiante', 'Intermedio', 'Avanzado'];
        nivelesOrden.forEach(n => {
            console.log(`  ${n}: in result =`, !!result[n], ', modalidades count:', Object.keys(result[n]?.modalidades || {}).length);
        });
    } catch (e) {
        console.log('not JSON:', r4.body.substring(0,300));
    }
})();
