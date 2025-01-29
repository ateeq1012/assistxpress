<input type="hidden" name="settings[field_template]" value="date">
<input type="hidden" name="field_type" value="Date">

<div class="form-group">
    <label for="name">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $custom_field->name) }}" required >
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" class="form-control" id="description">{{ old('description', $custom_field->description) }}</textarea>
</div>

<div class="form-group">
    <label for="placeholder">Placeholder</label>
    <input type="text" name="settings[placeholder]" class="form-control" value="{{ old('settings.placeholder', $custom_field->settings['placeholder'] ?? '') }}">
</div>

<div class="form-group">
    <label for="min_date">Min/Max Date <span class="text-danger">*</span></label>
    <div class="row">
        <div class="col-6">
            <input type="date" name="settings[min_date]" class="form-control" value="{{ old('settings.min_date', $custom_field->settings['min_date'] ?? '') }}">
        </div>
        <div class="col-6">
            <input type="date" name="settings[max_date]" class="form-control" value="{{ old('settings.max_date', $custom_field->settings['max_date'] ?? '') }}">
        </div>
    </div>
</div>

<div class="form-group">
    <label for="required">
        Required 
        <input type="hidden" name="required" value="0">
        <input type="checkbox" name="required" value="1" {{ $custom_field->required ? 'checked' : '' }} >
    </label>
</div>

<div class="form-group">
    <label for="default_val">Default Value</label>
    <input type="date" name="settings[default_val]" class="form-control" value="{{ old('settings.default_val', $custom_field->settings['default_val'] ?? '') }}">
</div>
