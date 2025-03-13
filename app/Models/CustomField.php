<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class CustomField extends Model
{
    use HasFactory; // Include this trait for Eloquent factory support

    protected $table = 'custom_fields';

    protected $fillable = [
        'field_id', 
        'name', 
        'description', 
        'field_type', 
        'required', 
        'use_as_filter', 
        'settings', 
        'created_by', 
        'updated_by'
    ];

    public function rules()
    {
        $rules = [
            'field_id' => 'required|string',
            'name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9_.()\[\] -]+$/',
                'max:255',
                Rule::unique('custom_fields')->ignore($this->id) // Apply unique only for update
            ],
            'description' => 'nullable|string|regex:/^[a-zA-Z0-9_.()\[\] -]+$/',
            'field_type' => 'required|in:Textarea,Text,Dropdown List,Auto Complete Dropdown,Checkbox Group,Radio Buttons,Date,Time,Datetime Picker,File Upload,Heading,Hidden Input,Number,Paragraph',
            'required' => 'boolean',
            'use_as_filter' => 'boolean',
            'settings' => 'required|array',
        ];
        // Set specific rules based on the field type
        if ($this->field_type === 'Textarea' || $this->field_type === 'Text') {
            $rules['settings.field_template'] = 'required|string';
            $rules['settings.default_val'] = 'nullable|string';
            $rules['settings.min_length'] = 'required|integer|min:0';
            $rules['settings.max_length'] = 'required|integer|min:0';
            if($this->field_type === 'Textarea') {
                $rules['settings.rows'] = 'required|integer|min:1';
            }
        } else if ($this->field_type === 'Number Field') {
            $rules['settings.field_template'] = 'required|string';
            $rules['settings.default_val'] = 'nullable|numeric';
            $rules['settings.min'] = 'nullable|numeric';
            $rules['settings.max'] = 'nullable|numeric';
        } else if (in_array($this->field_type, ['Checkbox Group','Dropdown List', 'Dropdown List'])) {
            $rules['settings.field_template'] = 'required|string';
            $rules['settings.placeholder'] = 'nullable|string|regex:/^[a-zA-Z0-9_.()\[\] -]+$/';
            $rules['settings.default_val'] = [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    // Access the options data from the request
                    $options = request()->input('settings.options', []);
                    $cleanedOptions = array_filter(array_unique(array_map('trim', $options)));

                    // Clean default values
                    $cleanedDefaults = array_filter(array_unique(array_map('trim', $value)));

                    // Ensure all default values are in the options
                    foreach ($cleanedDefaults as $default) {
                        if (!in_array($default, $cleanedOptions)) {
                            $fail("The default value '{$default}' must be one of the provided options.");
                        }
                    }
                },
            ];
            $rules['settings.options'] = [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $cleanedOptions = array_map('trim', $value); // Trim all values

                    // Ensure all values are valid
                    foreach ($cleanedOptions as $option) {
                        if (!preg_match('/^[a-zA-Z0-9_.()\[\] -]+$/', $option)) {
                            $fail("Each option may only contain letters, numbers, spaces, underscores (_), dashes (-), parentheses (), and brackets [].");
                        }
                    }

                    // Check for duplicates (case-sensitive)
                    $duplicates = array_diff_assoc($cleanedOptions, array_unique($cleanedOptions));
                    if (!empty($duplicates)) {
                        $fail('Options must have unique values. Duplicate options are not allowed.');
                    }
                },
            ];
        }

        if ($this->field_type === 'Textarea' || $this->field_type === 'Text' ) {
            $rules['settings'] = [
                'required', 
                'array', 
                function ($attribute, $value, $fail) {
                    if (isset($value['max_length']) && isset($value['min_length']) && $value['max_length'] < $value['min_length']) {
                        $fail('The maximum length must be greater than or equal to the minimum length.');
                    }
                },
            ];
        }

        return $rules;
    }
    public function messages()
    {
        return [
            'name.regex' => 'Name may only contain letters, numbers, spaces, underscores (_), dashes (-), parentheses (), and brackets [].',
            'description.regex' => 'Name may only contain letters, numbers, spaces, underscores (_), dashes (-), parentheses (), and brackets [].',
            'settings.options.regex' => 'Each Option may only contain letters, numbers, spaces, underscores (_), dashes (-), parentheses (), and brackets [].',
            'settings.options.min' => 'At least one option must be provided.',
            'settings.placeholder.regex' => 'Placeholder may only contain letters, numbers, spaces, underscores (_), dashes (-), parentheses (), and brackets [].',
        ];
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function customFieldData()
    {
        return $this->hasMany(ServiceRequestCustomField::class, 'field_id');
    }

    // public function setUniqueOptions()
    // {
    //     if (isset($this->) && is_string($value['options'])) {
    //         // Split the options by new line, remove duplicates, trim whitespace
    //         $options = preg_split('/\r\n|\r|\n/', $value['options']);
    //         $cleanedOptions = array_filter(array_unique(array_map('trim', $options)));

    //         // Rebuild the options string
    //         $value['options'] = implode("\r\n", $cleanedOptions);
    //     }

    //     // Save the cleaned settings
    //     $this->attributes['settings'] = json_encode($value);
    // }

    public static function regenerateServiceRequestView()
    {
        $customFields = DB::table('custom_fields')->whereNot('field_type', 'File Upload')->get();

        $customFieldColumns = $customFields->map(function ($customField) {
            $escapedFieldId = addslashes($customField->id);
            $safeAlias = 'cf_' . $customField->id;

            $fieldType = $customField->field_type;
            
            $caseStatement = "MAX(CASE WHEN service_request_custom_fields.field_id = {$escapedFieldId} THEN service_request_custom_fields.value";
            
            if ($fieldType === 'Date') {
                $caseStatement .= "::date";
            } else if ($fieldType === 'Datetime Picker') {
                $caseStatement .= "::timestamp without time zone";
            } else if ($fieldType === 'Time') {
                $caseStatement .= "::time without time zone";
            }  else if ($fieldType === 'Number') {
               $caseStatement .= "::numeric";
            }
            
            $caseStatement .= " END) AS \"{$safeAlias}\"";

            return $caseStatement;
        })->join(",\n    ");

        DB::statement('DROP VIEW IF EXISTS service_request_view_temp');

        // Create the new temporary view
        $viewQuery = "
            CREATE VIEW service_request_view_temp AS
            SELECT
                service_requests.id,
                service_requests.service_domain_id,
                service_requests.service_id,
                service_requests.subject,
                service_requests.description,
                service_requests.status_id,
                service_requests.priority_id,
                service_requests.creator_group_id,
                service_requests.created_by,
                service_requests.updated_by,
                service_requests.executor_id,
                service_requests.executor_group_id,
                service_requests.sla_rule_id,
                service_requests.response_time,
                service_requests.tto,
                service_requests.ttr,
                service_requests.planned_start,
                service_requests.planned_end,
                service_requests.actual_execution_start,
                service_requests.actual_execution_end,
                service_requests.created_at,
                service_requests.updated_at,
                {$customFieldColumns}
            FROM
                service_requests
            LEFT JOIN
                service_request_custom_fields ON service_requests.id = service_request_custom_fields.service_request_id
            GROUP BY
                service_requests.id,
                service_requests.service_domain_id,
                service_requests.service_id,
                service_requests.subject,
                service_requests.description,
                service_requests.status_id,
                service_requests.priority_id,
                service_requests.creator_group_id,
                service_requests.created_by,
                service_requests.updated_by,
                service_requests.executor_id,
                service_requests.executor_group_id,
                service_requests.sla_rule_id,
                service_requests.response_time,
                service_requests.tto,
                service_requests.ttr,
                service_requests.planned_start,
                service_requests.planned_end,
                service_requests.actual_execution_start,
                service_requests.actual_execution_end,
                service_requests.created_at,
                service_requests.updated_at
            ";


        // Execute the query to create the view
        DB::statement($viewQuery);

        // Rename the current view and drop the backup
        DB::statement('ALTER VIEW IF EXISTS service_request_view RENAME TO service_request_view_backup');
        DB::statement('ALTER VIEW service_request_view_temp RENAME TO service_request_view');
        DB::statement('DROP VIEW IF EXISTS service_request_view_backup');
    }
}
