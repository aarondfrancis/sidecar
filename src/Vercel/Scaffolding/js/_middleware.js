// https://jameshfisher.com/2017/10/30/web-cryptography-api-hello-world/
async function sha1(str) {
    const buf = await crypto.subtle.digest('SHA-1', new TextEncoder('utf-8').encode(str));
    return Array.prototype.map.call(new Uint8Array(buf), x => (('00' + x.toString(16)).slice(-2))).join('');
}

function token(headers) {
    return (headers['authorization'] || '').split(' ')[1] || '';
}

function passesSimpleAuth(headers) {
    return token(headers) === 'sc_replace__middleware_token';
}

async function passesDigestAuth(request) {
    const headers = request.headers;

    let auth = token(headers);

    // Get it from the path if it's not in the header.
    if (!auth) {
        auth = request.url.split('/tok-')[1] || '';
        auth = auth.split('/')[0] || ''
    }

    const digest = auth.split('-')[0];
    const timestamp = auth.split('-')[1];

    if (!digest || !timestamp) {
        return false;
    }

    if (parseInt(timestamp) < (Math.floor(Date.now() / 1000))) {
        return false;
    }

    return digest === await sha1('sc_replace__middleware_token' + timestamp);
}

export default async function (request) {
    if (!passesSimpleAuth(request.headers) && !await passesDigestAuth(request)) {
        return new Response('Unauthorized', {
            status: 401
        })
    }

    // return new Response(null, {
    //     headers: {
    //         'x-middleware-rewrite': 'https://google.com/',
    //     },
    // })
    // let path = request.url.split('.vercel.app/')[1];
}
