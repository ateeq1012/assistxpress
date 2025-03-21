<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRoleRoute;
use App\Models\Route as RouteModel;
use App\Helpers\GeneralHelper;


class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'asc');
        $query = DB::table('roles')
            ->leftJoin('users as creators', 'roles.created_by', '=', 'creators.id')
            ->leftJoin('users as updaters', 'roles.updated_by', '=', 'updaters.id')
            ->select(
                'roles.id',
                'roles.name',
                'roles.description',
                'roles.enabled',
                'creators.name as created_by',
                'roles.created_at',
                'updaters.name as updated_by',
                'roles.updated_at'
            );
        if ($search)
        {
            $query->where(function ($q) use ($search)
            {
                $q->whereRaw('LOWER(roles.name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(roles.description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $roles = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('roles.index', [
            'roles' => $roles,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $now_ts = date('Y-m-d H:i:s');
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:255',
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'name' => GeneralHelper::cleanText($request->input('name')),
            'description' => GeneralHelper::cleanText($request->input('description')),
            'created_by' => auth()->user()->id,
            'created_at' => $now_ts,
            'updated_by' => auth()->user()->id,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('roles.edit', ['role' => $roleId])->with('success', 'Role created successfully, Please assign System Access.');
    }

    public function show($id)
    {
        $role = Role::where('id', $id)->with('creator', 'updater', 'users.role')->firstOrFail();
        
        $assigned_routes = UserRoleRoute::where('role_id', $id)->with('route')->get();
        $assigned_routes_lkp = [];
        foreach ($assigned_routes as $ark => $assigned_route)
        {
            $assigned_routes_lkp[$assigned_route->route->name . "-" . $assigned_route->route->type . "-" . $assigned_route->route->method] = $assigned_route->route->id;
        }

        $route_cfg = config('routes');
        $route_cfg_resp = [];
        foreach ($route_cfg as $rk => $route_group)
        {
            if($route_group['public'])
            {
                $route_group_assigned = false;

                foreach ($route_group['routes'] as $rcfgk => $route)
                {
                    if(isset($assigned_routes_lkp[$route]))
                    {
                        $route_group_assigned = true;
                    }
                    else
                    {
                        $route_group_assigned = false;
                        break;
                    }
                }
               
                $route_cfg_resp[] = [
                    'key' => $rk,
                    'description' => $route_group['description'],
                    'selected' => $route_group_assigned,
                    'entity' => $route_group['entity'],
                ];
            }
        }
        
        $route_cfg_resp = collect($route_cfg_resp)->groupBy('entity')->all();

        return view('roles.show', ['role' => $role, 'route_cfg_resp' => $route_cfg_resp]);
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        $assigned_routes = UserRoleRoute::where('role_id', $id)->with('route')->get();
        $assigned_routes_lkp = [];
        foreach ($assigned_routes as $ark => $assigned_route)
        {
            $assigned_routes_lkp[$assigned_route->route->name . "-" . $assigned_route->route->type . "-" . $assigned_route->route->method] = $assigned_route->route->id;
        }

        $route_cfg = config('routes');
        $route_cfg_resp = [];
        foreach ($route_cfg as $rk => $route_group)
        {
            if($route_group['public'])
            {
                $route_group_assigned = false;

                foreach ($route_group['routes'] as $rcfgk => $route)
                {
                    if(isset($assigned_routes_lkp[$route]))
                    {
                        $route_group_assigned = true;
                    }
                    else
                    {
                        $route_group_assigned = false;
                        break;
                    }
                }
               
                $route_cfg_resp[] = [
                    'key' => $rk,
                    'description' => $route_group['description'],
                    'selected' => $route_group_assigned,
                    'entity' => $route_group['entity'],
                ];
            }
        }
        $route_cfg_resp = collect($route_cfg_resp)->groupBy('entity')->all();
        return view('roles.edit', ['role' => $role, 'route_cfg_resp' => $route_cfg_resp]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'enabled' => 'boolean',
        ]);

        $allowedActions = $request->input('allowed_actions', []);

        $routes = RouteModel::select('id', 'type', 'name', 'url', 'method')->get();
        $routes_lkp = $routes->keyBy(fn($route) => $route->name . "-" . $route->type . "-" . $route->method);

        $add_route_data = [];
        $route_cfg = config('routes');
        foreach ($route_cfg as $rk => $route_group)
        {
            if(in_array($rk, $allowedActions) && $route_group['public'])
            {
                foreach ($route_group['routes'] as $route)
                {
                    if(isset($routes_lkp[$route]))
                    {
                        $add_route_data[$routes_lkp[$route]->id] = [
                            'role_id' => $id,
                            'route_id' => $routes_lkp[$route]->id,
                            'created_by' => Auth::user()->id,
                            'created_at' => now(),
                        ];
                    }
                }
            }
        }

        $delete_routes = [];
        $assigned_routes = UserRoleRoute::where('role_id', $id)->with('route')->get();
        foreach ($assigned_routes as $ark => $assigned_route)
        {
            if(isset($add_route_data[$assigned_route->route->id]))
            {
                unset($add_route_data[$assigned_route->route->id]);
            }
            else
            {
                $delete_routes[$assigned_route->route->id] = true;
            }
        }

        $old_id_enabled = Role::findOrFail($id)->enabled;
        $new_is_enabled = $request->input('enabled', false);

        try {
            DB::beginTransaction();

            DB::table('user_role_routes')
                ->where('role_id', $id)
                ->whereIn('route_id', array_keys($delete_routes))
                ->delete();

            DB::table('user_role_routes')->insert(array_values($add_route_data));

            DB::table('roles')->where('id', $id)->update([
                'name' => GeneralHelper::cleanText($request->input('name')),
                'description' => GeneralHelper::cleanText($request->input('description')),
                'enabled' => $new_is_enabled,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $users_to_logout = [];
            if ($old_id_enabled != $new_is_enabled || !empty($delete_routes) || !empty($add_route_data))
            {
                $users_to_logout = User::where('role_id', $id)->pluck('id')->toArray();
                DB::table('sessions')->whereIn('user_id', $users_to_logout)->delete();
            }

            DB::commit();

            if (in_array(Auth::user()->id, $users_to_logout))
            {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('login')->with('info', 'You have updated your own role information!. Please log in again.');
            }

            return redirect()->route('roles.index')->with('success', 'Role updated successfully');

        } catch (\Exception $e)
        {
            DB::rollBack();
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $role_users = User::where('role_id', $id)->count();
        if($role_users > 0) {
            $msg = "There is {$role_users} user against this Role";
            if($role_users > 1) {
                $msg = "There are {$role_users} users against this Role";
            }
            return redirect()->route('roles.index')->with('error', "This Role cannot be deleted, {$msg}");
        }
        else
        {
            $role = Role::findOrFail($id);
            $role->delete();
            return redirect()->route('roles.index');
        }
    }
}
