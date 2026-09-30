<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\Group;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles
{
    public function __construct(private readonly Client $client, private readonly ?Group $group) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return [[$this->client->code, 'SIM-CONTOH-001', 'Nama Karyawan Contoh', '', '081234567890', now()->format('Y-m-d'), '1995-01-01', 'male', 'permanent', 'single', $this->group?->code ?? '', 'active']];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Client Code', 'SIM ID', 'Nama Lengkap', 'Email', 'Nomor Telepon', 'Tanggal Masuk', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Karyawan', 'Status Perkawinan', 'Group Code', 'Status'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT, 'B' => NumberFormat::FORMAT_TEXT, 'D' => NumberFormat::FORMAT_TEXT, 'E' => NumberFormat::FORMAT_TEXT, 'K' => NumberFormat::FORMAT_TEXT];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']]], 2 => ['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]];
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        $notes = ['A1' => 'Kosongkan untuk memakai client aktif.', 'B1' => 'Opsional. Jika SIM ID sudah ada pada client, data karyawan diperbarui.', 'C1' => 'Wajib.', 'D1' => 'Opsional, harus unik jika diisi.', 'E1' => 'Wajib.', 'F1' => 'Format YYYY-MM-DD.', 'G1' => 'Wajib. Password awal akun dibuat dari tanggal ini dengan format ddmmyyyy.', 'H1' => 'male atau female.', 'I1' => 'permanent, contract, atau daily.', 'J1' => 'single, married, divorced, atau widowed.', 'K1' => 'Opsional, gunakan Group Code.', 'L1' => 'active atau inactive.'];

        return [AfterSheet::class => function (AfterSheet $event) use ($notes): void {
            $event->sheet->getDelegate()->freezePane('A2');
            foreach ($notes as $cell => $note) {
                $event->sheet->getDelegate()->getComment($cell)->getText()->createTextRun($note);
            }
        }];
    }
}
