<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah ada session 'access_granted'
        if (!$request->session()->has('access_granted')) {
            return redirect()->route('landing')->with('error', 'Silakan masukkan kunci akses terlebih dahulu.');
        }

        return $next($request);
    }
}