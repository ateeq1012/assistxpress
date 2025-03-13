<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\ServiceRequestCustomField;
use App\Helpers\GeneralHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Requests\StoreCustomFieldRequest;


class CustomFieldController extends Controller
{
	public function index(Request $request)
	{
		$pageSize = $request->input('page_size', 25);
		$search = $request->input('search');
		$sortColumn = $request->input('sort', 'id');
		$sortDirection = $request->input('direction', 'asc');

		// Query the custom_fields with optional search
		$query = CustomField::query();

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
				  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
			});
		}

		$custom_fields = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

		return view('custom_fields.index', [
			'custom_fields' => $custom_fields,
			'pageSize' => $pageSize,
			'search' => $search,
			'sortColumn' => $sortColumn,
			'sortDirection' => $sortDirection,
		]);
	}

	public function create(Request $request)
	{
		$custom_field = new CustomField();
		$field_type = $request->query('field_type', null);
		return view('custom_fields.create', ['custom_field' => $custom_field, 'field_type' => $field_type]);
	}

	public function store( Request $request)
	{
		$now_ts = date('Y-m-d H:i:s');
		$request->merge(['field_id' => 'tmp'.time()]);
		$custom_field = new CustomField();
	    $custom_field->field_type = $request->input('field_type', null);

		$validatedData = $request->validate($custom_field->rules(),$custom_field->messages());

		DB::transaction(function () use (&$custom_field, $validatedData, $now_ts) {
			// Step 1: Insert the record and get the ID
			$custom_field = CustomField::create([
				'field_id' => $validatedData['field_id'],
				'name' => $validatedData['name'],
				'description' => $validatedData['description'],
				'field_type' => $validatedData['field_type'],
				'required' => $validatedData['required'] ?? false,
				'use_as_filter' => $validatedData['use_as_filter'] ?? false,
				'settings' => json_encode($validatedData['settings']),
				'created_by' => Auth::user()->id,
				'updated_by' => Auth::user()->id,
				'created_at' => $now_ts,
				'updated_at' => $now_ts,
			]);

			// Step 2: Update the field_id after getting the ID
			$custom_field->field_id = 'cf_' . $custom_field->id;
			$custom_field->save();
		});

		CustomField::regenerateServiceRequestView();

		return redirect()->route('custom_fields.index')->with('success', 'Custom Field created successfully.');
	}

	public function edit($id)
	{
		$custom_field = CustomField::findOrFail($id);
		$custom_field->settings = json_decode($custom_field->settings, true);
		// echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $custom_field ); echo "</pre><br>"; exit;
		$field_template = $custom_field->settings['field_template'];
		return view('custom_fields.edit', compact('custom_field','field_template'));
	}
	
	public function update(Request $request, $id)
	{
		// echo "<pre><strong>" . __FILE__ . " Line: [". __LINE__ ."]</strong> @ " .date("Y-m-d H:i:s"). "<br>"; print_r( $request->all() ); echo "</pre><br>"; exit;
		$now_ts = date('Y-m-d H:i:s');

		$custom_field = CustomField::findOrFail($id);
		$custom_field_original_settings = json_decode($custom_field->settings, true);

		$request->merge(['field_id' => $custom_field->field_id]);
		$validatedData = $request->validate($custom_field->rules(),$custom_field->messages());

		$custom_field->name = $validatedData['name'];
		$custom_field->description = $validatedData['description'];
		$custom_field->field_type = $validatedData['field_type'];
		$custom_field->required = $validatedData['required'] ?? false;
		$custom_field->use_as_filter = $validatedData['use_as_filter'] ?? false;
		$custom_field->settings = json_encode($validatedData['settings']);
		$custom_field->created_by = Auth::user()->id;
		$custom_field->updated_by = Auth::user()->id;
		$custom_field->created_at = $now_ts;
		$custom_field->updated_at = $now_ts;

		$original_options = [];
		$new_options = [];
		$deleted_options = [];
		$modified_options = [];

		if(in_array($validatedData['field_type'], ['Dropdown List', 'Checkbox Group', 'Radio Buttons']) && isset($validatedData['settings']) && isset($validatedData['settings']['options'])) {

			$new_options = $validatedData['settings']['options'];
			$original_options = $custom_field_original_settings['options'];
			foreach ($original_options as $ook => $oov) {
				if(isset($new_options[$ook])) {
					if($new_options[$ook] != $oov) {
						$modified_options[$ook] = [
							'from' => $oov,
							'to' => $new_options[$ook]
						];
					}
				} else {
					$deleted_options[$ook] = $oov;
				}
			}
		}

		if (count($deleted_options) > 0 || count($modified_options) > 0) {
			$service_request_data = ServiceRequestCustomField::where('field_id', $id)
				->where(function ($query) use ($deleted_options, $modified_options) {
					foreach ($deleted_options as $dok => $dov) {
						$query->orWhereRaw("jsonval->>'{$dok}' = ?", [$dov]);
					}
					foreach ($modified_options as $mok => $mov) {
						$query->orWhereRaw("jsonval->>'{$mok}' = ?", [$mov['from']]);
					}
				})
				->get();
			foreach ($service_request_data as $service_request) {
				$opts = json_decode($service_request->jsonval, true);
				$opts = array_diff_key($opts, $deleted_options);
				foreach ($opts as $key => $value) {
				    if (isset($new_options[$key])) {
				        $opts[$key] = $new_options[$key];
				    }
				}
				if(count($opts) > 0) {
					$service_request->jsonval = json_encode($opts);
					$service_request->value = implode(', ', $opts);
					$service_request->save();
				} else {
					$service_request->delete();
				}
			}
		}

		$custom_field->save();

		CustomField::regenerateServiceRequestView();

		return redirect()->route('custom_fields.index')->with('success', 'Custom Field created successfully.');
	}

	public function destroy($id)
	{
		$custom_field = CustomField::findOrFail($id);
		ServiceRequestCustomField::where('field_id', $id)->delete();
		$custom_field->delete();

		CustomField::regenerateServiceRequestView();

		return redirect()->route('custom_fields.index')->with('success', 'Custom field deleted successfully.');
	}
}
