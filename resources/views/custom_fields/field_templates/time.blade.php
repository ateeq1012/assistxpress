<input type="hidden" name="settings[field_template]" value="time">
<input type="hidden" name="field_type" value="Time">

<div class="form-group">
    <label for="name">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $custom_field->name) }}" required >
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" class="form-control" id="description" >{{ old('description', $custom_field->description) }}</textarea>
</div>

<div class="form-group">
    <label for="placeholder">Placeholder</label>
    <input type="text" name="settings[placeholder]" class="form-control" value="{{ old('settings.placeholder', $custom_field->settings['placeholder'] ?? 'HH:MM') }}" >
</div>

<!-- <div class="form-group">
    <label for="format">Time Format <span class="text-danger">*</span></label>
    <select name="settings[format]" class="form-control" id="format" required >
        <option value="24-hour" {{ old('settings.format', $custom_field->settings['format'] ?? '') === '24-hour' ? 'selected' : '' }}>24-Hour (HH:MM)</option>
        <option value="12-hour" {{ old('settings.format', $custom_field->settings['format'] ?? '') === '12-hour' ? 'selected' : '' }}>12-Hour (HH:MM AM/PM)</option>
    </select>
</div> -->

<div class="form-group">
    <label for="min_time">Minimum Time</label>
    <input type="time" name="settings[min_time]" class="form-control" value="{{ old('settings.min_time', $custom_field->settings['min_time'] ?? '') }}" >
</div>

<div class="form-group">
    <label for="max_time">Maximum Time</label>
    <input type="time" name="settings[max_time]" class="form-control" value="{{ old('settings.max_time', $custom_field->settings['max_time'] ?? '') }}" >
</div>

<div class="form-group">
    <label for="required">
        Required 
        <input type="hidden" name="required" value="0">
        <input type="checkbox" name="required" value="1" {{ $custom_field->required ? 'checked' : '' }} >
    </label>
</div>

<div class="form-group">
    <label for="default_val">Default Time</label>
    <input type="time" name="settings[default_val]" class="form-control" value="{{ old('settings.default_val', $custom_field->settings['default_val'] ?? '') }}" >
</div>
