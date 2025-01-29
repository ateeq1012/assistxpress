<style type="text/css">
    input[type="checkbox"] {
        transform: scale(1.2);
    }
    .option-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        width: 100%; /* Ensure full width of the parent container */
    }
    .option-item .form-control {
        flex: 1; /* Allow the input field to take up the remaining width */
    }
    .option-item .btn-danger {
        flex-shrink: 0; /* Prevent the button from resizing */
    }
</style>
<input type="hidden" name="settings[field_template]" value="checkboxes">
<input type="hidden" name="field_type" value="Checkbox Group">

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
    <label>Options (add options below)</label>
    <div id="options-container" class="form-group">
        @if(isset($custom_field->settings['options']))
            @foreach($custom_field->settings['options'] as $index => $option)
                <div class="option-item form-group mb-1">
                    <input type="text" name="settings[options][{{$index}}]" class="form-control" value="{{ trim($option) }}">
                    <input type="checkbox" name="settings[default_val][{{$index}}]" value="{{ trim($option) }}" 
                        {{ isset($custom_field->settings['default_val']) && in_array(trim($option), $custom_field->settings['default_val']) ? 'checked' : '' }}>
                    <button type="button" class="btn btn-danger btn-sm remove-option">Remove</button>
                </div>
            @endforeach
        @else
            <div class="option-item form-group mb-1">
                <input type="text" name="settings[options][1]" class="form-control" placeholder="Enter option" value="Option 1">
                <input type="checkbox" name="settings[default_val][1]" value="Option 1">
                <button type="button" class="btn btn-danger btn-sm remove-option">Remove</button>
            </div>
        @endif
    </div>
    <button type="button" class="btn btn-primary btn-sm mt-0" id="add-option">+ Add Option</button>
</div>
<hr>
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
        <input type="checkbox" name="use_as_filter" value="1" {{ $custom_field->use_as_filter ? 'checked' : '' }}>
    </label>
</div>
<script>
    let opt_id = 1; // Default value for opt_id

    @php
        if (isset($custom_field->settings['options']) && is_array($custom_field->settings['options'])) {
            $option_keys = array_keys($custom_field->settings['options']);
            $max_key = max($option_keys);
            echo "opt_id = $max_key;";
        }
    @endphp

    document.addEventListener("DOMContentLoaded", function() {
        const optionsContainer = document.getElementById("options-container");
        const addOptionButton = document.getElementById("add-option");

        addOptionButton.addEventListener("click", function() {
            const optionItem = document.createElement("div");
            optionItem.className = "option-item form-group mb-1";
            optionItem.innerHTML = `
                <input type="text" name="settings[options][${opt_id+1}]" class="form-control d-inline-block w-75" placeholder="Enter option">
                <input type="checkbox" name="settings[default_val][${opt_id+1}]" value="">
                <button type="button" class="btn btn-danger btn-sm remove-option">Remove</button>
            `;
            optionsContainer.appendChild(optionItem);

            // Update checkbox value dynamically
            optionItem.querySelector("input[type='text']").addEventListener("input", function() {
                optionItem.querySelector("input[type='checkbox']").value = this.value;
            });

            // Remove button functionality
            optionItem.querySelector(".remove-option").addEventListener("click", function() {
                optionsContainer.removeChild(optionItem);
            });
            opt_id++;
        });

        // Attach event listeners to existing "Remove" buttons
        document.querySelectorAll(".remove-option").forEach(function(button) {
            button.addEventListener("click", function() {
                const optionItem = this.closest(".option-item");
                optionsContainer.removeChild(optionItem);
            });
        });

        // Update checkbox values dynamically for preloaded options
        document.querySelectorAll("#options-container .option-item input[type='text']").forEach(function(input) {
            input.addEventListener("input", function() {
                const checkbox = input.closest(".option-item").querySelector("input[type='checkbox']");
                checkbox.value = input.value;
            });
        });
    });
</script>