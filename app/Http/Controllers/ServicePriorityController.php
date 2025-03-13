<?php

namespace App\Http\Controllers;

use App\Models\ServicePriority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServicePriorityController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'order');
        $sortDirection = $request->input('direction', 'asc');

        // Query the service_priorities with optional search
        $query = ServicePriority::query();

        if ($search) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $service_priorities = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('service_priorities.index', [
            'service_priorities' => $service_priorities,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        return view('service_priorities.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:service_priorities',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
        ]);

        // Set the current timestamp
        $now_ts = now();

        ServicePriority::create([
            'name' => $request->name,
            'color' => $request->color,
            'order' => $request->order ?? ServicePriority::max('order') + 1, // Set the highest order + 1
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('service_priorities.index')->with('success', 'Status created successfully.');
    }

    public function show($id)
    {
        $service_priorities = ServicePriority::findOrFail($id);
        return view('service_priorities.show', compact('service_priorities'));
    }

    public function edit($id)
    {
        $service_priorities = ServicePriority::findOrFail($id);
        return view('service_priorities.edit', compact('service_priorities'));
    }

    public function update(Request $request, $id)
    {
        $service_priorities = ServicePriority::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:service_priorities,name,' . $id,
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
        ]);

        $service_priorities->update([
            'name' => $request->name,
            'color' => $request->color,
            'order' => $request->order ?? $service_priorities->order,
            'updated_by' => Auth::user()->id,
            'updated_at' => now(),
        ]);

        return redirect()->route('service_priorities.index')->with('success', 'Status updated successfully.');
    }

    public function destroy($id)
    {
        $service_priorities = ServicePriority::findOrFail($id);
        $service_priorities->delete();

        return redirect()->route('service_priorities.index')->with('success', 'Status deleted successfully.');
    }
}