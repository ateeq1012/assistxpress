<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestView;
use App\Models\Status;
use App\Models\ServicePriority;
use App\Models\User;
use App\Models\Group;
use App\Models\Service;
use App\Models\CustomField;
use App\Models\ServiceDomain;
use App\Models\Sla;

use App\Helpers\SlaHelper;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceRequestExport implements FromCollection, WithMapping, WithColumnFormatting, WithHeadings, WithStyles
{
    protected $searchParams;
    protected $final_fields;
    protected $custom_fields_lkp;
    protected $service_domain_lkp;
    protected $services_lkp;
    protected $statuses_lkp;
    protected $priorities_lkp;
    protected $users_lkp;
    protected $groups_lkp;
    protected $sla_rules_lkp;

    public function title(): string
    {
        return 'Service Requests';
    }

    public function __construct(array $searchParams)
    {
        $this->searchParams = $searchParams;
        $this->initializeFields();
    }

    protected function initializeFields()
    {
        $service_request = new \App\Models\ServiceRequest();
        $system_fields = $service_request->getAllServiceRequestFields();
        $_EXCLUDED_FIELDS = ['File Upload'];
        $custom_fields = CustomField::whereNotIn('field_type', $_EXCLUDED_FIELDS)->get()->toArray();
        $custom_fields_lkp = array_column($custom_fields, null, 'field_id');

        $final_fields = $system_fields;
        foreach ($custom_fields as $custom_field) {
            $final_fields[$custom_field['field_id']] = $custom_field['name'];
        }

        $this->final_fields = $final_fields;
        $this->custom_fields_lkp = $custom_fields_lkp;

        $service_domains = ServiceDomain::select('id', 'name', 'color')->get()->toArray();
        $this->service_domain_lkp = array_column($service_domains, null, 'id');
        
        $services = Service::select('id', 'name', 'color')->get()->toArray();
        $this->services_lkp = array_column($services, null, 'id');

        $statuses = Status::get()->toArray();
        $this->statuses_lkp = array_column($statuses, null, 'id');

        $priorities = ServicePriority::select('id', 'name', 'color')->get()->toArray();
        $this->priorities_lkp = array_column($priorities, null, 'id');
        
        $users = User::select('id', 'name')->get()->toArray();
        $this->users_lkp = array_column($users, 'name', 'id');
        
        $groups = Group::select('id', 'name')->get()->toArray();
        $this->groups_lkp = array_column($groups, 'name', 'id');
        
        $sla_rules = Sla::get()->toArray();
        $this->sla_rules_lkp = array_column($sla_rules, null, 'id');
    }

    public function collection()
    {
        ini_set("memory_limit", '8192M');
        set_time_limit(300);

        $filters = $this->searchParams;
        $query = DB::table('service_request_view');

        if (count($filters) > 0) {
            $_LIKE = ['subject', 'description'];
            $_EQUAL = ['id', 'service_domain_id', 'service_id', 'status_id', 'priority_id'];

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

    public function map($service_request): array
    {
        $service_request_status = $this->statuses_lkp[$service_request->status_id];

        $slaInfo = [];
        if(isset($service_request->sla_rule_id) && isset($this->sla_rules_lkp[$service_request->sla_rule_id]) ) {
            $slaInfo = SlaHelper::getSlaInfo($service_request_status, $service_request, $this->sla_rules_lkp[$service_request->sla_rule_id]);
        }

        $row = [
            $service_request->id,
            $this->service_domain_lkp[$service_request->service_domain_id]['name'] ?? '',
            $this->services_lkp[$service_request->service_id]['name'] ?? '',
            $service_request->subject,
            $service_request->description,
            $service_request_status['name'] ?? '',
            $this->priorities_lkp[$service_request->priority_id]['name'] ?? '',
            $this->groups_lkp[$service_request->creator_group_id] ?? '',
            $this->users_lkp[$service_request->created_by] ?? '',
            $this->users_lkp[$service_request->executor_id] ?? '',
            $this->groups_lkp[$service_request->executor_group_id] ?? '',
            $this->users_lkp[$service_request->updated_by] ?? '',
            $slaInfo['sla_rule_name'] ?? 'Not Applicable',
            $slaInfo['response_time_sla'] ?? '',
            $slaInfo['response_time_spent'] ?? '',
            $slaInfo['response_sla_percentage'] ?? '',
            $slaInfo['response_sla_status'] ?? '',
            $slaInfo['resolution_time_sla'] ?? '',
            $slaInfo['resolution_time_spent'] ?? '',
            $slaInfo['resolution_sla_percentage'] ?? '',
            $slaInfo['resolution_sla_status'] ?? '',
            $service_request->created_at,
            $service_request->updated_at
        ];

        foreach ($this->custom_fields_lkp as $cfk => $cfl) {
            $row[] = $service_request->$cfk;
        }

        return $row;
    }

    public function headings(): array
    {
        $headings = [
            'ServiceRequest ID',
            'Service Domain',
            'Service',
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
            'Response Time SLA',
            'Response Time Spent',
            'Response SLA %',
            'Response SLA Status',
            'Resolution Time SLA',
            'Resolution Time Spent',
            'Resolution SLA %',
            'Response SLA Status',
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
        
        $sheet->getStyle('M1:Q1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FF4BACC6'],
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->getStyle('R1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FF31869B'],
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
