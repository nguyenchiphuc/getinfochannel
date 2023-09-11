<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class ApiDataLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::channel('api-in')->info('Url : '.json_encode($request->url()).' - Data : '.json_encode($request->all()));
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $log = ['status' => $response->getStatusCode(), 'desc' => json_encode($response)];
        Log::channel('api-out')->info(json_encode($log));
    }
}
