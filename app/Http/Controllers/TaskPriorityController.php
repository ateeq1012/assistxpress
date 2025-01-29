<?php

namespace App\Http\Controllers;

use App\Models\TaskPriority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskPriorityController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'order');
        $sortDirection = $request->input('direction', 'asc');

        // Query the task_priorities with optional search
        $query = TaskPriority::query();

        if ($search) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $task_priorities = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('task_priorities.index', [
            'task_priorities' => $task_priorities,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        return view('task_priorities.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:task_priorities',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
        ]);

        // Set the current timestamp
        $now_ts = now();

        TaskPriority::create([
            'name' => $request->name,
            'color' => $request->color,
            'order' => $request->order ?? TaskPriority::max('order') + 1, // Set the highest order + 1
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('task_priorities.index')->with('success', 'Status created successfully.');
    }

    public function show($id)
    {
        $task_priority = TaskPriority::findOrFail($id);
        return view('task_priorities.show', compact('task_priority'));
    }

    public function edit($id)
    {
        $task_priority = TaskPriority::findOrFail($id);
        return view('task_priorities.edit', compact('task_priority'));
    }

    public function update(Request $request, $id)
    {
        $task_priority = TaskPriority::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:task_priorities,name,' . $id,
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
        ]);

        $task_priority->update([
            'name' => $request->name,
            'color' => $request->color,
            'order' => $request->order ?? $task_priority->order,
            'updated_by' => Auth::user()->id,
            'updated_at' => now(),
        ]);

        return redirect()->route('task_priorities.index')->with('success', 'Status updated successfully.');
    }

    public function destroy($id)
    {
        $task_priority = TaskPriority::findOrFail($id);
        $task_priority->delete();

        return redirect()->route('task_priorities.index')->with('success', 'Status deleted successfully.');
    }
}