<?php

namespace App\Http\Middleware;

use App\Helpers\Response;
use Closure;
use Illuminate\Http\Request;

class Auth
{
    use Response;
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
//        $token = $request->header('Authorization');
//        if (!$token) {
//            $this->error(__('Unauthorized'), 401);
//        }
//        $ip = $request->ip();
//        if (!in_array($ip, config('auth.ips'))) {
//            $this->error(__("Unauthorized: $ip"), 401);
//        }
//        $client = config('auth.clients')[$token] ?? null;
//        if (!$client) {
//            $this->error(__('Unauthorized'), 401);
//        }
//        $request->headers->set('Client', $client);
//        $request->headers->set('RequestCode', uniqid());

        return $next($request);
    }
}
