@extends('layouts.app')

@section('content')
<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
    #color {
        border-radius: 3px;
    }
</style>
<script src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/plugins/form-builder/form-builder.min.js') }}"></script>
<script src="{{ asset('js/plugins/form-builder/form-render.min.js') }}"></script>

<div class="ibox pt-2 col-8 container">
    <div class="ibox-title">
        <h5>Create Custom Field</h5>
        <div class="ibox-tools">
            <a href="{{ route('custom_fields.index') }}" class="btn btn-primary btn-xs">Manage Custom Fields</a>
        </div>
    </div>
    <div class="ibox-content">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('error') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('warning') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div id="build-wrap"></div>
        @include('custom_fields.field_templates.textarea', [
            'field' => 'name',
            'label' => 'Field Name',
            'type' => 'text',
            'value' => old('name')
        ])

        <form action="{{ route('custom_fields.store') }}" method="POST">
             @csrf
            <input type="hidden" id="fields_settings" name="fields_settings" value="">
            <button type="submit" class="btn btn-primary" id="save">Save</button>
            <a href="{{ route('custom_fields.create') }}" class="btn btn-warning">Reset</a>

        </form>
    </div>
</div>
<script>
    const sanitizerOptions = {
        clobberingProtection: {
            document: true,
            form: false, //Set true for FormRender
        },
        backendOrder: ['dompurify','sanitizer','fallback'],
    };

    var options = {
        lang: false,
        editOnAdd: true,
        disabledActionButtons: ['clear', 'data', 'save'],
        disabledAttrs: ['access', 'className', 'description', 'name', 'value', 'multiple'],
        disableFields: ['button', 'hidden'],
        disabledSubtypes: {
            textarea: ['tinymce'],
        },
        // allowStageSort: false,
        disabledFieldButtons: {
            textarea: ['remove', 'edit', 'copy'],
            text: ['remove', 'edit', 'copy'],
            date: ['remove', 'edit', 'copy'],
            autocomplete: ['remove', 'edit', 'copy'],
            select: ['remove', 'edit', 'copy'],
            'checkbox-group': ['remove', 'edit', 'copy'],
            'radio-group': ['remove', 'edit', 'copy'],
            file: ['remove', 'edit', 'copy'],
            number: ['remove', 'edit', 'copy'],
            email: ['remove', 'edit', 'copy'],
        },
        controlOrder: [
            'textarea',
            'text',
            'date',
            'autocomplete',
            'select',
            'checkbox-group',
            'radio-group',
            'file',
            'number',
            'email',
        ],
        // fields: [{
        //     label: "Email",
        //     type: "text",
        //     subtype: "email",
        //     icon: "✉"
        // },{
        //     label: "System Users",
        //     type: "autocomplete",
        //     subtype: "autocomplete",
        //     id: "system-users",
        //     disabledFieldButtons: ['edit'],
        //     disabledAttrs: ['options'],
        //     // disableFields: ['button', 'hidden'],
        // }],
        onAddField: function(fieldId, fieldData) {
            setTimeout(() => {
                let parts = fieldId.split('-');
                let lastNumber = parseInt(parts[parts.length - 1]);
                let incrementedNumber = lastNumber + 1;
                parts[parts.length - 1] = incrementedNumber;
                let newFieldId = parts.join('-');
                console.log(newFieldId);
                console.log(fieldData);

                const fieldSettings = $(`#${newFieldId}-holder`);
                const fieldOptions = fieldSettings.find('div.form-elements').first();
                console.log(fieldSettings);

                const customCheckboxHtml = `
                    <div class="form-group for_all_task_types-wrap">
                        <label for="for_all_task_types-${newFieldId}">Use in all Projects</label>
                        <div class="input-wrap">
                            <input type="checkbox" class="fld-for_all_task_types" name="for_all_task_types" id="for_all_task_types-${newFieldId}">
                        </div>
                    </div>
                    <div class="form-group label-wrap" style="display: block">
                        <label for="field-description-${newFieldId}">Field Description</label>
                        <div class="input-wrap">
                            <div name="field-description" placeholder="Label" class="fld-label form-control" id="field-description-${newFieldId}" contenteditable="true"></div>
                        </div>
                    </div>
                `;

                // Append the custom checkbox to the field settings
                fieldOptions.prepend(customCheckboxHtml);
            }, 100);
            $('.form-wrap.form-builder .cb-wrap.sticky-controls').hide();
        },
        onClearAll: function() {
            $('.form-wrap.form-builder .cb-wrap.sticky-controls').show();
            alert('removed');
        },
        onRemoveField: function() {
            alert('removed');
            $('.form-wrap.form-builder .cb-wrap.sticky-controls').show();
        }
    };

    $(document).ready(function() {

        const fbEditor = document.getElementById("build-wrap");
        const formBuilder = $(fbEditor).formBuilder(options);
        
        $('#save').click(function(event) {
            event.preventDefault(); // Prevent default submission
            const formData = formBuilder.actions.getData('json'); // Get form data
            const strData = JSON.stringify(formData); // Convert to JSON string
            document.getElementById('fields_settings').value = formData; // Set hidden input value
            this.closest('form').submit(); // Submit the form
        });
    });
</script>

@endsection