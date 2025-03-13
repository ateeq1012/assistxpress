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
        <h5>Edit Custom Field</h5>
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
            <select name="field_type" class="form-control chosen-select" id="field_type" disabled>
                <option value="">{{$custom_field->field_type}}</option>
            </select>
        </div>
        @if(isset($field_template))
            @if(View::exists('custom_fields.field_templates.' . $field_template))
                <form id="cf-form" action="{{ route('custom_fields.update', $custom_field->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('custom_fields.field_templates.' . $field_template, ['custom_field' => $custom_field])

                </form>
            @else
                <p>Custom field form could not be loaded.</p>
            @endif
        @endif
    </div>
    <div class="ibox-footer">
        @if(isset($field_template) && View::exists('custom_fields.field_templates.' . $field_template))
            <button id="submit-button" type="submit" class="btn btn-primary">Update Custom Field</button>
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
    
</script>

@endsection