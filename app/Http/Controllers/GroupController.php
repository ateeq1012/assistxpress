<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Group;
use App\Models\User;
use App\Models\ServiceDomainGroup;
use App\Helpers\GeneralHelper;

class GroupController extends Controller
{
    public function index(Request $request): View
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'asc');
        $query = Group::with('creator', 'updater', 'parent', 'children');
        if ($search)
        {
            $query->where(function ($q) use ($search)
            {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }
        
        $groups = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('groups.index', [
            'groups' => $groups,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        $parent_candidates = DB::table('groups')->where('enabled', true)->get();
        return view('groups.create', compact('parent_candidates'));
    }

    public function store(Request $request)
    {
        $now_ts = date('Y-m-d H:i:s');

        $request->validate([
            'name' => 'required|string|max:255|unique:groups',
        ]);


        $group = Group::create([
            'name' => GeneralHelper::cleanText($request->name),
            'description' => GeneralHelper::cleanText($request->description),
            'parent_id' => $request->parent_id ?? null,
            'enabled' => $request->enabled ?? false,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ]);
        return redirect()->route('groups.edit', ['group' => $group->id])->with('success', 'Group created successfully, now you can assign members.');
    }

    public function show($id)
    {
        $group = Group::with('creator', 'updater', 'parent', 'children', 'members.role', 'members.groups')->where('id', $id)->firstOrFail();
        $groups = DB::table('groups')->where('enabled', true)->get();
        return view('groups.show', ['group' => $group]);
    }

    public function edit($id)
    {
        $group = Group::with('creator', 'updater', 'parent', 'children', 'members.role', 'members.groups')->where('id', $id)->firstOrFail();
        $all_groups = Group::get();

        $hierarchy = $this->create_hierarchy($all_groups, ['id'], ['parent_id']);

        if(isset($group->parent_id))
        {
            $this->drop_branches($hierarchy, $group->parent_id);
        }
        else
        {
            $this->drop_branch($hierarchy, $id);
        }

        $parent_candidates = $this->flatify($hierarchy);

        foreach ($parent_candidates as $key => $value)
        {
            if(!$value['enabled'])
            {
                unset($parent_candidates[$key]);
            }
        }
        return view('groups.edit', ['group' => $group, 'parent_candidates' => $parent_candidates]);
    }

    public function update(Request $request, $id)
    {
        $now_ts = date('Y-m-d H:i:s');
        $request->request->all();
        $request->validate([
            'name' => 'required|string|max:255|unique:groups,name,' . $id,
        ]);
        
        DB::table('groups')->where('id', $id)->update([
            'name' => GeneralHelper::cleanText($request->name),
            'description' => GeneralHelper::cleanText($request->description),
            'parent_id' => $request->parent_id,
            'enabled' => $request->enabled,
            'updated_by' => Auth::user()->id,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('groups.index')->with('success', 'Group updated successfully.');
    }

    public function add_users(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $users = $request->input('users', []);
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($users as $userId) {
            DB::table('user_groups')->updateOrInsert(
                ['user_id' => $userId, 'group_id' => $group->id],
                ['created_by' => $currentUserId, 'created_at' => now()]
            );
        }

        return redirect()->route('groups.edit', $group->id)->with('success', 'Users added successfully.');
    }

    public function add_users_bulk(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $emails = explode("\n", $request->input('emails', []));
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($emails as $email) {
            $email = trim($email);

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Add user to the group
                    DB::table('user_groups')->updateOrInsert(
                        ['user_id' => $user->id, 'group_id' => $group->id],
                        ['created_by' => $currentUserId, 'created_at' => now()]
                    );
                }
            }
        }

        return redirect()->route('groups.edit', $group->id)->with('success', 'Users added successfully.');
    }

    public function remove_user(Request $request, $groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        $group->members()->detach($userId);

        return redirect()->route('groups.edit', $groupId)->with('success', 'User removed successfully.');
    }

    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        $group->delete();
        return redirect()->route('groups.index');
    }
    
    public function create_hierarchy($data, $pks, $relks)
    {
        // Convert the data to an associative array for easy access
        $data = json_decode(json_encode($data), true);

        // Prepare an array to hold the hierarchical structure
        $hierarchy = [];
        $items = [];
        
        // First, create a map of all items by their ID
        foreach ($data as $item)
        {
            $primaryKeys = array_intersect_key($item, array_flip($pks));
            $id = implode('-', $primaryKeys);
            $items[$id] = $item;
            $items[$id]['children'] = [];
        }

        // Next, build the hierarchy by assigning children
        foreach ($items as $id => &$item)
        {
            $parentPrimaryKeys = array_intersect_key($item, array_flip($relks));
            $parentId = implode('-', $parentPrimaryKeys);
            if ($parentId === null || !isset($items[$parentId]))
            {
                // If no parent or parent does not exist, it's a root item
                $hierarchy[$id] = &$item;
            }
            else
            {
                // If there is a parent, add this item to its parent's children
                $items[$parentId]['children'][$id] = &$item;
            }
        }
        
        // Return the hierarchy with proper nesting
        return $hierarchy;
    }
    
    public function drop_branch(&$hierarchy, $targetId)
    {
        foreach ($hierarchy as $id => &$item)
        {
            // If the current item is the target, remove it
            if ($id == $targetId)
            {
                unset($hierarchy[$id]);
                return true; // Successfully removed the target item
            }
            
            // If the item has children, recursively check them
            if (!empty($item['children']))
            {
                if ($this->drop_branch($item['children'], $targetId))
                {
                    // If the target was found and removed in the children, remove the empty 'children' key
                    if (empty($item['children']))
                    {
                        unset($item['children']);
                    }
                    return true; // Successfully removed the target item
                }
            }
        }
        
        return false; // Target item was not found in this branch
    }
    
    public function drop_branches(&$hierarchy, $targetId)
    {
        foreach ($hierarchy as $id => &$item)
        {
            // If the current item is the target, remove it
            if ($id == $targetId && array_key_exists('children', $item))
            {
                unset($item['children']);
                return true; // Successfully removed the target item
            }
            
            // If the item has children, recursively check them
            if (!empty($item['children']))
            {
                if ($this->drop_branches($item['children'], $targetId))
                {
                    // If the target was found and removed in the children, remove the empty 'children' key
                    if (empty($item['children']))
                    {
                        unset($item['children']);
                    }
                    return true; // Successfully removed the target item
                }
            }
        }
        
        return false; // Target item was not found in this branch
    }

    public function flatify($array)
    {
        $result = [];
        foreach ($array as $item)
        {
            $this->flatten_node($item, $result);
        }
        return $result;
    }

    private function flatten_node($node, &$result)
    {
        // Filter the current node to remove the 'children' key
        $current = $node;
        unset($current['children']);

        // Add the current node to the result
        $result[] = $current;

        // Process each child node if there are any
        if (isset($node['children']) && is_array($node['children']))
        {
            foreach ($node['children'] as $child)
            {
                $this->flatten_node($child, $result);
            }
        }
    }

    public function search(Request $request)
    {
        $search = $request->input('q');
        $enabled_only = $request->input('enabled_only', false);
        $service_domain_id = $request->input('service_domain_id', null);

        $already_added_groups = [];
        if($service_domain_id) {
            $already_added_groups = ServiceDomainGroup::where('service_domain_id', $service_domain_id)->pluck('group_id')->toArray();
        }

        if (!$search) {
            return response()->json([]);
        }

        $query = Group::query()->where('name', 'ILIKE', "%{$search}%");

        if ( count($already_added_groups) > 0 ) {
            $query->whereNotIn('id', $already_added_groups);
        }

        if ($enabled_only) {
            $query->where('enabled', true);
        }

        // Eager load member count
        $groups = $query->withCount('members')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($groups);
    }
}
