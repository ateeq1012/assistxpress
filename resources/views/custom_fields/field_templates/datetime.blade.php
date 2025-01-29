<input type="hidden" name="settings[field_template]" value="datetime">
<input type="hidden" name="field_type" value="Datetime Picker">

<div class="form-group">
    <label for="name">Name <span class="text-danger">*</span></label>
    <input 
        type="text" 
        name="name" 
        class="form-control" 
        value="{{ old('name', $custom_field->name) }}" 
        required
    >
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea 
        name="description" 
        class="form-control"
    >{{ old('description', $custom_field->description) }}</textarea>
</div>

<div class="form-group">
    <label for="date-format">Date Format <span class="text-danger">*</span></label>
    <select 
        name="settings[date_format]" 
        class="form-control" 
        id="date-format" 
        required
    >
        <option value="">Select Date Format</option>
        <option value="Y-m-d" {{ old('settings.date_format', $custom_field->settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
        <option value="d/m/Y" {{ old('settings.date_format', $custom_field->settings['date_format'] ?? '') == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
        <option value="m-d-Y" {{ old('settings.date_format', $custom_field->settings['date_format'] ?? '') == 'm-d-Y' ? 'selected' : '' }}>MM-DD-YYYY</option>
    </select>
</div>

<div class="form-group">
    <label for="time-format">Time Format <span class="text-danger">*</span></label>
    <select 
        name="settings[time_format]" 
        class="form-control" 
        id="time-format" 
        required
    >
        <option value="">Select Time Format</option>
        <option value="H:i" {{ old('settings.time_format', $custom_field->settings['time_format'] ?? '') == 'H:i' ? 'selected' : '' }}>24-hour (HH:mm)</option>
        <option value="h:i A" {{ old('settings.time_format', $custom_field->settings['time_format'] ?? '') == 'h:i A' ? 'selected' : '' }}>12-hour (hh:mm AM/PM)</option>
    </select>
</div>

<div class="form-group">
    <label for="default-value">Default Value</label>
    <input 
        type="datetime-local" 
        name="settings[default_value]" 
        class="form-control" 
        value="{{ old('settings.default_value', $custom_field->settings['default_value'] ?? '') }}"
    >
    <small class="form-text text-muted">Optional: Set a default date and time.</small>
</div>

<div class="form-group">
    <label for="required">
        Required 
        <input type="hidden" name="required" value="0">
        <input 
            type="checkbox" 
            name="required" 
            value="1" 
            {{ old('required', $custom_field->required) ? 'checked' : '' }}
        >
    </label>
</div>