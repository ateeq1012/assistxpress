<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class CreateTaskView extends Migration
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
            DB::statement('DROP VIEW IF EXISTS task_view');
            DB::statement("CREATE VIEW task_view AS
                SELECT
                    tasks.id,
                    tasks.task_type_id,
                    tasks.subject,
                    tasks.description,
                    tasks.status_id,
                    tasks.priority_id,
                    tasks.creator_group_id,
                    tasks.created_by,
                    tasks.updated_by,
                    tasks.executor_id,
                    tasks.executor_group_id,
                    tasks.sla_rule_id,
                    tasks.time_spent,
                    tasks.planned_start,
                    tasks.planned_end,
                    tasks.actual_execution_start,
                    tasks.actual_execution_end,
                    tasks.created_at,
                    tasks.updated_at
                FROM
                    tasks
                ");
            return;
        }

         $customFieldColumns = $customFields->map(function ($customField) {
            $escapedFieldId = addslashes($customField->id);
            $safeAlias = 'cf_' . $customField->id;

            $fieldType = $customField->field_type;
            
            $caseStatement = "MAX(CASE WHEN task_custom_fields.field_id = {$escapedFieldId} THEN task_custom_fields.value";
            
            if ($fieldType === 'Date') {
                $caseStatement .= "::date";
            } else if ($fieldType === 'Datetime Picker') {
                $caseStatement .= "::timestamp without time zone";
            } else if ($fieldType === 'Time') {
                $caseStatement .= "::time without time zone";
            }  else if ($fieldType === 'Number') {
               $caseStatement .= "::numeric";
            }
            
            $caseStatement .= " END) AS \"{$safeAlias}\"";

            return $caseStatement;
        })->join(",\n    ");


        DB::statement('DROP VIEW IF EXISTS task_view_temp');

        // Create the new temporary view
        $viewQuery = "
            CREATE VIEW task_view_temp AS
            SELECT
                tasks.id,
                tasks.task_type_id,
                tasks.subject,
                tasks.description,
                tasks.status_id,
                tasks.priority_id,
                tasks.creator_group_id,
                tasks.created_by,
                tasks.updated_by,
                tasks.executor_id,
                tasks.executor_group_id,
                tasks.sla_rule_id,
                tasks.time_spent,
                tasks.planned_start,
                tasks.planned_end,
                tasks.actual_execution_start,
                tasks.actual_execution_end,
                tasks.created_at,
                tasks.updated_at,
                {$customFieldColumns}
            FROM
                tasks
            LEFT JOIN
                task_custom_fields ON tasks.id = task_custom_fields.task_id
            GROUP BY
                tasks.id,
                tasks.task_type_id,
                tasks.subject,
                tasks.description,
                tasks.status_id,
                tasks.priority_id,
                tasks.creator_group_id,
                tasks.created_by,
                tasks.updated_by,
                tasks.executor_id,
                tasks.executor_group_id,
                tasks.sla_rule_id,
                tasks.time_spent,
                tasks.planned_start,
                tasks.planned_end,
                tasks.actual_execution_start,
                tasks.actual_execution_end,
                tasks.created_at,
                tasks.updated_at
            ";


        // Execute the query to create the view
        DB::statement($viewQuery);

        // Rename the current view and drop the backup
        DB::statement('ALTER VIEW IF EXISTS task_view RENAME TO task_view_backup');
        DB::statement('ALTER VIEW task_view_temp RENAME TO task_view');
        DB::statement('DROP VIEW IF EXISTS task_view_backup');
    }

    /**
    * drop the task_view.
    *
    * @return void
    */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS task_view');
    }
}