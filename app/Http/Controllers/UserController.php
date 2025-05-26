<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRoleRoute;
use App\Models\ServiceDomainUser;
use App\Models\UserGroup;
use App\Models\Route as RouteModel;

use App\Helpers\GeneralHelper;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'asc');

        $query = User::with('creator', 'updater', 'role', 'groups')
            ->where('is_sys_user', false);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('LOWER(users.name)'), 'like', '%' . strtolower($search) . '%')
                  ->orWhere(DB::raw('LOWER(users.email)'), 'like', '%' . strtolower($search) . '%')
                  ->orWhere('users.phone', 'like', '%' . strtolower($search) . '%')
                  ->orWhereHas('role', function ($roleQuery) use ($search) {
                      $roleQuery->where(DB::raw('LOWER(name)'), 'like', '%' . strtolower($search) . '%')
                                ->orWhere(DB::raw('LOWER(description)'), 'like', '%' . strtolower($search) . '%');
                  });
            });
        }

        if (!in_array($sortColumn, ['id', 'name', 'email', 'phone', 'created_at'])) {
            $sortColumn = 'id';
        }

        $users = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('users.index', [
            'users' => $users,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        $roles = DB::table('roles')->where('enabled', true)->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $now_ts = date('Y-m-d H:i:s');

        // Validation rules
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|numeric|digits_between:10,15',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                'confirmed',
            ],
            'role_id' => 'required|integer|exists:roles,id',
            'enabled' => 'required|boolean',
        ], [
            'password.regex' => 'Password must contain at least one lowercase letter, one uppercase letter, one number, and one special character.',
            'password.confirmed' => 'The password and confirmation must match.',
        ]);

        $data = [
            'name' => GeneralHelper::cleanText($request->input('name')),
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'enabled' => $request->boolean('enabled'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ];
        $user = User::create($data);

        // Redirect with success message
        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show($id)
    {
        $user = User::where('users.id', $id)->with('creator', 'updater', 'role', 'groups')->firstOrFail();
        return view('users.show', ['user' => $user]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = DB::table('roles')->where('enabled', true)->orWhere('id', $user->role_id)->get();
        return view('users.edit', ['user' => $user, 'roles' => $roles]);
    }

    public function user_role_routes(Request $request)
    {
        try
        {
            $user_id = $request->input('user_id');
            $role_id = $request->input('role_id');

            $assigned_routes = UserRoleRoute::where('user_id', $user_id)->orWhere('role_id', $role_id)->with('route')->get()->toArray();
            $assigned_routes_lkp = [];
            
            foreach ($assigned_routes as $ark => $assigned_route)
            {
                if(isset($assigned_route['user_id']))
                {
                    $assigned_routes_lkp[$assigned_route['route']['name'] . '-' . $assigned_route['route']['type'] . '-' . $assigned_route['route']['method'] . '_user'] = $assigned_route;
                }

                if(isset($assigned_route['role_id']))
                {
                    $assigned_routes_lkp[$assigned_route['route']['name'] . '-' . $assigned_route['route']['type'] . '-' . $assigned_route['route']['method'] . '_role'] = $assigned_route;
                }
            }

            $route_cfg = config('routes');
            $route_cfg_resp = [];
            
            foreach ($route_cfg as $rk => $route_group)
            {
                if($route_group['public'])
                {
                    $route_group_assigned_to_user = false;
                    $route_group_assigned_to_role = false;

                    foreach ($route_group['routes'] as $route)
                    {
                        if(isset($assigned_routes_lkp[$route . '_user']) && $assigned_routes_lkp[$route . '_user']['is_allowed'] == true)
                        {
                            $route_group_assigned_to_user = 'allowed';
                        }
                        else if(isset($assigned_routes_lkp[$route . '_user']) && $assigned_routes_lkp[$route . '_user']['is_allowed'] == false)
                        {
                            $route_group_assigned_to_user = 'forbidden';
                        }
                        else
                        {
                            $route_group_assigned_to_user = 'not-assigned';
                            break;
                        }
                    }

                    foreach ($route_group['routes'] as $route)
                    {
                        if(isset($assigned_routes_lkp[$route . '_role']))
                        {
                            $route_group_assigned_to_role = true;
                        }
                        else
                        {
                            $route_group_assigned_to_role = false;
                            break;
                        }
                    }
                   
                    $route_cfg_resp[$route_group['entity']][] = [
                        'key' => $rk,
                        'description' => $route_group['description'],
                        'assigned_to_user' => $route_group_assigned_to_user,
                        'assigned_to_role' => $route_group_assigned_to_role,
                    ];
                }
            }
            $resp = [
                'success' => true,
                'data' => $route_cfg_resp,
            ];
            return response()->json($resp, 200);
        }
        catch(QueryException $e)
        {
            $resp = [
                'success' => false,
                'message' => 'Something went wrong while fetching data, Please try again later. (QE0x'.__LINE__.')',
            ];
            return response()->json($resp, 500);
        }
        catch(Exception $e)
        {
            $resp = [
                'success' => false,
                'message' => 'Something went wrong while fetching data, Please try again later. (E0x'.__LINE__.')',
            ];
            return response()->json($resp, 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $now_ts = date('Y-m-d H:i:s');

            $request->validate([
                'email' => 'required|email|unique:users,email,' . $id,
                'phone' => 'nullable|numeric|digits_between:10,15',
                'password' => 'nullable',
                'enabled' => 'required|boolean',
            ]);
            $data = [
                'name' => GeneralHelper::cleanText($request->name),
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $request->role_id,
                'enabled' => $request->enabled,
                'updated_by' => auth()->id(),
                'updated_at' => $now_ts,
            ];

            $add_route_data = [];
            
            if ($request->has('acl')) {
                $aclData = $request->input('acl');

                $routes = RouteModel::select('id', 'type', 'name', 'url', 'method')->get();
                $routes_lkp = $routes->keyBy(fn($route) => $route->name . "-" . $route->type . "-" . $route->method);

                $route_cfg = config('routes');
                foreach ($route_cfg as $rk => $route_group) {
                    if (isset($aclData[$rk]) && $route_group['public']) {
                        $is_allowed = null;
                        if ($aclData[$rk] == 'allowed') {
                            $is_allowed = true;
                        } elseif ($aclData[$rk] == 'forbidden') {
                            $is_allowed = false;
                        } else {
                            continue;
                        }

                        foreach ($route_group['routes'] as $route) {
                            if (isset($routes_lkp[$route])) {
                                $add_route_data[$routes_lkp[$route]->id] = [
                                    'user_id' => $id,
                                    'route_id' => $routes_lkp[$route]->id,
                                    'is_allowed' => $is_allowed,
                                    'created_by' => Auth::user()->id,
                                    'created_at' => $now_ts,
                                ];
                            }
                        }
                    }
                }
                $assigned_routes = UserRoleRoute::select('id', 'user_id', 'route_id', 'is_allowed')->where('user_id', $id)->get();                
                $delete_routes = [];
                $update_routes = [];
                foreach ($assigned_routes as $asr) {
                    if (isset($add_route_data[$asr->route_id])) {
                        $ard = $add_route_data[$asr->route_id];
                        if ($ard['is_allowed'] === $asr->is_allowed) {
                            unset($add_route_data[$asr->route_id]);
                        } else {
                            $route_to_update = $asr;
                            $route_to_update->is_allowed = $ard['is_allowed'];
                            $update_routes[] = $route_to_update;
                            unset($add_route_data[$asr->route_id]);
                        }
                    } else {
                        $delete_routes[] = $asr['id'];
                    }
                }
            }
            
            DB::beginTransaction();
            
            $user = User::findOrFail($id); // Fetch user before update to get old values
            $old_role_id = $user->role_id; // Store old role_id
            $old_is_enabled = $user->enabled;

            DB::table('users')->where('id', $id)->update($data);
            if (count($add_route_data)) {
                DB::table('user_role_routes')->insert(array_values($add_route_data));
            }
            if (count($update_routes)) {
                foreach ($update_routes as $urv) {
                    DB::table('user_role_routes')->where('user_id', $id)->where('id', $urv['id'])->update(['is_allowed' => $urv['is_allowed']]);
                }
            }
            if (count($delete_routes)) {
                DB::table('user_role_routes')->where('user_id', $id)->whereIn('id', $delete_routes)->delete();
            }

            DB::commit();

            $new_role_id = $request->input('role_id');
            $new_is_enabled = $request->input('enabled', false);

            // Check if role_id, enabled, or routes changed for the edited user
            if ($old_role_id != $new_role_id || $old_is_enabled != $new_is_enabled || !empty($delete_routes) || !empty($add_route_data) || !empty($update_routes)) {
                // Log out only the edited user
                DB::table('sessions')->where('user_id', $id)->delete();

                // If the edited user is the current logged-in user, log them out fully
                if (Auth::user()->id == $id) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect('login')->with('info', 'You have updated your own user information or role! Please log in again.');
                }
            }

            return redirect()->route('users.index')->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error updating User: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        echo "User Delete Not Allowed"; exit();
        // if($id != 1) {
        //     $user = User::findOrFail($id);
        //     $user->delete();
        // }
        // return redirect()->route('users.index');
    }

    public function search(Request $request)
    {
        $search = $request->input('q');
        $enabled_only = $request->input('enabled_only') ?? false;
        $service_domain_id = $request->input('service_domain_id', null);
        $group_id = $request->input('group_id', null);

        $already_added_users = [];
        if($service_domain_id) {
            $already_added_users = ServiceDomainUser::where('id', $service_domain_id)->pluck('user_id')->toArray();
        }
        if($group_id) {
            $already_added_users = UserGroup::where('id', $group_id)->pluck('user_id')->toArray();
        }
        if (!$search) {
            return response()->json([]);
        }

        $query = User::query()
            ->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });

        if (count($already_added_users) > 0) {
            $query->whereNotIn('id', $already_added_users);
        }
        $query->whereNot('id', 1);

        if ($enabled_only) {
            $query->where('enabled', true);
        }

        $users = $query->limit(10)->get(['id', 'name', 'email', 'phone']);

        return response()->json($users);
    }

    public function download(Request $request)
    {
        $searchParams = $request->only(['name', 'email']);
        $now_ts = date('Ymd_Hms');
        return Excel::download(new UsersExport($searchParams), "users_{$now_ts}.xlsx");
    }
}