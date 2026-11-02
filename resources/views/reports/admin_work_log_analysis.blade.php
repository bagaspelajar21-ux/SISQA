<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Pekerjaan</title>
    <style>
        @page { margin: 1.2cm; }
        body { color: #172033; font-family: Helvetica, Arial, sans-serif; font-size: 9px; line-height: 1.45; }
        h1 { color: #1D4ED8; font-size: 20px; margin: 0 0 4px; }
        h2 { color: #1E3A8A; font-size: 13px; margin: 18px 0 8px; }
        .subtitle { color: #64748B; margin-bottom: 12px; }
        .metrics { width: 100%; border-collapse: collapse; margin: 12px 0 18px; }
        .metrics td { background: #EFF6FF; border: 1px solid #DBEAFE; padding: 9px; width: 25%; }
        .metric-value { color: #1D4ED8; font-size: 17px; font-weight: bold; }
        .metric-label { color: #475569; font-size: 8px; }
        table.data { border-collapse: collapse; width: 100%; }
        .data th { background: #1E3A8A; color: #fff; text-align: left; }
        .data th, .data td { border: 1px solid #DCE3ED; padding: 6px; vertical-align: top; }
        .data tr:nth-child(even) td { background: #F8FAFC; }
        .description { white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Analisis Pekerjaan</h1>
    <div class="subtitle">
        {{ $filters['start_date'] }} – {{ $filters['end_date'] }}
        @if ($filters['user_id'])
            • Filter pengguna: {{ collect($users)->firstWhere('user_id', $filters['user_id'])['name'] ?? 'Pengguna terpilih' }}
        @else
            • Semua pengguna
        @endif
    </div>

    <table class="metrics">
        <tr>
            <td><div class="metric-value">{{ $summary['total_entries'] }}</div><div class="metric-label">Total Catatan</div></td>
            <td><div class="metric-value">{{ $summary['active_days'] }}</div><div class="metric-label">Hari Aktif</div></td>
            <td><div class="metric-value">{{ $summary['contributors'] }}</div><div class="metric-label">Kontributor</div></td>
            <td><div class="metric-value">{{ $summary['average_entries_per_active_day'] }}</div><div class="metric-label">Rata-rata per Hari Aktif</div></td>
        </tr>
    </table>

    <h2>Aktivitas Harian</h2>
    <table class="data">
        <thead><tr><th>Tanggal</th><th>Jumlah Catatan</th></tr></thead>
        <tbody>
            @foreach ($daily_activity as $day)
                @if ($day['entry_count'] > 0)
                    <tr><td>{{ $day['date'] }}</td><td>{{ $day['entry_count'] }}</td></tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <h2>Kontribusi Pengguna</h2>
    <table class="data">
        <thead><tr><th>No</th><th>Nama Pengguna</th><th>Total Catatan</th><th>Hari Aktif</th><th>Rata-rata per Hari Aktif</th></tr></thead>
        <tbody>
            @forelse ($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user['name'] }}</td>
                    <td>{{ $user['entry_count'] }}</td>
                    <td>{{ $user['active_days'] }}</td>
                    <td>{{ $user['average_entries_per_active_day'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada catatan pekerjaan pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Rincian Pekerjaan</h2>
    <table class="data">
        <thead><tr><th>Tanggal</th><th>Pengguna</th><th>Uraian Pekerjaan</th></tr></thead>
        <tbody>
            @forelse ($work_logs as $work_log)
                <tr>
                    <td>{{ $work_log['work_date'] }}</td>
                    <td>{{ $work_log['user_name'] }}</td>
                    <td class="description">{{ $work_log['description'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Tidak ada pekerjaan untuk ditampilkan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
