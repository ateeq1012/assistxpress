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

        // If edit not allowed, send to show if allowed
        if (isset($allowed_routes) && $requested_route === 'service_requests.edit' && !isset($allowed_routes[$requested_route]) && isset($allowed_routes['service_requests.show'])) {
            $id = $request->route('service_request');
            return redirect()->route('service_requests.show', ['service_request' => $id])->with('info', 'You do not have permission to edit this Service Request. Viewing instead.');

        }
        
        if(isset($allowed_routes) && isset($allowed_routes[$requested_route])) {
            return $next($request);
        } else {
            return redirect()->back()->with('error', 'You do not have Permission to perform this action!');
        }

        return $next($request);
    }
}
