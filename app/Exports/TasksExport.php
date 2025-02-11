<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\TaskView;
use App\Models\Status;
use App\Models\TaskPriority;
use App\Models\User;
use App\Models\Group;
use App\Models\TaskType;
use App\Models\CustomField;
use App\Models\Project;
use App\Models\Sla;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TasksExport implements FromCollection, WithMapping, WithColumnFormatting, WithHeadings, WithStyles
{
    protected $searchParams;
    protected $final_fields;
    protected $custom_fields_lkp;
    protected $project_lkp;
    protected $task_types_lkp;
    protected $statuses_lkp;
    protected $priorities_lkp;
    protected $users_lkp;
    protected $groups_lkp;
    protected $sla_rules_lkp;

    public function title(): string
    {
        return 'Tasks';
    }

    public function __construct(array $searchParams)
    {
        $this->searchParams = $searchParams;
        $this->initializeFields();
    }

    protected function initializeFields()
    {
        $task = new \App\Models\Task();
        $system_fields = $task->getAllTaskFields();
        $_EXCLUDED_FIELDS = ['File Upload'];
        $custom_fields = CustomField::whereNotIn('field_type', $_EXCLUDED_FIELDS)->get()->toArray();
        $custom_fields_lkp = array_column($custom_fields, null, 'field_id');

        $final_fields = $system_fields;
        foreach ($custom_fields as $custom_field) {
            $final_fields[$custom_field['field_id']] = $custom_field['name'];
        }

        $this->final_fields = $final_fields;
        $this->custom_fields_lkp = $custom_fields_lkp;

        $projects = Project::select('id', 'name', 'color')->get()->toArray();
        $this->project_lkp = array_column($projects, null, 'id');
        
        $task_types = TaskType::select('id', 'name', 'color')->get()->toArray();
        $this->task_types_lkp = array_column($task_types, null, 'id');

        $statuses = Status::select('id', 'name', 'color')->get()->toArray();
        $this->statuses_lkp = array_column($statuses, null, 'id');

        $priorities = TaskPriority::select('id', 'name', 'color')->get()->toArray();
        $this->priorities_lkp = array_column($priorities, null, 'id');
        
        $users = User::select('id', 'name')->get()->toArray();
        $this->users_lkp = array_column($users, 'name', 'id');
        
        $groups = Group::select('id', 'name')->get()->toArray();
        $this->groups_lkp = array_column($groups, 'name', 'id');
        
        $sla_rules = Sla::select('id', 'name', 'color')->get()->toArray();
        $this->sla_rules_lkp = array_column($sla_rules, null, 'id');
    }

    public function collection()
    {
        ini_set("memory_limit", '8192M');
        set_time_limit(300);

        $filters = $this->searchParams;
        $query = DB::table('task_view')->select(array_keys($this->final_fields));

        if (count($filters) > 0) {
            $_LIKE = ['subject', 'description'];
            $_EQUAL = ['id', 'project_id', 'task_type_id', 'status_id', 'priority_id'];

            $_LIKE_CF = ['Text', 'Textarea', 'Checkbox Group'];
            $_EQUAL_CF = ['Number', 'Radio Buttons', 'Dropdown List', 'Date', 'Time', 'Date-Time Picker'];

            foreach ($this->custom_fields_lkp as $custom_field) {
                if (in_array($custom_field['field_type'], $_LIKE_CF)) {
                    $_LIKE[] = $custom_field['field_id'];
                } else if (in_array($custom_field['field_type'], $_EQUAL_CF)) {
                    $_EQUAL[] = $custom_field['field_id'];
                }
            }

            foreach ($filters as $field_id => $searchValue) {
                if (in_array($field_id, $_LIKE)) {
                    $searchValue = strtolower(trim($searchValue));
                    $query->where(function ($q) use ($field_id, $searchValue) {
                        $q->whereRaw('LOWER(' . $field_id . ') LIKE ?', ['%' . $searchValue . '%']);
                    });
                }
                if (in_array($field_id, $_EQUAL)) {
                    $query->where(function ($q) use ($field_id, $searchValue) {
                        $q->where($field_id, $searchValue);
                    });
                }
            }
        }

        return $query->get();
    }

    public function map($task): array
    {
        $row = [
            $task->id,
            $this->project_lkp[$task->project_id]['name'] ?? '',
            $this->task_types_lkp[$task->task_type_id]['name'] ?? '',
            $task->subject,
            $task->description,
            $this->statuses_lkp[$task->status_id]['name'] ?? '',
            $this->priorities_lkp[$task->priority_id]['name'] ?? '',
            $this->groups_lkp[$task->creator_group_id] ?? '',
            $this->users_lkp[$task->created_by] ?? '',
            $this->users_lkp[$task->executor_id] ?? '',
            $this->groups_lkp[$task->executor_group_id] ?? '',
            $this->users_lkp[$task->updated_by] ?? '',
            $this->sla_rules_lkp[$task->sla_rule_id]['name'] ?? '',
            $task->created_at,
            $task->updated_at
        ];

        foreach ($this->custom_fields_lkp as $cfk => $cfl) {
            $row[] = $task->$cfk;
        }

        return $row;
    }

    public function headings(): array
    {
        $headings = [
            'Task ID',
            'Project',
            'Task Type',
            'Subject',
            'Description',
            'Status',
            'Priority',
            'Creator Group',
            'Creator',
            'Assignee',
            'Assignee Group',
            'Last Updated By',
            'SLA Rule',
            'Created at',
            'Updated at',
        ];

        foreach ($this->custom_fields_lkp as $cfk => $cfl) {
            $headings[] = $cfl['name'];
        }

        return $headings;
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FF1AB394'],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        return $sheet;
    }
}
