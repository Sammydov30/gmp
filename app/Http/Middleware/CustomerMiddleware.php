<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\HasApiTokens;

class CustomerMiddleware
{
    use HasApiTokens;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        if (auth()->user()->tokenCan('role:customer')) {
            return $next($request);
        }

        //return response()->json(['message' => 'Not Authorized'], 401);
        return response()->json(["message" => "Not Authorized", "status" => "error"], 401);

    }
}
