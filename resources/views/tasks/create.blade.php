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
</style>
<div class="ibox pt-2">
	<div class="ibox-title">
		<h5>Create Task</h5>
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

		<form id="task-form" method="POST" action="{{ route('tasks.store') }}">
			@csrf
			<div class="form-group">
				<label for="project_id">Project</label>
				<select name="project_id" class="form-control select2-field" id="project_id" required >
					<option value="">Select Project</option>
					@foreach($projects as $project)
						<option value="{{ $project->id }}">
							{{ $project->name }}
						</option>
					@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="task_type">Task Type</label>
				<select name="task_type" class="form-control select2-field" id="task_type" required >
					@foreach($task_types as $task_type)
						<option value="{{ $task_type->id }}">
							{{ $task_type->name }}
						</option>
					@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="subject">Subject<span class="text-danger">*</span></label>
				<input type="text" name="subject" class="form-control" id="subject" value="{{ old('subject') }}">
			</div>

			<div class="form-group">
				<label for="description">Description</label>
				<textarea name="description" class="form-control" id="description">{{ old('subject') }}</textarea>
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
						<option value="{{ $priority->id }}" style="font-size:unset; background-color:{{ $priority->color }}; color:{{ GeneralHelper::invert_color($priority->color) }}">
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
					<select name="creator_group_id" class="form-control select2-field" id="creator_group_id">
						<option value="">Select an option</option>
						@foreach($creator_groups as $gid => $gname)
							<option value="{{$gid}}">{{$gname}}</option>
						@endforeach
					</select>
				</div>
			@endif

			<div class="form-group">
				<label for="executor_group_id">Assignee Group<span class="text-danger">*</span></label>
				<select name="executor_group_id" class="form-control" id="executor_group_id">
					<option value="">Select an option</option>
					@foreach($all_groups as $gid => $gname)
						<option value="{{$gid}}">{{$gname}}</option>
					@endforeach
				</select>
			</div>
			<div class="form-group">
				<label for="executor_id">Assignee</label>
				<select name="executor_id" class="form-control select2-field" id="executor_id">
					<option value="">Select an option</option>
				</select>
			</div>
		</form>
	</div>
	<div class="ibox-footer">
		<button id="submit-button" type="submit" class="btn btn-primary">Create</button>
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

		$('#executor_group_id').select2({
		    width: '100%',
		    allowClear: true,
		    placeholder: 'Search for groups',
		    delay: 1000, // Delay before starting search
		    minimumInputLength: 2,
		    ajax: {
		        url: '{{ route("tasks.search_project_groups") }}',
		        dataType: 'json',
		        type: 'POST',
		        delay: 250, // Delay to prevent flooding requests
		        data: function(params) {
		            return {
		                q: params.term,
		                enabled_only: true,
		                project_id: $('#project_id').val()
		            };
		        },
		        processResults: function(data) {
		            return {
		                results: $.map(data.data, function(item) {
		                    return {
		                        id: item.id,
		                        text: item.name
		                    };
		                })
		            };
		        },
		        cache: true,
		        headers: {
		            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token
		        },
		        error: function(xhr, status, error) {
		        	if(xhr.status == 422) {
		        		Swal.fire({
							title: 'Invalid Request',
							icon: 'warning',
						});
		        	} else {
		        		Swal.fire({
							title: 'Error',
							text: 'Unable to search user group',
							icon: 'error',
						});
		        	}
		        }
		    }
		});
		$('#executor_id').select2({
		    width: '100%',
		    allowClear: true,
		    placeholder: 'Search Users',
		    delay: 1000, // Delay before starting search
		    minimumInputLength: 2,
		    ajax: {
		        url: '{{ route("tasks.search_group_users") }}',
		        dataType: 'json',
		        type: 'POST',
		        delay: 250, // Delay to prevent flooding requests
		        data: function(params) {
		            return {
		                q: params.term,
		                group_id: $('#executor_group_id').val()
		            };
		        },
		        processResults: function(data) {
		            return {
		                results: $.map(data.data, function(item) {
		                    return {
		                        id: item.id,
		                        text: item.name
		                    };
		                })
		            };
		        },
		        cache: true,
		        headers: {
		            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token
		        },
		        error: function(xhr, status, error) {
		        	if(xhr.status == 422) {
		        		Swal.fire({
							title: 'Invalid Request',
							icon: 'warning',
						});
		        	} else {
		        		Swal.fire({
							title: 'Error',
							text: 'Unable to search user',
							icon: 'error',
						});
		        	}
		        }
		    }
		});
	});

	$('#task_type').change(function() {
		get_fields('task_type');
	});

	$('#project_id').change(function() {
		get_fields('project_id');
	});

	$('#executor_group_id').change(function() {
	    $('#executor_id').val('').trigger('change');
	});

	function get_fields (changed_field) {

		let selected_task_type = $('#'+changed_field).val();
		if (selected_task_type !== 'undefined' && selected_task_type != '') {
			
			Swal.fire({
				text: "Loading...",
				icon: 'info',
				allowOutsideClick: false,
				allowEscapeKey: false,
				didOpen: () => {
					Swal.showLoading(); // Show the loading spinner
				}
			});

			$.ajax({
				url: '{{ route("tasks.get_fields") }}',
				type: 'POST',
				data: {
					_token: '{{ csrf_token() }}',
					changed_field: changed_field,
					mode: 'create',
					id: selected_task_type,
				},
				success: function(response) {
					if(response.success) {
						if(changed_field == 'project_id') {
							updateTaskTypeDropdown(response.data.task_types);
						} else if (changed_field == 'task_type') {							
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
								updateCustomFields(response.data.custom_fields);
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
					$('.swal2-container').hide();
					let response = xhr.responseJSON;
					console.log(response);
					if(changed_field == 'project_id' && (!response || typeof response.data.task_types === 'undefined' || Object.keys(response.data.task_types).length === 0)) {
						console.log(response);
						console.log(response.data);
						Swal.fire({title: "", text: "No active Task Types found under selected Project", icon: "warning" });
						$('#task_type').prop('disabled', true).select2();
					}
					if(changed_field == 'task_type' && (!response || typeof response.data.allowed_statuses === 'undefined' || Object.keys(response.data.allowed_statuses).length === 0)) {
						console.log(response);
						console.log(response.data);
						Swal.fire({title: "", text: "No statuses wer allowed, Please check the Workflow against the selected Task Type", icon: "warning" });
						$('#status_id').prop('disabled', true).select2();
					}
				}
			});

		} else {
			$('#custom-fields-container').empty();
		}
	}

	function updateTaskTypeDropdown(taskTypeOptions) {
		let taskTypeDropdown = $('#task_type');
		let selectedTaskType = taskTypeDropdown.val();
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
			taskTypeDropdown.trigger('change');
			return;
		}

	    taskTypeDropdown.append(
	        '<option value="" disabled selected>Select an option</option>'
	    );

		taskTypeOptions.forEach(function (taskType) {
			let isSelected = (selectedTaskType == taskType.id) ? 'selected' : '';
			let textColor = taskType.text_color || '#000000';
			taskTypeDropdown.append(
				'<option value="' + taskType.id + '" ' + isSelected + 
				' style="font-size:unset; background-color:' + taskType.color + 
				'; color:' + textColor + ';">' + taskType.name + '</option>'
			);
		});

		taskTypeDropdown.prop('disabled', false).select2();
	}

	function updateStatusDropdown(allowedStatuses) {
		let statusDropdown = $('#status_id');
		let selectedStatus = statusDropdown.val();
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

	function updateCustomFields(customFields) {
		
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
					fieldsContainer.append(createFileField(field));
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

		// Generate the checkboxes dynamically
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
		let defaultValues = field.settings.default_val || {};

		let radios = Object.entries(options).map(([index, value]) => {
			let isChecked = index in defaultValues ? 'checked' : '';
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
		let defaultValues = field.settings.default_val || {};

		let dropdownOptions = Object.entries(options).map(([index, value]) => {
			let selected = index in defaultValues ? 'selected' : '';
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

	function createFileField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';

		const multipleFiles = field.settings.allow_multiple === "yes" ? 'multiple' : '';
		
		const maxFilesHint = field.settings.allowed_file_types ? `Allowed Files: ${field.settings.allowed_file_types}` : '';
		const maxSizeHint = field.settings.max_file_size ? `Max Size: ${field.settings.max_file_size} MB` : '';
		
		const hints = `${maxFilesHint} ${maxSizeHint}`.trim();
        const inputName = field.settings.allow_multiple === "yes" ? `${field.field_id}[]` : field.field_id;

		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required} ${hints}</label>
				<input type="file" name="${inputName}" class="form-control custom-field" id="${field.field_id}" ${multipleFiles}>
			</div>
		`;
	}

	function createNumberField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		let field_val = field.settings.default_val ?? null;

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

		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<input type="time" 
					name="${field.field_id}" 
					class="form-control custom-field" 
					id="${field.field_id}" 
					placeholder="${field.settings.placeholder || 'HH:MM'}" 
					value="${field.settings.default_val || ''}" 
					min="${field.settings.min_time || ''}" 
					max="${field.settings.max_time || ''}">
			</div>
		`;
	}

	function createDateTimeField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		
		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<input type="datetime-local"
					name="${field.field_id}"
					class="form-control custom-field"
					id="${field.field_id}"
					placeholder="${field.settings.placeholder || 'YYYY-MM-DD HH:MM'}"
					value="${field.settings.default_val || ''}"
					min="${field.settings.min_time || ''}"
					max="${field.settings.max_time || ''}">
			</div>
		`;
	}

	function createDateField(field) {
		let required = (field.required === true) ? '<span class="text-danger">*</span>' : '';
		
		return `
			<div class="form-group">
				<label for="${field.field_id}">${field.name} ${required}</label>
				<input type="date"
					name="${field.field_id}"
					class="form-control custom-field"
					id="${field.field_id}"
					placeholder="${field.settings.placeholder || 'YYYY-MM-DD'}"
					value="${field.settings.default_val || ''}"
					min="${field.settings.min_date || ''}"
					max="${field.settings.max_date || ''}">
			</div>
		`;
	}
</script>
@endsection
