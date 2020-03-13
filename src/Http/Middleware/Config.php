<?php

namespace L5Swagger\Http\Middleware;

use Closure;

class Config
{
    public function handle($request, Closure $next, $guard = null)
    {
        $actions = $request->route()->getAction();

        $documentation = $actions['documentation'];

        if ($documentation === 'legacy') {
            $config = config('l5-swagger');
        } else {
            $config = config('l5-swagger.documentations.' . $documentation);
        }

        $request->offsetSet('documentation', $documentation);
        $request->offsetSet('config', $config);

        return $next($request);
    }
}
