<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomFieldRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return (new \App\Models\CustomField)->rules();
    }

    protected function prepareForValidation()
    {
        $this->merge(['field_id' => 'tmp'.time()]);
        $settings = $this->input('settings', []);
        // Clean up options
        if (isset($settings['options'])) {
            $options = preg_split('/\r\n|\r|\n/', $settings['options']); // Split by newlines
            $options = array_filter(array_unique(array_map('trim', $options))); // Remove duplicates and empty strings
            $settings['options'] = implode("\r\n", $options); // Join back with newline
        }

        // Clean up default values
        if (isset($settings['default_val'])) {
            $defaultValues = preg_split('/\r\n|\r|\n/', $settings['default_val']); // Split by newlines
            $defaultValues = array_filter(array_unique(array_map('trim', $defaultValues))); // Remove duplicates and empty strings
            $settings['default_val'] = implode("\r\n", $defaultValues); // Join back with newline
        }

        $this->merge(['settings' => $settings]);
    }
}

