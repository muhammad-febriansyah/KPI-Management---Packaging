<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Payroll</title>
    <style>
        @page { margin: 28px 24px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #2563eb; margin-bottom: 14px; padding-bottom: 10px; }
        .brand, .meta { display: table-cell; vertical-align: top; }
        .brand-name { color: #0f172a; font-size: 16px; font-weight: bold; }
        .brand-code, .muted { color: #64748b; font-size: 8px; margin-top: 3px; }
        .meta { text-align: right; }
        .title { color: #1d4ed8; font-size: 15px; font-weight: bold; }
        .period { color: #475569; font-size: 9px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1d4ed8; color: #ffffff; font-size: 7.5px; font-weight: bold; padding: 6px 4px; text-align: left; }
        td { border: 1px solid #dbe3ef; color: #334155; padding: 5px 4px; vertical-align: middle; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        th.number, td.number { text-align: right; }
        th.center, td.center { text-align: center; }
        td.employee { color: #0f172a; font-weight: bold; }
        td.net { color: #047857; font-weight: bold; }
        .empty { border: 1px solid #dbe3ef; color: #64748b; padding: 20px; text-align: center; }
        .footer { color: #94a3b8; font-size: 7.5px; margin-top: 12px; text-align: right; }
    </style>
</head>
<body>
    @php
        $rupiah = fn ($value): string => 'Rp '.number_format((float) $value, 0, ',', '.');
        $periodLabel = \Illuminate\Support\Carbon::parse($dateFrom)->translatedFormat('d M Y').' – '.\Illuminate\Support\Carbon::parse($dateTo)->translatedFormat('d M Y');
    @endphp

    <div class="header">
        <div class="brand">
            <div class="brand-name">{{ $client->name }}</div>
            <div class="brand-code">{{ $client->code }} · Laporan payroll karyawan</div>
        </div>
        <div class="meta">
            <div class="title">LAPORAN PAYROLL</div>
            <div class="period">Periode {{ $periodLabel }}</div>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="empty">Tidak ada data payroll pada periode yang dipilih.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIK</th>
                    <th>Nama Lengkap</th>
                    <th class="center">Jenis Kelamin</th>
                    <th class="number">Total Hari Masuk</th>
                    <th class="number">Gaji Bersih</th>
                    <th class="number">Gaji Kotor</th>
                    <th class="number">BPJS Ketenagakerjaan</th>
                    <th class="number">Seragam (Kaos/Celana)</th>
                    <th class="number">Perlengkapan Kerja</th>
                    <th class="number">Uang Makan</th>
                    <th class="number">DP Gaji</th>
                    <th class="number">Koreksi Pengurangan</th>
                    <th class="number">Koreksi Penambahan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $bpjs = \App\Services\PayrollReportBuilder::bpjsEmployment($row);
                        $advance = \App\Services\PayrollReportBuilder::salaryAdvanceAmount($row);
                        $net = \App\Services\PayrollReportBuilder::netSalary($row);
                    @endphp
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="employee">{{ $row->employee_no }}</td>
                        <td class="employee">{{ $row->full_name }}</td>
                        <td class="center">{{ $row->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</td>
                        <td class="number">{{ $row->attendance_days }}</td>
                        <td class="number net">{{ $rupiah($net) }}</td>
                        <td class="number">{{ $rupiah($row->gross_salary) }}</td>
                        <td class="number">{{ $rupiah($bpjs) }}</td>
                        <td class="number">{{ $rupiah($row->uniform_amount) }}</td>
                        <td class="number">{{ $rupiah($row->equipment_amount) }}</td>
                        <td class="number">{{ $rupiah($row->meal_amount) }}</td>
                        <td class="number">{{ $rupiah($advance) }}</td>
                        <td class="number">{{ $rupiah($row->correction_minus) }}</td>
                        <td class="number">{{ $rupiah($row->correction_plus) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Dicetak otomatis pada {{ now()->translatedFormat('d M Y H:i') }}</div>
</body>
</html>
