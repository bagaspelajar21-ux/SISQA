<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pekerjaan Mingguan</title>
    <style>
        @page { margin: 1.5cm; }
        body {
            color: #172033;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
        }
        h1 {
            color: #1D4ED8;
            font-size: 20px;
            margin-bottom: 4px;
        }
        .subtitle, .date { color: #64748B; }
        .day {
            border: 1px solid #DCE3ED;
            border-radius: 6px;
            margin-top: 12px;
            padding: 10px 12px;
        }
        .day h2 {
            border-bottom: 1px solid #E2E8F0;
            color: #1E3A8A;
            font-size: 13px;
            margin: 0 0 8px;
            padding-bottom: 5px;
        }
        .entry { margin: 6px 0; white-space: pre-wrap; }
        .empty { color: #94A3B8; font-style: italic; }
    </style>
</head>
<body>
    <h1>Laporan Pekerjaan Mingguan</h1>
    <div class="subtitle">{{ $user->name }}</div>
    <div class="date">
        {{ $weekStart->translatedFormat('d F Y') }} – {{ $weekEnd->translatedFormat('d F Y') }}
    </div>

    @foreach ($weekDays as $day)
        <section class="day">
            <h2>{{ $day['date']->translatedFormat('l, d F Y') }}</h2>
            @forelse ($day['work_logs'] as $workLog)
                <div class="entry">• {{ $workLog->description }}</div>
            @empty
                <div class="empty">Belum ada catatan pekerjaan.</div>
            @endforelse
        </section>
    @endforeach
</body>
</html>
