<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithMapping, WithColumnFormatting, WithHeadings, WithStyles
{
    protected $searchParams;

    public function __construct(array $searchParams)
    {
        $this->searchParams = $searchParams;
    }

    public function collection()
    {
        $query = User::query();

        if (!empty($this->searchParams['name'])) {
            $query->where('name', 'like', '%' . $this->searchParams['name'] . '%');
        }

        if (!empty($this->searchParams['email'])) {
            $query->where('email', 'like', '%' . $this->searchParams['email'] . '%');
        }

        // Add more filters as needed

        return $query->get();
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->phone,
            $user->role->name ?? '',
            $user->groups->pluck('name')->implode(', '),
            $user->creator->name ?? '',
            $user->creator->email ?? '',
            $user->creator->phone ?? '',
            $user->created_at->format('d-M-Y H:i:s'),
            $user->updater->name ?? '',
            $user->updater->email ?? '',
            $user->updater->phone ?? '',
            $user->updated_at->format('d-M-Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'Name', 
            'Email', 
            'Phone Number', 
            'Role', 
            'Groups', 
            'Created By',
            'Creator Email',
            'Creator Phone',
            'Created At',
            'Updated By', 
            'Updater Email',
            'Updater Phone',
            'Updated At'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Apply a thin border to all cells with data
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        // Apply a thick border only to the header row up to the last column with data
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
