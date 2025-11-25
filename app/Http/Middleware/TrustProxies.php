<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Confiamos en cualquier proxy aguas arriba para que Laravel detecte
     * correctamente el esquema (HTTP/HTTPS) y las IPs originales.
     */
    protected $proxies = '*';

    /**
     * Headers que serán revisados para obtener la información del proxy.
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}

