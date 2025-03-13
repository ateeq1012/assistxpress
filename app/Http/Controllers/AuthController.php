<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\UserRoleRoute;
use App\Models\Role;
class AuthController extends Controller
{
	public function showLoginForm()
	{
		return view('auth.login');
	}

	public function login(Request $request)
	{
		$credentials = $request->only('email', 'password');

		if (Auth::attempt($credentials)) {

			$user = Auth::user();
			$user_role = Role::where('id', $user->role_id)->first();

			$user_role_routes = DB::select(
	            "SELECT name from routes WHERE id IN (SELECT route_id FROM user_role_routes WHERE ((user_id = ? AND is_allowed = true) OR (role_id = ?)) AND route_id NOT IN (SELECT route_id FROM user_role_routes WHERE user_id = ? AND is_allowed = false ))",
	            [$user->id, $user->role_id, $user->id]
	        );

			$allowed_routes = [];
			foreach ($user_role_routes as $route) {
				$allowed_routes[$route->name] = true;
			}

			session([
				'user_routes' => $allowed_routes,
				'user_role_name' => isset($user_role) ? $user_role->name : '{not assigned}',
			]);

			return redirect()->intended('/');
		}

		return back()->withErrors(['email' => 'Invalid credentials']);
	}

	public function logout()
	{
		Auth::logout();
		request()->session()->invalidate();
		request()->session()->regenerateToken();
		return redirect('/login');
	}
}

