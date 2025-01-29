<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Status;

class StatusController extends Controller
{
    /**
     * Display a listing of the statuses with drag-and-drop support for ordering.
     */
    public function index(Request $request)
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'order');
        $sortDirection = $request->input('direction', 'asc');

        // Query the statuses with optional search
        $query = Status::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $statuses = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('statuses.index', [
            'statuses' => $statuses,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    /**
     * Show the form for creating a new status.
     */
    public function create()
    {
        return view('statuses.create');
    }

    /**
     * Store a newly created status in the database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:statuses',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'type' => 'required|string',
            'order' => 'nullable|integer',
        ],[
            'type.required' => 'Status Stage requried.'
        ]);

        // Set the current timestamp
        $now_ts = now();

        Status::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'type' => $request->type,
            'order' => $request->order ?? Status::max('order') + 1,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('statuses.index')->with('success', 'Status created successfully.');
    }

    /**
     * Display the specified status.
     */
    public function show($id)
    {
        $status = Status::findOrFail($id);
        return view('statuses.show', compact('status'));
    }

    /**
     * Show the form for editing the specified status.
     */
    public function edit($id)
    {
        $status = Status::findOrFail($id);
        return view('statuses.edit', compact('status'));
    }

    /**
     * Update the specified status in the database.
     */
    public function update(Request $request, $id)
    {
        $status = Status::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:statuses,name,' . $id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'type' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);

        $status->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'type' => $request->type,
            'order' => $request->order ?? $status->order,
            'updated_by' => Auth::user()->id,
            'updated_at' => now(),
        ]);

        return redirect()->route('statuses.index')->with('success', 'Status updated successfully.');
    }

    /**
     * Remove the specified status from the database.
     */
    public function destroy($id)
    {
        $status = Status::findOrFail($id);
        $status->delete();

        return redirect()->route('statuses.index')->with('success', 'Status deleted successfully.');
    }
}