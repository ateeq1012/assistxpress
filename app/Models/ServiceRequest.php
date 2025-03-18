<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
	protected $fillable = [
		'service_domain_id', 'service_id', 'subject', 'description', 'status_id', 'priority_id', 'creator_group_id', 'created_by', 'updated_by', 'executor_id', 'executor_group_id',
		'sla_rule_id', 'response_time', 'tto', 'ttr', 'planned_start', 'planned_end', 'actual_execution_start', 'actual_execution_end'
	];

	protected $service_request_fields = [
		'service_domain_id' => 'Service Domain',
		'service_id' => 'Service',
		'subject' => 'Subject',
		'description' => 'Description',
		'status_id' => 'Status',
		'priority_id' => 'Priority',
		// 'planned_start' => 'Planned Start',
		// 'planned_end' => 'Planned End',
		// 'actual_execution_start' => 'Actual Exec. Start',
		// 'actual_execution_end' => 'Actual Exec. End',
	];
	protected $all_service_request_fields = [
		'id' => 'ID',
		'service_domain_id' => 'Service Domain',
		'service_id' => 'Service',
		'subject' => 'Subject',
		'description' => 'Description',
		'status_id' => 'Status',
		'priority_id' => 'Priority',
		// 'planned_start' => 'Planned Start',
		// 'planned_end' => 'Planned End',
		// 'actual_execution_start' => 'Actual Exec. Start',
		// 'actual_execution_end' => 'Actual Exec. End',
		'creator_group_id' => 'Creator Group',
		'created_by' => 'Creator',
		'updated_by' => 'Updater',
		'executor_id' => 'Assignee',
		'executor_group_id' => 'Assignee Group',
		'sla_rule_id' => 'SLA Rule',
		'created_at' => 'Created at',
		'updated_at' => 'Updated at',
	];

	public static $fileds_to_make_history = [
		'service_domain_id' => 'Service Domain',
		'service_id' => 'Service',
		'subject' => 'Subject',
		'description' => 'Description',
		'status_id' => 'Status',
		'priority_id' => 'Priority',
		'planned_start' => 'Planned Start',
		'planned_end' => 'Planned End',
		'actual_execution_start' => 'Actual Exec. Start',
		'actual_execution_end' => 'Actual Exec. End',
		'executor_id' => 'Assignee',
		'executor_group_id' => 'Assignee Group',
	];

	public function getServiceRequestFields()
	{
		return $this->service_request_fields;
	}
	public function getAllServiceRequestFields()
	{
		return $this->all_service_request_fields;
	}

	public function serviceDomain()
	{
		return $this->belongsTo(ServiceDomain::class);
	}

	public function service()
	{
		return $this->belongsTo(Service::class);
	}

	public function status()
	{
		return $this->belongsTo(Status::class);
	}

	public function priority()
	{
		return $this->belongsTo(ServicePriority::class);
	}

	public function creator()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
	public function updater()
	{
		return $this->belongsTo(User::class, 'updated_by');
	}
	public function executor()
	{
		return $this->belongsTo(User::class, 'executor_id');
	}

	public function creatorGroup()
	{
		return $this->belongsTo(Group::class, 'creator_group_id');
	}
	public function executorGroup()
	{
		return $this->belongsTo(Group::class, 'executor_group_id');
	}
	public function sla()
	{
		return $this->belongsTo(Sla::class, 'sla_rule_id');
	}
	public function serviceRequestAttachments()
	{
		return $this->hasMany(ServiceRequestAtachment::class, 'service_request_id');
	}
	public function serviceRequestAuditLogs()
	{
		return $this->hasMany(ServiceRequestAuditLog::class, 'service_request_id');
	}
	public function serviceRequestStatusLogs()
	{
		return $this->hasMany(ServiceRequestAuditLog::class, 'service_request_id')
					->where('field_name', 'status_id')
					->orderBy('created_at', 'asc');
	}
	public function serviceRequestComments()
	{
		return $this->hasMany(ServiceRequestComment::class, 'service_request_id');
	}
	public function serviceRequestCustomField()
	{
		return $this->hasMany(ServiceRequestCustomField::class, 'service_request_id');
	}
}
