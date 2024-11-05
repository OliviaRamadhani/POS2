<?php

namespace App\Exports;

use App\Models\Reservation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReservationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $reservations = Reservation::select('id', 'name', 'phone', 'date', 'start_time', 'end_time', 'guests', 'menus', 'total_price')->get();
    
        $totalGuests = $reservations->sum('guests');
        $totalReservations = $reservations->count();
        $totalRevenue = $reservations->sum('total_price');
    
        // Adding totals row at the end
        $reservations->push((object) [
            'id' => '',
            'name' => 'Total Reservations',
            'phone' => '',
            'date' => '',
            'start_time' => '',
            'end_time' => '',
            'guests' => $totalReservations,
            'menus' => '',
            'total_price' => $totalRevenue,
            'status' => ''
        ]);
    
        $reservations->push((object) [
            'id' => '',
            'name' => 'Total Guests',
            'phone' => '',
            'date' => '',
            'start_time' => '',
            'end_time' => '',
            'guests' => $totalGuests,
            'menus' => '',
            'total_price' => '',
            'status' => ''
        ]);
    
        return $reservations;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Phone',
            'Date',
            'Start Time',
            'End Time',
            'Guests',
            'Order',
            'Total Price',
            'Status',
        ];
    }

    public function map($reservation): array
    {
        return [
            $reservation->id,
            $reservation->name,
            $reservation->phone,
            $reservation->date,
            $reservation->start_time,
            $reservation->end_time,
            $reservation->guests,
            $reservation->menus,
            $reservation->total_price,
            $this->getReservationStatus($reservation),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Define header style with a strong background and bold white text
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4A86E8'], // Strong blue
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => 'FF000000'], // Black border
                ],
            ],
        ];

        // Apply header style
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Define data rows with alternating light gray background
        $highestRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $highestRow; $i++) {
            $rowStyle = ($i % 2 == 0) ? 'FFF2F2F2' : 'FFFFFFFF'; // Alternating gray and white
            $sheet->getStyle("A{$i}:J{$i}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $rowStyle],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD3D3D3'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        // Define style for the totals row at the bottom
        $sheet->getStyle('A' . ($highestRow - 1) . ':J' . $highestRow)
              ->applyFromArray([
                  'font' => [
                      'bold' => true,
                      'color' => ['argb' => 'FF4A86E8'], // Matching header color
                  ],
                  'alignment' => [
                      'horizontal' => Alignment::HORIZONTAL_RIGHT,
                  ],
                  'borders' => [
                      'top' => [
                          'borderStyle' => Border::BORDER_MEDIUM,
                          'color' => ['argb' => 'FF000000'],
                      ],
                  ],
              ]);

        // Adjust column widths
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(30);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(15);

        return [];
    }

    private function getReservationStatus($reservation)
    {
        $now = new \DateTime();
        $endTime = new \DateTime("{$reservation->date} {$reservation->end_time}");
        return $now > $endTime ? 'Reservation Ended' : 'Active';
    }
}
