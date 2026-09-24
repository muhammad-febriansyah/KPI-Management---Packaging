<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Slip Gaji</title>
<style>
    @page { margin: 24px 28px; }
    * { box-sizing: border-box; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; font-size: 12px; margin: 0; }
    .slip { padding-bottom: 12px; }
    .slip + .slip { page-break-before: always; }
    .header { display: table; width: 100%; border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }
    .header .company { display: table-cell; vertical-align: middle; }
    .header .company .name { font-size: 16px; font-weight: bold; color: #0f172a; }
    .header .company .code { font-size: 10px; color: #64748b; margin-top: 2px; }
    .header .title { display: table-cell; vertical-align: middle; text-align: right; }
    .header .title .label { font-size: 15px; font-weight: bold; color: #1d4ed8; }
    .header .title .period { font-size: 10px; color: #64748b; margin-top: 2px; }
    .employee { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .employee td { padding: 3px 0; font-size: 11.5px; }
    .employee td.label { color: #64748b; width: 130px; }
    .employee td.value { font-weight: bold; color: #0f172a; }
    table.amounts { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.amounts th { text-align: left; background: #eff6ff; color: #1d4ed8; font-size: 10.5px; text-transform: uppercase; letter-spacing: .03em; padding: 6px 8px; border: 1px solid #dbeafe; }
    table.amounts td { padding: 6px 8px; border: 1px solid #e2e8f0; font-size: 11.5px; }
    table.amounts td.amount { text-align: right; font-variant-numeric: tabular-nums; }
    .columns { display: table; width: 100%; }
    .columns .col { display: table-cell; width: 50%; vertical-align: top; }
    .columns .col:first-child { padding-right: 8px; }
    .columns .col:last-child { padding-left: 8px; }
    .summary { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .summary td { padding: 7px 10px; font-size: 12.5px; }
    .summary td.label { color: #334155; }
    .summary td.amount { text-align: right; font-weight: bold; font-variant-numeric: tabular-nums; }
    .summary tr.net { background: #ecfdf5; }
    .summary tr.net td { font-size: 14px; color: #047857; font-weight: bold; border-top: 2px solid #10b981; }
    .footer { margin-top: 22px; display: table; width: 100%; }
    .footer .box { display: table-cell; width: 50%; text-align: center; font-size: 10.5px; color: #64748b; }
    .footer .box .line { margin-top: 40px; border-top: 1px solid #94a3b8; padding-top: 4px; }
    .note { margin-top: 16px; font-size: 9.5px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
@php
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $periodLabel = \Illuminate\Support\Carbon::parse($dateFrom)->translatedFormat('d M Y').' – '.\Illuminate\Support\Carbon::parse($dateTo)->translatedFormat('d M Y');
@endphp
@foreach ($rows as $row)
    @php
        $bpjsHealth = \App\Services\PayrollReportBuilder::bpjsHealth($row);
        $bpjs = \App\Services\PayrollReportBuilder::bpjsEmployment($row);
        $advance = \App\Services\PayrollReportBuilder::salaryAdvanceAmount($row);
        $net = \App\Services\PayrollReportBuilder::netSalary($row);
    @endphp
    <div class="slip">
        <div class="header">
            <div class="company">
                <div class="name">{{ $client->name }}</div>
                <div class="code">{{ $client->code }}</div>
            </div>
            <div class="title">
                <div class="label">SLIP GAJI</div>
                <div class="period">Periode {{ $periodLabel }}</div>
            </div>
        </div>

        <table class="employee">
            <tr><td class="label">Nama karyawan</td><td class="value">{{ $row->full_name }}</td><td class="label">Jenis kelamin</td><td class="value">{{ $row->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
            <tr><td class="label">No. karyawan</td><td class="value">{{ $row->employee_no }}</td><td class="label">Total hari masuk</td><td class="value">{{ $row->attendance_days }} hari</td></tr>
        </table>

        <div class="columns">
            <div class="col">
                <table class="amounts">
                    <tr><th colspan="2">Pendapatan</th></tr>
                    <tr><td>Gaji kotor (borongan)</td><td class="amount">{{ $rupiah($row->gross_salary) }}</td></tr>
                    <tr><td>Koreksi penambahan</td><td class="amount">{{ $rupiah($row->correction_plus) }}</td></tr>
                </table>
            </div>
            <div class="col">
                <table class="amounts">
                    <tr><th colspan="2">Potongan</th></tr>
                    <tr><td>BPJS Ketenagakerjaan ({{ number_format((float) $row->bpjs_employment_percent, 2) }}%)</td><td class="amount">{{ $rupiah($bpjs) }}</td></tr>
                    <tr><td>BPJS Kesehatan ({{ number_format((float) $row->bpjs_health_percent, 2) }}%)</td><td class="amount">{{ $rupiah($bpjsHealth) }}</td></tr>
                    <tr><td>Seragam (Kaos/Celana)</td><td class="amount">{{ $rupiah($row->uniform_amount) }}</td></tr>
                    <tr><td>Perlengkapan kerja</td><td class="amount">{{ $rupiah($row->equipment_amount) }}</td></tr>
                    <tr><td>Uang makan</td><td class="amount">{{ $rupiah($row->meal_amount) }}</td></tr>
                    <tr><td>DP gaji{{ $row->salary_advance_type === 'percentage' ? ' ('.number_format((float) $row->salary_advance_value, 2).'%)' : '' }}</td><td class="amount">{{ $rupiah($advance) }}</td></tr>
                    <tr><td>Koreksi pengurangan</td><td class="amount">{{ $rupiah($row->correction_minus) }}</td></tr>
                </table>
            </div>
        </div>

        <table class="summary">
            <tr class="net"><td class="label">Gaji bersih diterima</td><td class="amount">{{ $rupiah($net) }}</td></tr>
        </table>

        <div class="footer">
            <div class="box">Diperiksa oleh<div class="line">Admin Penggajian</div></div>
            <div class="box">Diterima oleh<div class="line">{{ $row->full_name }}</div></div>
        </div>

        <div class="note">Slip gaji ini dicetak otomatis oleh sistem pada {{ now()->translatedFormat('d M Y H:i') }} dan sah tanpa tanda tangan basah.</div>
    </div>
@endforeach
</body>
</html>
