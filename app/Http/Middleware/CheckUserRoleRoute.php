<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class CheckUserRoleRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $requested_route = $request->route()->getName();
        $always_allow_routes = [null, 'login', 'login.form', 'login.submit', 'logout', 'home'];
        
        if(in_array($requested_route, $always_allow_routes)) {
            return $next($request);
        }
        
        $allowed_routes = session('user_routes');

        if(isset($allowed_routes) && isset($allowed_routes[$requested_route])) {
            return $next($request);
        }
        else
        {
            return redirect()->back()->with('error', 'You do not have Permission to perform this action!');
        }

        return $next($request);
    }
}
