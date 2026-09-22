// Test como Oscar (id=14, role=comun)
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
    // 1. Get login page
    const r2 = await req({ hostname:'127.0.0.1', port:8000, path:'/login', method:'GET' });
    const m = r2.body.match(/name="_token"[^>]+value="([^"]+)"/);
    const token = m ? m[1] : '';

    // 2. POST login (como osqui)
    const body = 'nick=osqui&password=osqui123&_token=' + token;
    const r3 = await req({ hostname:'127.0.0.1', port:8000, path:'/login', method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','Content-Length':Buffer.byteLength(body)}
    }, body);
    console.log('login POST:', r3.status, 'location:', r3.headers.location);

    // 3. Get /api/rutinas
    const r4 = await req({ hostname:'127.0.0.1', port:8000, path:'/api/rutinas', method:'GET',
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
    console.log('rutinas:', r4.status, 'bytes:', r4.body.length);
    try {
        const data = JSON.parse(r4.body);
        console.log('count:', data.length);
        const niveles = {};
        data.forEach(r => { niveles[r.nivel] = (niveles[r.nivel] || 0) + 1; });
        console.log('Por nivel:', JSON.stringify(niveles));
    } catch (e) {
        console.log('not JSON:', r4.body.substring(0, 200));
    }
})();
