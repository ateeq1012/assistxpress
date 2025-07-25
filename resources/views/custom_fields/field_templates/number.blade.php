<input type="hidden" name="settings[field_template]" value="number">
<input type="hidden" name="field_type" value="Number">

<div class="form-group">
    <label for="name">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $custom_field->name) }}" required>
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
    <label for="min">Min/Max</label>
    <div class="row">
        <div class="col-6">
            <input type="number" name="settings[min]" class="form-control" value="{{ old('settings.min', $custom_field->settings['min'] ?? '') }}">
        </div>
        <div class="col-6">
            <input type="number" name="settings[max]" class="form-control" value="{{ old('settings.max', $custom_field->settings['max'] ?? '') }}">            
        </div>
    </div>
</div>

<div class="form-group">
    <label for="required">
        Required 
        <input type="hidden" name="required" value="0">
        <input type="checkbox" name="required" value="1" {{ $custom_field->required ? 'checked' : '' }}>
    </label>
</div>

<div class="form-group">
    <label for="use_as_filter">
        Use as Filter
        <input type="hidden" name="use_as_filter" value="0">
        <input type="checkbox" name="use_as_filter" value="1" {{ $custom_field->use_as_filter ? 'checked' : '' }} >
    </label>
</div>

<div class="form-group">
    <label for="default_val">Default Value</label>
    <input type="mumber" name="settings[default_val]" class="form-control" id="default_val" value = {{ old('settings.default_val', $custom_field->settings['default_val'] ?? '') }}>
</div>