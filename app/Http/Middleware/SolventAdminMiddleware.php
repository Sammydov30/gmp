<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SolventAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the Authorization header
        $authorizationHeader = $request->header('Authorization');

        // Check if it contains a Bearer token
        if ($authorizationHeader && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            $token = $matches[1]; // Extract the token

            // Manually validate the token (custom logic to check if the token is valid)
            if ($this->isValidToken($token)) {
                return $next($request); // Proceed if the token is valid
            }
        }

        // If the token is invalid or missing, return a 401 Unauthorized response
        return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }

    // Example of token validation logic
    protected function isValidToken($token)
    {
        // Here you can define your custom token validation logic, e.g.,
        // comparing against a hardcoded token or checking from a database
        $validToken = env('BEARER_TOKEN'); // You can store the token in .env or DB
        return $token === $validToken;
        //return \DB::table('api_tokens')->where('token', $token)->exists();
    }
}
