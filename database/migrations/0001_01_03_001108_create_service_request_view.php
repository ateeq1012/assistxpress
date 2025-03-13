<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class CreateServiceRequestView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $customFields = DB::table('custom_fields')->get();

        if ($customFields->isEmpty()) {
            // Handle the case where there are no custom fields
            DB::statement('DROP VIEW IF EXISTS service_request_view');
            DB::statement("CREATE VIEW service_request_view AS
                SELECT
                    service_requests.id,
                    service_requests.service_domain_id,
                    service_requests.service_id,
                    service_requests.subject,
                    service_requests.description,
                    service_requests.status_id,
                    service_requests.priority_id,
                    service_requests.creator_group_id,
                    service_requests.created_by,
                    service_requests.updated_by,
                    service_requests.executor_id,
                    service_requests.executor_group_id,
                    service_requests.sla_rule_id,
                    service_requests.response_time,
                    service_requests.tto,
                    service_requests.ttr,
                    service_requests.planned_start,
                    service_requests.planned_end,
                    service_requests.actual_execution_start,
                    service_requests.actual_execution_end,
                    service_requests.created_at,
                    service_requests.updated_at
                FROM
                    service_requests
                "
            );
        }
    }

    /**
    * drop the service_request_view.
    *
    * @return void
    */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS service_request_view');
    }
}