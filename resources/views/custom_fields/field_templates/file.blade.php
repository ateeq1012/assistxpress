<input type="hidden" name="settings[field_template]" value="file">
<input type="hidden" name="field_type" value="File Upload">

<div class="form-group">
    <label for="name">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $custom_field->name) }}" required >
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" class="form-control" >{{ old('description', $custom_field->description) }}</textarea>
</div>

<div class="form-group">
    <label for="file_types">Allowed File Types <span class="text-danger">*</span></label>
    <input type="text" name="settings[allowed_file_types]" class="form-control" placeholder="e.g., jpg, png, pdf" value="{{ old('settings.allowed_file_types', $custom_field->settings['allowed_file_types'] ?? '') }}" required >
    <small class="form-text text-muted">Specify allowed file extensions, separated by commas.</small>
</div>

<div class="form-group">
    <label for="max_file_size">Maximum File Size (MB)</label>
    <input type="number" name="settings[max_file_size]" class="form-control" placeholder="e.g., 2" value="{{ old('settings.max_file_size', $custom_field->settings['max_file_size'] ?? '') }}" >
    <small class="form-text text-muted">Leave empty for no limit.</small>
</div>
<div class="form-group">
    <label for="allow_multiple">Allow Multiple Files</label>
    <select name="settings[allow_multiple]" class="form-control" id="allow-multiple" required >
        <option value="yes" {{ old('settings.allow_multiple', $custom_field->settings['allow_multiple'] ?? '') === 'yes' ? 'selected' : '' }} > Yes </option>
        <option value="no" {{ old('settings.allow_multiple', $custom_field->settings['allow_multiple'] ?? '') === 'no' ? 'selected' : '' }} > No </option>
    </select>
</div>

<div class="form-group">
    <label for="required">
        Required 
        <input type="hidden" name="required" value="0">
        <input type="checkbox" name="required" value="1" {{ old('required', $custom_field->required) ? 'checked' : '' }} >
    </label>
</div>
