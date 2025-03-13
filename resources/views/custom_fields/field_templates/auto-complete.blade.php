<input type="hidden" name="settings[field_template]" value="auto-complete">
<input type="hidden" name="field_type" value="Auto Complete Dropdown">

<div class="form-group">
    <label for="name">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $custom_field->name) }}" required >
</div>

<div class="form-group">
    <label for="description">Description</label>
    <textarea name="description" class="form-control" >{{ old('description', $custom_field->description) }}</textarea>
</div>

<div class="form-group">
    <label for="options">Option Source <span class="text-danger">*</span></label>
    <select name="settings[option_source]" class="form-control" id="options" required >
        <option value="">Select Option Source</option>
        <option value="users" {{ old('settings.option_source', $custom_field->settings['option_source'] ?? '') === 'users' ? 'selected' : '' }} > All System Users </option>
        <option value="creator-group-users" {{ old('settings.option_source', $custom_field->settings['option_source'] ?? '') === 'creator-group-users' ? 'selected' : '' }} > Users from Request Creator's Group </option>
        <option value="executor-group-users" {{ old('settings.option_source', $custom_field->settings['option_source'] ?? '') === 'executor-group-users' ? 'selected' : '' }} > Users from Request Executor's Group </option>
        <option value="service_domains" {{ old('settings.option_source', $custom_field->settings['option_source'] ?? '') === 'service_domains' ? 'selected' : '' }} > Service Domains </option>
    </select>
</div>

<div class="form-group">
    <label for="allow-multiple">Allow Multiple Selection</label>
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

<!-- <div class="form-group">
    <label for="for_all_services">
        Use in all Service Domains 
        <input type="hidden" name="for_all_services" value="0">
        <input 
            type="checkbox" 
            name="for_all_services" 
            value="1" 
            {{ old('for_all_services', $custom_field->for_all_services) ? 'checked' : '' }}
        >
    </label>
</div> -->
