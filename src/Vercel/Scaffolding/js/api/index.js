import {sc_replace__handler_function} from '../package/sc_replace__handler_file';

let logs = [];

shim('log');
shim('info');
shim('warn');

export default async function (request, response) {
    if (!request.url.includes('/execute')) {
        return response.status(404).send();
    }

    logs = [];
    let result = execute(request);

    // This is the signal from the execution side that
    // they are not interested in the response, and to
    // return as quickly as possible.
    if (request.query.scevent == 1) {
        // Don't wait, send an empty "received" response.
        return response.status(202).send();
    }

    result = await result;

    // If the developer indicates that the raw response should
    // be returned, we'll do that. This helps in the cases
    // where the handler generates an image and is being
    // invoked from the frontend.
    if (request.query.scraw == 1) {
        return response.status(200).send(result);
    }

    response.setHeader('Cache-Control', 's-maxage=60');

    response.status(200).json({
        logs: logs,
        result: JSON.stringify(result)
    });
}

function execute(request) {
    const payload = request.body;
    // const payload = (request.method === 'POST') ? request.body : request.query;

    try {
        return sc_replace__handler_function(payload);
    } catch (e) {
        return e;
    }
}

function shim(method) {
    let original = console[method];

    console[method] = function (...args) {
        original(...args);

        logs.push({
            time: (Math.floor(Date.now() / 1000)),
            level: method,
            body: args.map(arg => (typeof arg === 'string') ? arg : JSON.stringify(arg))
        })
    }
}