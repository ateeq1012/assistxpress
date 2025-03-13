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
<link href="{{ asset('css/plugins/chosen/bootstrap-chosen.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/chosen/chosen.jquery.js') }}"></script>

<div class="ibox pt-2 col-6 container">
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
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="form-group">
            <label for="field_type">Select Field Type</label>
            <select name="field_type" class="form-control chosen-select" id="field_type" onchange="redirectToFieldType()">
                <option value="">Select a Field Type</option>
                <option value="text" @if(isset($field_type) && $field_type == "text") selected @endif> Text Field </option>
                <option value="number" @if(isset($field_type) && $field_type == "number") selected @endif> Number </option>
                <option value="checkboxes" @if(isset($field_type) && $field_type == "checkboxes") selected @endif> Checkbox Group </option>
                <option value="radios" @if(isset($field_type) && $field_type == "radios") selected @endif> Radio Buttons </option>
                <option value="dropdown" @if(isset($field_type) && $field_type == "dropdown") selected @endif> <i class="fa fa-list-ul"></i> Simple Dropdown </option>
                <option value="date" @if(isset($field_type) && $field_type == "date") selected @endif> Date </option>
                <option value="time" @if(isset($field_type) && $field_type == "time") selected @endif> Time </option>
                <option value="datetime" @if(isset($field_type) && $field_type == "datetime") selected @endif> Datetime </option>
                <option value="file" @if(isset($field_type) && $field_type == "file") selected @endif> File Upload </option>
                <option value="textarea" @if(isset($field_type) && $field_type == "textarea") selected @endif> Text Area </option>
                <option value="auto-complete" @if(isset($field_type) && $field_type == "auto-complete") selected @endif> Auto Complete Dropdown </option>
                <!-- <option value="hidden" @if(isset($field_type) && $field_type == "hidden") selected @endif> Hidden Input </option>
                <option value="paragraph" @if(isset($field_type) && $field_type == "paragraph") selected @endif> Paragraph </option>
                <option value="heading" @if(isset($field_type) && $field_type == "heading") selected @endif> Heading </option> -->
            </select>

        </div>
        @if(isset($field_type))
            @if(View::exists('custom_fields.field_templates.' . $field_type))
                <form id="cf-form" method="POST" action="{{ route('custom_fields.store') }}">
                    @csrf
                    @include('custom_fields.field_templates.' . $field_type)
                </form>
            @else
                <p>Custom field form could not be loaded.</p>
            @endif
        @endif
    </div>
    <div class="ibox-footer">
        @if(isset($field_type) && View::exists('custom_fields.field_templates.' . $field_type))
            <button id="submit-button" type="submit" class="btn btn-primary">Create Custom Field</button>
        @endif
    </div>
</div>
<script type="text/javascript">

    let submit = document.getElementById('submit-button');
    if(submit !== undefined || submit != null) {
        submit.addEventListener('click', function () {
            const form = document.getElementById('cf-form');
            if (form.checkValidity()) {
                Swal.fire({
                    title: "Saving...",
                    text: "Please wait",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading(); // Show the loading spinner
                    }
                });
                form.submit();
            } else {
                form.reportValidity();
            }
        });
    }

    function redirectToFieldType() {
        var selectedFieldType = document.getElementById('field_type').value; // Get the selected value
        var url = "{{ route('custom_fields.create') }}"; // Base URL for create route

        // Redirect to the create route with the selected field type as a query parameter
        window.location.href = url + '?field_type=' + selectedFieldType;
    }

    $(document).ready(function(){
        $('.chosen-select').chosen({width: "100%"});
    });


</script>

@endsection