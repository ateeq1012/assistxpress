
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
<input type="hidden" name="settings[field_template]" value="dropdown">
<input type="hidden" name="field_type" value="Dropdown List">

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
    document.addEventListener("DOMContentLoaded", function() {
        const optionsContainer = document.getElementById("options-container");
        const addOptionButton = document.getElementById("add-option");

        // Function to ensure only one checkbox is checked
        function enforceSingleDefault(checkbox) {
            const allCheckboxes = optionsContainer.querySelectorAll("input[type='checkbox']");
            allCheckboxes.forEach(function(cb) {
                if (cb !== checkbox) {
                    cb.checked = false;
                }
            });
        }

        // Add a new option dynamically
        addOptionButton.addEventListener("click", function() {
            const optionItem = document.createElement("div");
            optionItem.className = "option-item form-group mb-1";
            optionItem.innerHTML = `
                <input type="text" name="settings[options][]" class="form-control d-inline-block w-75" placeholder="Enter option">
                <input type="checkbox" name="settings[default_val][]" value="">
                <button type="button" class="btn btn-danger btn-sm remove-option">Remove</button>
            `;
            optionsContainer.appendChild(optionItem);

            const textInput = optionItem.querySelector("input[type='text']");
            const checkbox = optionItem.querySelector("input[type='checkbox']");
            const removeButton = optionItem.querySelector(".remove-option");

            // Update checkbox value dynamically
            textInput.addEventListener("input", function() {
                checkbox.value = this.value;
            });

            // Ensure only one checkbox is checked
            checkbox.addEventListener("change", function() {
                if (this.checked) {
                    enforceSingleDefault(this);
                }
            });

            // Remove button functionality
            removeButton.addEventListener("click", function() {
                optionsContainer.removeChild(optionItem);
            });
        });

        // Attach event listeners to existing "Remove" buttons and checkboxes
        document.querySelectorAll(".remove-option").forEach(function(button) {
            button.addEventListener("click", function() {
                const optionItem = this.closest(".option-item");
                optionsContainer.removeChild(optionItem);
            });
        });

        document.querySelectorAll("#options-container .option-item input[type='checkbox']").forEach(function(checkbox) {
            checkbox.addEventListener("change", function() {
                if (this.checked) {
                    enforceSingleDefault(this);
                }
            });
        });

        // Update checkbox values dynamically for preloaded options
        document.querySelectorAll("#options-container .option-item input[type='text']").forEach(function(input) {
            input.addEventListener("input", function() {
                const checkbox = input.closest(".option-item").querySelector("input[type='checkbox']");
                checkbox.value = this.value;
            });
        });
    });
</script>
