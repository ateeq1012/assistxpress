<?php
	use App\Helpers\GeneralHelper;
?>
@extends('layouts.app')

@section('content')

<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>

<style type="text/css">
	td {
		padding: 3px 8px !important;
	}
	#color {
		border-radius: 3px;
	}
	.select2-results__option[aria-selected=true] {
		display: none;
	}
	.select2-container--default .select2-selection--single {
		border: 1px solid #e5e6e7;
		border-radius: 1px;
	}
	input[type="checkbox"] {
		transform: scale(1.2);
	}
	input[type="radio"] {
		transform: scale(1.2);
	}
	.form-check {
		padding: 0px 34px;
	}
	.form-group {
		/*box-shadow: #e7eaec 0px 2px 8px;*/
		padding: 5px 6px;
		margin-bottom: 4px;
		/*background: #FFFFFF;*/
		border-radius: 5px;
	}
	label {
		margin-bottom: 2px;
	}
	.form-control::placeholder {
		color: #6c757d;
		font-size: 13px;
	}
	.remove-file {
		position: relative;
		right: 16px;
		z-index: 1;
	}

</style>
<div class="ibox pt-2">
	<div class="ibox-title">
		<h5>Update Task:{{$task->id}}</h5>
		<div class="ibox-tools">
			<a href="{{ route('tasks.index') }}" class="btn btn-primary btn-xs">Manage Tasks</a>
		</div>
	</div>
	<div id="form-wrapper" class="ibox-content" style="height:calc(100vh - 180px); overflow-y:scroll; /*background: #eceff1;*/">
		@if (session('error'))
			<div class="alert alert-danger alert-dismissable">
				<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
				{{ session('error') }}
			</div>
		@endif
		@if ($errors->any())
			<div class="alert alert-danger alert-dismissable">
				<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
				<ul>
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif

		<div id='ajax-errors'></div>

		<form id="task-form"  action="{{ route('tasks.update', $task->id) }}" method="POST">
			@csrf
			@method('PUT')
			<div class="form-group">
				<label for="project_id">Project</label>
				<select name="project_id" class="form-control select2-field" id="project_id" required >
					<option value="">Select Task Type</option>
					@foreach($projects as $project)
						<option value="{{ $project->id }}" {{ ((old('project_id') == $project->id) || (isset($task) && $task->project_id == $project->id)) ? 'selected' : '' }}>
							{{ $project->name }}
						</option>
					@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="task_type">Task Type</label>
				<select name="task_type" class="form-control select2-field" id="task_type" required >
					<!-- <option value="">Select Task Type</option> -->
					@foreach($task_types as $task_type)
						<option value="{{ $task_type->id }}" {{ ((old('task_type') == $task_type->id) || (isset($task) && $task->task_type_id == $task_type->id)) ? 'selected' : '' }}>
							{{ $task_type->name }}
						</option>
					@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="subject">Subject<span class="text-danger">*</span></label>
				<!-- <input type="text" name="subject" class="form-control" id="subject" value="{{ old('subject', $task->subject ?? '') }}"> -->
				<input type="text" name="subject" class="form-control" id="subject" value="{{ old('subject', $task->subject) }}" />

			</div>

			<div class="form-group">
				<label for="description">Description</label>
				<textarea name="description" class="form-control" id="description">{{ old('description', $task->description ?? '') }}</textarea>
			</div>

			<div class="form-group">
				<label for="status_id">Status<span class="text-danger">*</span></label>
				<select name="status_id" class="form-control" id="status_id">
					<option value="">Select an option</option>
				</select>
			</div>

			<div class="form-group">
				<label for="priority_id">Priority<span class="text-danger">*</span></label>
				<select name="priority_id" class="form-control" id="priority_id">
					<option value="">Select an option</option>
					@foreach($priorities as $priority)
						<option value="{{ $priority->id }}" style="font-size:unset; background-color:{{ $priority->color }}; color:{{ GeneralHelper::invert_color($priority->color) }}" {{ old('priority_id') == $priority->id ? 'selected' : '' }}>
							{{ $priority->name }}
						</option>
					@endforeach
				</select>

			</div>

			<div id="custom-fields-container"></div>

			<hr>

			@if(count($creator_groups) > 1)
				<div class="form-group">
					<label for="creator_group_id">Creator Group<span class="text-danger">*</span></label>
					<select name="creator_group_id" class="form-control select2-field" id="creator_group_id" required>
						<option value="">Select an option</option>
						@foreach($creator_groups as $gid => $gname)
							<option value="{{$gid}}" {{ old('creator_group_id') == $gid ? 'selected' : '' }}>{{$gname}}</option>
						@endforeach
					</select>
				</div>
			@endif
			<div class="form-group">
				<label for="executor_group_id">Assignee Group<span class="text-danger">*</span></label>
				<select name="executor_group_id" class="form-control select2-field" id="executor_group_id">
					<option value="">Select an option</option>
					@foreach($all_groups as $gid => $gname)
						<option value="{{$gid}}" {{ old('executor_group_id') == $gid ? 'selected' : '' }}>{{$gname}}</option>
					@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="executor_id">Assignee<span class="text-danger">*</span></label>
				<select name="executor_id" class="form-control select2-field" id="executor_id">
					<option value="">Select an option</option>
				</select>
			</div>
		</form>
	</div>
	<div class="ibox-footer">
		<button id="submit-button" type="submit" class="btn btn-primary">Update</button>
	</div>
</div>

<script>
	document.getElementById('submit-button').addEventListener('click', function () {
		const form = document.getElementById('task-form');
		if (form.checkValidity()) {
			const formData = new FormData(form);

			Swal.fire({
				title: "Saving...",
				text: "Please wait",
				allowOutsideClick: false,
				allowEscapeKey: false,
				didOpen: () => {
					Swal.showLoading();
				}
			});

			fetch(form.action, {
				method: 'POST',
				headers: {
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
				},
				body: formData,
			})
				.then(async (response) => {
					Swal.close();

					if (response.status === 422) {
						// Validation error handling
						const errorData = await response.json();
						const errorMessages = Object.values(errorData.errors)
							.map(errorArray => errorArray.join(' '))
							.join('</li>\n<li>');

						$('#ajax-errors').html(`
							<div class="alert alert-danger alert-dismissable">
								<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
								<ul><li>${errorMessages}</li></ul>
							</div>
						`);

						$('#form-wrapper').animate({ scrollTop: 0 }, 'fast');

						Swal.fire({
							title: 'Errors were found, in submitted data.',
							icon: 'warning',
						});

					} else {
						Swal.fire({
							title: 'Error Saving Task.',
							icon: 'warning',
						});

					}

					return response.json();
				})
				.then(data => {
					if (data.success) {
						Swal.fire({
							title: 'Success!',
							text: data.message || 'Task Created Successfully.',
							icon: 'success',
							showConfirmButton: false,
							timer: 1000,
							timerProgressBar: true
						}).then(() => {
							window.location.href = '{{ route('tasks.index') }}';
						});

					} else {
						Swal.fire({
							title: 'Error',
							text: data.message || 'Something went wrong.',
							icon: 'error',
						});
					}
				})
				.catch(error => {
					console.error('Error:', error);
					Swal.fire({
						title: 'Error',
						text: 'An error occurred while submitting the form. Please try again.',
						icon: 'error',
					});
				});
		} else {
			form.reportValidity();
		}
	});

	let old_vals = {};
	let old_val_are = 'stored_vals';
	@foreach ($task->toArray() as $key => $value)
		old_vals['{{ $key }}'] = @json($value);
	@endforeach

	$(document).ready(function () {
		$('.select2-field').select2({
			placeholder: "Select",
			allowClear: true,
			width: "100%",
			language: {
				noResults: function () {
					return "No options found";
				}
			}
		});
		let selectedProject = '{{ old('project_id', $task->project_id ?? '') }}';
		if (selectedProject) {
		    $('#project_id').val(selectedProject).trigger('change');
		}
		let selectedTaskType = '{{ old('task_type', $task->task_type_id ?? '') }}';
		// if (selectedTaskType) {
		//     $('#task_type').val(selectedTaskType).trigger('change');
		// }
	});

	function setFormTempData () {
		const formData = $('#task-form').serializeArray();
		const objData = {};

		formData.forEach(item => {
			const name = item.name;
			const value = item.value;

			if (name != '_method' && name != '_token') {
				const match = name.match(/^(.+)\[(\d+)\]$/);
				if (match) {
					const baseName = match[1];
					const index = match[2];

					if (!objData[baseName]) {
						objData[baseName] = {};
					}
					objData[baseName][index] = value;
				} else {
					objData[name] = value;
				}
			}
		});

		old_vals = objData;
	}

	$('#project_id').change(function() {
		let selectedStatus = $('#status_id').val();
		get_fields('project_id', function () {
	        $('#task_type').trigger('change');
	    });
	});

	$('#task_type').change(function() {
		// setFormTempData();
		if($('#task_type').val() != '' && $('#task_type').val() != null) {
			get_fields('task_type');
		}
	});


	function get_fields (changed_field, callback) {

		let selected_task_type = $('#'+changed_field).val();
		if (selected_task_type !== 'undefined' && selected_task_type != '') {
			let swal_exists = $('.swal2-container').length > 0;
			if (!swal_exists) {
				Swal.fire({
					text: "Loading...",
					// icon: 'info',
					allowOutsideClick: false,
					allowEscapeKey: false,
					didOpen: () => {
						Swal.showLoading(); // Show the loading spinner
					}
				});
			}

			$.ajax({
				url: '{{ route("tasks.get_fields") }}',
				type: 'POST',
				data: {
					_token: '{{ csrf_token() }}',
					changed_field: changed_field,
					mode: 'edit',
					id: selected_task_type,
					task_id : {{$task->id}},
				},
				success: function(response) {
					if(response.success) {
						if(changed_field == 'project_id') {
							updateTaskTypeDropdown(response.data.task_types);
						} else if (changed_field == 'task_type') {	
							updateSystemSelect2Fields();

							if(typeof response.data.allowed_statuses !== 'undefined' && Object.keys(response.data.allowed_statuses).length > 0) {
								updateStatusDropdown(response.data.allowed_statuses);
							}
							else
							{
								let statusDropdown = $('#status_id');
								statusDropdown.empty();
								Swal.fire({title: "No statuses were allowed", text: "Please check workflow engine", icon: "error" });
							}

							if (typeof response.data.custom_fields !== 'undefined' && Object.keys(response.data.custom_fields).length > 0) {
								updateCustomFields(response.data.custom_fields, response.data.task_files);
							}
							else
							{
								let fieldsContainer = $('#custom-fields-container');
								fieldsContainer.empty();
							}
						}

					} else {
						Swal.fire({title: "Something went wrong", text: "Please try again", icon: "error" });
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX Error:', error);
					let errorMessage = "An error occurred";

					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					}
					Swal.fire({title: "Error loading data", text: errorMessage, icon: "warning" });
				},
				complete: function(xhr) {
					
					let closeSwal = true;
					let callCallback = true;
					let response = xhr.responseJSON;
					if(changed_field == 'project_id' && (!response || typeof response.data.task_types === 'undefined' || Object.keys(response.data.task_types).length === 0)) {
						let closeSwal = false;
						let callCallback = false;
						Swal.fire({title: "", text: "No active Task Types found under selected Project", icon: "warning" });
						$('#task_type').prop('disabled', true).select2();
					}
					if(changed_field == 'task_type' && (!response || typeof response.data.allowed_statuses === 'undefined' || Object.keys(response.data.allowed_statuses).length === 0)) {
						let closeSwal = false;
						let callCallback = false;
						Swal.fire({title: "", text: "No statuses wer allowed, Please check the Workflow against the selected Task Type", icon: "warning" });
						$('#status_id').prop('disabled', true).select2();
					}
		            if (typeof callback === 'function' && callCallback) {
		                callback();
		            } else if (closeSwal) {
		            	// $('.swal2-container').hide();
		            	Swal.close();
		            }
				}
			});

		} else {
			$('#custom-fields-container').empty();
		}
	}

	function updateSystemSelect2Fields() {
		if(Object.keys(old_vals).length > 0) {
			priority_id = old_vals['priority_id'] !== undefined && old_vals['priority_id'] !== null ? old_vals['priority_id'] : '';
			if (priority_id) {
				$('#priority_id').val(priority_id).trigger('change');
			}

			creator_group_id = old_vals['creator_group_id'] !== undefined && old_vals['creator_group_id'] !== null ? old_vals['creator_group_id'] : '';
			if (creator_group_id) {
				$('#creator_group_id').val(creator_group_id).trigger('change');
			}

			executor_group_id = old_vals['executor_group_id'] !== undefined && old_vals['executor_group_id'] !== null ? old_vals['executor_group_id'] : '';
			if (executor_group_id) {
				$('#executor_group_id').val(executor_group_id).trigger('change');
			}

			executor_id = old_vals['executor_id'] !== undefined && old_vals['executor_id'] !== null ? old_vals['executor_id'] : '';
			if (executor_id) {
				$('#executor_id').val(executor_id).trigger('change');
			}
		}
	}

	function updateTaskTypeDropdown(taskTypeOptions) {
		let taskTypeDropdown = $('#task_type');
		taskTypeDropdown.empty();

		if (taskTypeOptions.length === 1) {
			let singleStatus = taskTypeOptions[0];

			let backgroundColor = singleStatus.color || '#ffffff';
			let textColor = singleStatus.text_color || '#000000';

			let optionHtml = 
				'<option value="' + singleStatus.id + '" selected style="font-size:unset; background-color:' +
				backgroundColor + '; color:' + textColor + ';">' + singleStatus.name + '</option>';

			taskTypeDropdown.append(optionHtml);
			taskTypeDropdown.prop('disabled', false).select2();
			// $('#task_type').trigger('change');
			return;
		}

		taskTypeDropdown.append('<option value="" disabled selected>Select an option</option>' );

		taskTypeOptions.forEach(function (taskType) {
			let textColor = taskType.text_color || '#000000';
			let selected = (typeof old_vals['task_type_id'] !== 'undefined' && old_vals['task_type_id'] == taskType.id) ? 'selected' : '';

			taskTypeDropdown.append(
				`<option value="${taskType.id}"
				 style="font-size:unset; background-color:${taskType.color} 
				; color:${textColor};" selected >${taskType.name}</option>`
			);
		});

		taskTypeDropdown.prop('disabled', false).select2();
	}

	function updateStatusDropdown(allowedStatuses) {
		let statusDropdown = $('#status_id');
		let selectedStatus = old_vals['status_id'];
		statusDropdown.empty();

		if (allowedStatuses.length === 1) {
			let singleStatus = allowedStatuses[0];

			let backgroundColor = singleStatus.color || '#ffffff'; // Default to white
			let textColor = singleStatus.text_color || '#000000'; // Default to black

			let optionHtml = 
				'<option value="' + singleStatus.id + '" selected style="font-size:unset; background-color:' +
				backgroundColor + '; color:' + textColor + ';">' + singleStatus.name + '</option>';

			statusDropdown.append(optionHtml);
			statusDropdown.prop('disabled', false).select2();
			return;
		}

		statusDropdown.append('<option value="">Select an option</option>');

		allowedStatuses.forEach(function (status) {
			let isSelected = (selectedStatus == status.id) ? 'selected' : '';
			let textColor = status.text_color || '#000000';
			statusDropdown.append(
				'<option value="' + status.id + '" ' + isSelected + 
				' style="font-size:unset; background-color:' + status.color + 
				'; color:' + textColor + ';">' + status.name + '</option>'
			);
		});

		statusDropdown.prop('disabled', false).select2();
	}

	function updateCustomFields(customFields, task_files) {
		let fieldsContainer = $('#custom-fields-container');
		fieldsContainer.empty();

		Object.keys(customFields).forEach(function (key) {
			let field = customFields[key];
			field.settings = JSON.parse(field.settings);

			switch (field.field_type) {
				case 'Text':
					fieldsContainer.append(createTextField(field));
					break;
				case 'Textarea':
					fieldsContainer.append(createTextareaField(field));
					break;
				case 'Checkbox Group':
					fieldsContainer.append(createCheckboxGroup(field));
					break;
				case 'Radio Buttons':
					fieldsContainer.append(createRadioButtons(field));
					break;
				case 'Auto Complete Dropdown':
					console.log('Auto Complete TBD');
					break;
				case 'Dropdown List':
					fieldsContainer.append(createDropdownField(field));
					$('#'+field.field_id).select2({
						placeholder: "Select", // Placeholder text
						allowClear: true, // Allow clearing all selected options
						width: "100%", // Full width of the parent container
					});
					break;
				case 'File Upload':
					fieldsContainer.append(createFileField(field, task_files));
					break;
				case 'Date':
					fieldsContainer.append(createDateField(field));
					break;
				case 'Datetime Picker':
					fieldsContainer.append(createDateTimeField(field));
					break;
				case 'Time':
					fieldsContainer.append(createTimeField(field));
					break;
				case 'Number':
					fieldsContainer.append(createNumberField(field));
					break;
				default:
					console.warn('Unsupported field type:', field.field_type);
			}
		});
	}

	function createTextField(field) {
		let field_val = field.settings.default_val ?? '';
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		if(Object.keys(old_vals).length > 0) {
			field_val = old_vals[field.field_id] !== undefined && old_vals[field.field_id] !== null ? old_vals[field.field_id] : '';
		}
		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<input type="text" name="${field.field_id}" class="form-control custom-field" id="${field.field_id}"
					placeholder="${field.settings.placeholder || ''}" 
					value="${field_val}">
			</div>
		`;
	}

	function createTextareaField(field) {
		let field_val = field.settings.default_val ?? '';
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		if(Object.keys(old_vals).length > 0) {
			field_val = old_vals[field.field_id] !== undefined && old_vals[field.field_id] !== null ? old_vals[field.field_id] : '';
		}
		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<textarea name="${field.field_id}" class="form-control custom-field" id="${field.field_id}" 
					rows="${field.settings.rows || 3}" 
					placeholder="${field.settings.placeholder || ''}">${field_val}</textarea>
			</div>
		`;
	}

	function createCheckboxGroup(field) {
		let required = field.required === true ? '<span class="text-danger">*</span>' : '';
		let options = field.settings.options || {};
		let defaultValues = field.settings.default_val || {};
		if (typeof old_vals !== 'undefined' && Object.keys(old_vals).length > 0) {
			defaultValues = old_vals[field.field_id] || {};
		}
		let checkboxes = Object.entries(options).map(([index, value]) => {
			let isChecked = index in defaultValues ? 'checked' : '';
			return `
				<div class="form-check">
					<input type="checkbox" name="${field.field_id}[${index}]" value="${value}" class="form-check-input" id="${field.field_id}-${index}" ${isChecked}>
					<label class="form-check-label" for="${field.field_id}-${index}">${value}</label>
				</div>
			`;
		}).join('');
		
		return `
			<div class="form-group">
				<label>${field.name} ${required}</label>
				${checkboxes}
			</div>
		`;
	}

	function createRadioButtons(field) {
		let required = field.required === true ? '<span class="text-danger">*</span>' : '';
		let options = field.settings.options || {};

		let old_val_obj = {};
		if (typeof old_vals !== 'undefined' && Object.keys(old_vals).length > 0) {
			let old_val = old_vals[field.field_id] || null;
			for (let [key, value] of Object.entries(options)) {
				if ((old_val_are == 'stored_vals' && value === old_val) || (old_val_are == 'old_vals' && key == old_val)) {
					old_val_obj[key] = value;
				}
			}
		}

		let radios = Object.entries(options).map(([index, value]) => {
			let isChecked = index in old_val_obj ? 'checked' : '';
			return `
				<div class="form-check">
					<input type="radio" name="${field.field_id}" value="${index}" class="form-check-input" id="${field.field_id}-${index}" ${isChecked}>
					<label class="form-check-label" for="${field.field_id}-${index}">${value}</label>
				</div>
			`;
		}).join('');
		
		return `
			<div class="form-group">
				<label>${field.name} ${required}</label>
				${radios}
			</div>
		`;
	}

	function createDropdownField(field) {
		let required = field.required === true ? '<span class="text-danger">*</span>' : '';
		let options = field.settings.options || {};
		let old_val_obj = {};

		if (typeof old_vals !== 'undefined' && Object.keys(old_vals).length > 0) {
			let old_val = old_vals[field.field_id] || null;

			for (let [key, value] of Object.entries(options)) {
				if ((old_val_are == 'stored_vals' && value === old_val) || (old_val_are == 'old_vals' && key == old_val)) {
					old_val_obj[key] = value;
				}
			}
		}

		let dropdownOptions = Object.entries(options).map(([index, value]) => {
			let selected = index in old_val_obj ? 'selected' : '';
			return `
				<option value="${index.trim()}" ${selected}>${value.trim()}</option>
			`;
		}).join('');

		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<select name="${field.field_id}" class="form-control" id="${field.field_id}">
					<option value="">Select an option</option>
					${dropdownOptions}
				</select>
			</div>
		`;
	}
	const downloadFileRouteBase = "{{ route('tasks.download_file', ['id' => '__TASK_ID__']) }}";
	function displayTaskFiles(field, task_files) {
		console.log(task_files);
		let html = '';

		if (task_files && task_files.hasOwnProperty(field.field_id)) {

			const files = task_files[field.field_id];
			if(files && files.length > 0) {
				for (const file of files) {
					let fileExtension = file.name.split('.').pop();
					var icon = "fa fa-file";
					if(jQuery.inArray(fileExtension, ["csv", "xls", "xlsx", "xlsm", "xls", "xls"]) !== -1) {
						icon = "fa fa-file-excel-o";
					} else if(jQuery.inArray(fileExtension, ["txt", "doc", "docx"]) !== -1){
						icon = "fa fa-file-word-o";
					} else if(jQuery.inArray(fileExtension, ["pdf"]) !== -1){
						icon = "fa fa-file-pdf-o";
					} else if(jQuery.inArray(fileExtension, ["txt"]) !== -1){
						icon = "fa fa-file-text-o";
					} else if(jQuery.inArray(fileExtension, ["rar", "tar", "zip"]) !== -1){
						icon = "fa fa-file-archive-o";
					} else if(jQuery.inArray(fileExtension, ["html", "mml", "xml"]) !== -1){
						icon = "fa fa-file-code-o";
					} else if(jQuery.inArray(fileExtension, ["jpg", "jpeg", "png", "gif", "bmp"]) !== -1){
						icon = "fa fa-file-image-o";
					} else if(jQuery.inArray(fileExtension, ["ppt", "pptx"]) !== -1){
						icon = "fa fa-file-powerpoint-o";
					}

					var creator_mobile = '';
					if( file.creator_moble != null && file.creator_moble != '' ) {
						creator_mobile = `(${file.creator_mobile})`;
					}

					let url = downloadFileRouteBase.replace('__TASK_ID__', file.id);
					html += `<div id="file-box-${file.id}" class="file-box" style="width:auto; max-width:49%">
					    <button aria-hidden="true" data-dismiss="alert" class="close remove-file" onclick="rm_file(${file.id})" type="button">×</button>
					    <div class="file" style="margin: 0px 5px 5px 0px; display: flex;">
					        <span class="corner"></span>
					        <div class="icon" style="padding: 3px 16px 0px 3px; height: 73px;">
					            <a href="${url}" target="_blank">
					                <i class="${icon}" style="font-size:66px; color: #1ab394;"></i>
					            </a>
					        </div>
					        <div class="file-name" style="width: 100%; border-left:3px solid #e7eaec; border-top: 0px; padding: 0px 10px;">
					            <div style="display:inline-grid">
					                <span style="word-wrap: break-word;">${file.name}</span>
					                <small>Type: <strong>${fileExtension}</strong></small>
					                <small>Uploaded By: ${file.creator_name} (${file.creator_email}) ${creator_mobile} </small>
					                <small> On: ${new Date(file.created_at).toLocaleString()} </small>
					            </div>
					        </div>
					    </div>
					</div>`;
				}
			}
		}/* else {
			html += "<p>No files</p>";
		}*/

		return html;
	}

	function createFileField(field, task_files) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';

		const multipleFiles = field.settings.allow_multiple === "yes" ? 'multiple' : '';
		
		const maxFilesHint = field.settings.allowed_file_types ? `Allowed Files: ${field.settings.allowed_file_types}` : '';
		const maxSizeHint = field.settings.max_file_size ? `Max Size: ${field.settings.max_file_size} MB` : '';
		
		const hints = `(${maxFilesHint}) (${maxSizeHint})`.trim();
        const inputName = field.settings.allow_multiple === "yes" ? `${field.field_id}[]` : field.field_id;

        const field_files = displayTaskFiles(field, task_files);

        if(field_files == '') {
			return `
				<div class="form-group">
					<label for="${field.field_id}">${field.name} ${required} ${hints}</label>
					<input type="file" name="${inputName}" class="form-control custom-field" id="${field.field_id}" ${multipleFiles}>
				</div>
			`;

        } else {
        	return `
				<div class="col-12" style="padding: 5px 6px;">
					<div class="panel panel-default" id="attachment-block">
						<div class="panel-heading pl-2 pt-1 pr-1 pb-0">
							<h5>${field.name} files</h5>
						</div>
						<div class="panel-body panel-body-adjust p-1">
							<div class="col-12" id="attachment-block-div" data-state="custom-height">
								<div class="row">
									${field_files}
								</div>
							</div>
							<div class="form-group">
								<label for="${field.field_id}">${field.name} ${required} ${hints}</label>
								<input type="file" name="${inputName}" class="form-control custom-field" id="${field.field_id}" ${multipleFiles}>
							</div>
						</div>
					</div>
				</div>
			`;
        }
	}

	function rm_file(id) {
	    Swal.fire({
	        title: 'Are you sure?',
	        text: 'You won\'t be able to revert this!',
	        icon: 'warning',
	        showCancelButton: true,
	        confirmButtonColor: '#3085d6',
	        cancelButtonColor: '#d33',
	        confirmButtonText: 'Yes, delete it!',
	        cancelButtonText: 'Cancel'
	    }).then((result) => {
	        if (result.isConfirmed) {
                Swal.fire({
                    title: "Deleting...",
                    text: "Please wait",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading(); // Show the loading spinner
                    }
                });
	            $.ajax({
	                url: `/tasks/${id}/rm-file`, // Update with the correct route
	                type: 'DELETE',
	                headers: {
	                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Required for Laravel CSRF protection
	                },
	                success: function(response) {
	                    if (response.success) {
	                        Swal.fire(
	                            'Deleted!',
	                            response.message,
	                            'success'
	                        );

	                        $(`#file-box-${id}`).remove();
	                    } else {
	                        Swal.fire(
	                            'Error!',
	                            response.message,
	                            'error'
	                        );
	                    }
	                },
	                error: function(xhr) {
	                    Swal.fire(
	                        'Error!',
	                        'Something went wrong while deleting the file.',
	                        'error'
	                    );
	                }
	            });
	        }
	    });
	}

	function createNumberField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		let field_val = field.settings.default_val ?? null;
		if(Object.keys(old_vals).length > 0) {
			field_val = old_vals[field.field_id] ?? null;
		}
		// Display min and max values as a hint if they are set
		const minMaxHint = (field.settings.min && field.settings.max) 
			? `(Min: ${field.settings.min}, Max: ${field.settings.max})` 
			: field.settings.min 
				? `(Min: ${field.settings.min})` 
				: field.settings.max 
					? `(Max: ${field.settings.max})` 
					: '';

		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required} ${minMaxHint}</label>
				<input type="number" 
					name="${field.field_id}" 
					class="form-control custom-field" 
					id="${field.field_id}" 
					placeholder="${field.settings.placeholder || ''}" 
					value="${field_val}" 
					${field.settings.min ? `min="${field.settings.min}"` : ''} 
					${field.settings.max ? `max="${field.settings.max}"` : ''}>
			</div>
		`;
	}

	function createTimeField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		let oldValue = old_vals[field.field_id] ?? null;

		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<input type="time" 
					name="${field.field_id}" 
					class="form-control custom-field" 
					id="${field.field_id}" 
					placeholder="${field.settings.placeholder || 'HH:MM'}" 
					value="${oldValue !== null ? oldValue : field.settings.default_val || ''}" 
					min="${field.settings.min_time || ''}" 
					max="${field.settings.max_time || ''}">
			</div>
		`;
	}

	function createDateTimeField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		let oldValue = old_vals[field.field_id] ?? null;
		
		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<input type="datetime-local"
					name="${field.field_id}"
					class="form-control custom-field"
					id="${field.field_id}"
					placeholder="${field.settings.placeholder || 'YYYY-MM-DD HH:MM'}"
					value="${oldValue !== null ? oldValue : field.settings.default_val || ''}"
					min="${field.settings.min_time || ''}"
					max="${field.settings.max_time || ''}">
			</div>
		`;
	}

	function createDateField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		let oldValue = old_vals[field.field_id] ?? null;
		
		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<input type="date"
					name="${field.field_id}"
					class="form-control custom-field"
					id="${field.field_id}"
					placeholder="${field.settings.placeholder || 'YYYY-MM-DD'}"
					value="${oldValue !== null ? oldValue : field.settings.default_val || ''}"
					min="${field.settings.min_date || ''}"
					max="${field.settings.max_date || ''}">
			</div>
		`;
	}
</script>
@endsection
