<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class TrustRenderProxy extends TrustProxies
{
    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_PORT;

    protected function proxies()
    {
        // Read after Laravel has loaded configuration, including cached config.
        return config('app.trust_render_proxy') ? '*' : parent::proxies();
    }
}
