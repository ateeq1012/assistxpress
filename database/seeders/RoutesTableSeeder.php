<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\DB;
use App\Models\Route as RouteModel;

class RoutesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all existing routes from the database
        $existing_routes = RouteModel::get();

        // Initialize the lookup array for existing routes
        $existing_routes_lkp = [];
        foreach ($existing_routes as $existing_route)
        {
            $route_identifier = $existing_route->name . "-" . $existing_route->type . "-" . $existing_route->method;
            $existing_routes_lkp[$route_identifier] = $existing_route->id;
        }

        // Get all defined routes in the Laravel application
        $routeCollection = RouteFacade::getRoutes();
        $routes_to_create = [];

        foreach ($routeCollection as $key => $route)
        {
            // Skip routes that are not relevant for this operation
            if (str_contains($route->uri(), 'password') || 
                str_contains($route->getActionName(), 'Controllers\Auth') || 
                str_contains($route->uri(), 'api/') || 
                str_contains($route->uri(), '_ignition/') || 
                $route->getName() == '' || 
                $route->getName() == '/')
            {
                continue;
            }

            // Define the route attributes
            $file_route = [
                'type' => 'web', // Set type as 'web' or change based on your logic
                'name' => $route->getName(),
                'url' => $route->uri(),
                'method' => $route->methods()[0], // Get the first method, assuming only one
                'controller' => $route->getActionName(),
                'middleware' => implode(',', $route->middleware()), // Convert middleware array to a string
            ];

            // Create a unique identifier for the route
            $this_route_identifier = $file_route['name'] . "-" . $file_route['type'] . "-" . $file_route['method'];

            // Check if this route does not exist in the database
            if (!isset($existing_routes_lkp[$this_route_identifier]))
            {

                RouteModel::create($file_route);
            }
            else
            {
                RouteModel::where('id', $existing_routes_lkp[$this_route_identifier])->update([
                    'url' => $file_route['url'],
                    'controller' => $file_route['controller'],
                    'middleware' => $file_route['middleware'],
                ]);
            }
        }

        $sysUser = DB::table('users')->where('email', 'no-reply-inxhelpdesk@innexiv.com')->first();

        $all_routes = DB::table('routes')->get();
        $all_routes_data = [];
        
        DB::table('user_role_routes')->where('user_id', $sysUser->id)->delete();

        foreach ($all_routes as $key => $route)
        {
            $all_routes_data[] = [
                'user_id' => $sysUser->id,
                'role_id' => null,
                'route_id' => $route->id,
                'is_allowed' => true,
                'created_by' => $sysUser->id,
                'created_at' => now(),
            ];
        }
        DB::table('user_role_routes')->insert($all_routes_data);


        $adminUser = DB::table('users')->where('email', 'adm-inx-helpdesk@innexiv.com')->first();

        $all_routes = DB::table('routes')->get();
        $all_routes_data = [];
            
        DB::table('user_role_routes')->where('user_id', $adminUser->id)->delete();

        foreach ($all_routes as $key => $route)
        {
            $all_routes_data[] = [
                'user_id' => $adminUser->id,
                'role_id' => null,
                'route_id' => $route->id,
                'is_allowed' => true,
                'created_by' => $adminUser->id,
                'created_at' => now(),
            ];
        }

        DB::table('user_role_routes')->insert($all_routes_data);

    }
}
