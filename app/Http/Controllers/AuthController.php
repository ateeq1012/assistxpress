<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UserRoleRoute;
use App\Models\Role;
use App\Models\User;

class AuthController extends Controller
{
	public function showLoginForm()
	{
		return view('auth.login');
	}

	public function login(Request $request)
	{
		$credentials = $request->validate([
			'email' => ['required', 'email'],
			'password' => ['required'],
		]);

		$user = User::where('email', $credentials['email'])->first();
		if ($user && $user->id === 1) {
			return back()->withErrors(['email' => 'This account is not allowed to log in.']);
		}

		if (Auth::attempt($credentials)) {
			$request->session()->regenerate();
			$user = Auth::user();
			$user_role = Role::where('id', $user->role_id)->first();

			$user_role_routes = DB::table('routes')
				->select('name')
				->whereIn('id', function ($query) use ($user) {
					$query->select('route_id')
						->from('user_role_routes')
						->where(function ($q) use ($user) {
							$q->where('user_id', $user->id)
							  ->where('is_allowed', true)
							  ->orWhere('role_id', $user->role_id);
						})
						->whereNotIn('route_id', function ($q) use ($user) {
							$q->select('route_id')
								->from('user_role_routes')
								->where('user_id', $user->id)
								->where('is_allowed', false);
						});
				})
				->get();

			$allowed_routes = array_column($user_role_routes->toArray(), 'name');

			session([
				'user_routes' => $allowed_routes,
				'user_role_name' => $user_role ? $user_role->name : '{not assigned}',
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