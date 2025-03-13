<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\ServiceDomain;
use App\Models\ServiceDomainUser;
use App\Models\User;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceDomainService;

class ServiceDomainController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'closed');
        $sortDirection = $request->input('direction', 'asc');

        // Query the service_domains with optional search
        $query = ServiceDomain::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $service_domains = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('service_domains.index', [
            'service_domains' => $service_domains,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        return view('service_domains.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:service_domains',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'enabled' => 'nullable|boolean',
            'closed' => 'nullable|boolean',
        ]);

        $now_ts = now();

        ServiceDomain::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'enabled' => false, // $request->enabled,
            'closed' => false, // $request->closed,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('service_domains.index')->with('success', 'Service Domain created successfully.');
    }

    public function show($id)
    {
        $service_domain = ServiceDomain::with(['creator', 'updater', 'members.role', 'groups' => function ($query) {
            $query->withCount('members');
        }])->where('id', $id)->firstOrFail();
        return view('service_domains.show', compact('service_domain'));
    }
    public function edit($id)
    {
        $service_domain = ServiceDomain::with(['creator', 'updater', 'members.role', 'services', 'groups' => function ($query) {
            $query->withCount('members');
        }])->where('id', $id)->firstOrFail();
        $selected_service_ids = $service_domain->services->pluck('id')->toArray();
        $services = Service::select('id', 'name')->where('enabled', true)->orWhereIn('id', $selected_service_ids)->get()->toArray();

        foreach ($services as $key => $service) {
            $services[$key]['checked'] = '';
            if (in_array($service['id'], $selected_service_ids)) {
                $services[$key]['checked'] = 'checked';
            }
        }
        return view('service_domains.edit', compact('service_domain', 'services'));
    }

    public function update(Request $request, $id)
    {
        $service_domain = ServiceDomain::findOrFail($id);

        $validatedData = $request->validate([
            'services' => 'nullable|array',
            'services.*' => 'required|numeric|exists:services,id',
        ]);

        $services = $validatedData['services'] ?? [];

        $request->validate([
            'name' => 'required|string|max:255|unique:service_domains,name,' . $id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'enabled' => 'required|boolean',
            'closed' => 'required|boolean',
        ]);

        DB::transaction(function () use ($service_domain, $request, $services, $id) {
            $service_domain->update([
                'name' => $request->name,
                'description' => $request->description,
                'color' => $request->color,
                'enabled' => $request->enabled,
                'closed' => $request->closed,
                'updated_by' => Auth::user()->id,
                'updated_at' => now(),
            ]);

            ServiceDomainService::where('service_domain_id', $id)
                ->whereNotIn('service_id', $services)
                ->delete();

            $existingServices = ServiceDomainService::where('service_domain_id', $id)
                ->pluck('service_id')
                ->toArray();

            $newServices = array_diff($services, $existingServices);

            $insertData = array_map(function ($serviceId) use ($id) {
                return [
                    'service_domain_id' => $id,
                    'service_id' => $serviceId
                ];
            }, $newServices);

            if (!empty($insertData)) {
                ServiceDomainService::insert($insertData);
            }

        });
        return redirect()->route('service_domains.index')->with('success', 'Service Domain updated successfully.');
    }

    public function add_users(Request $request, $id)
    {
        $service_domain = ServiceDomain::findOrFail($id);
        $users = $request->input('users', []);
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($users as $userId) {
            DB::table('service_domain_users')->updateOrInsert(
                ['user_id' => $userId, 'service_domain_id' => $service_domain->id],
                ['created_by' => $currentUserId, 'created_at' => now()]
            );
        }

        return redirect()->route('service_domains.edit', $service_domain->id)->with('success', 'Users added successfully.');
    }


    public function add_users_bulk(Request $request, $id)
    {
        $service_domain = ServiceDomain::findOrFail($id);
        $emails = explode("\n", $request->input('emails', []));
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($emails as $email) {
            $email = trim($email);

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Add user to the service domain
                    DB::table('service_domain_users')->updateOrInsert(
                        ['user_id' => $user->id, 'service_domain_id' => $service_domain->id],
                        ['created_by' => $currentUserId, 'created_at' => now()]
                    );
                }
            }
        }

        return redirect()->route('service_domains.edit', $service_domain->id)->with('success', 'Users added successfully.');
    }

    public function remove_user(Request $request, $service_domainId, $userId)
    {
        $service_domain = ServiceDomain::findOrFail($service_domainId);
        $service_domain->members()->detach($userId);

        return redirect()->route('service_domains.edit', $service_domainId)->with('success', 'User removed successfully.');
    }

    public function add_groups(Request $request, $id)
    {
        $service_domain = ServiceDomain::findOrFail($id);
        $groups = $request->input('groups', []);
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($groups as $group_id) {
            DB::table('service_domain_groups')->updateOrInsert(
                ['group_id' => $group_id, 'service_domain_id' => $service_domain->id],
                ['created_by' => $currentUserId, 'created_at' => now()]
            );
        }

        return redirect()->route('service_domains.edit', $service_domain->id)->with('success', 'Group added successfully.');
    }


    public function remove_group(Request $request, $service_domainId, $group_id)
    {
        $service_domain = ServiceDomain::findOrFail($service_domainId);
        $service_domain->groups()->detach($group_id);

        return redirect()->route('service_domains.edit', $service_domainId)->with('success', 'Group removed successfully.');
    }

    public function destroy($id)
    {
        $service_domain = ServiceDomain::findOrFail($id);
        $service_requests = ServiceRequest::where('service_domain_id', $id)->count();
        if($service_requests > 0) {
            return redirect()->back()->with('error', $service_requests . ' exist against the service domain.');
        }
        DB::beginTransaction();
        try {
            DB::table('service_domain_groups')->where('service_domain_id', $id)->delete();
            DB::table('service_domain_services')->where('service_domain_id', $id)->delete();
            DB::table('service_domain_users')->where('service_domain_id', $id)->delete();

            $service_domain->delete();
            DB::commit();
            return redirect()->route('service_domains.index')->with('success', 'Service Domain deleted successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete service domain: ' . $e->getMessage());
        }
    }
}
