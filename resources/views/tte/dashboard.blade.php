@extends('tte.layout')

@section('title', 'Ringkasan TTE')

@section('content')
    <div class="content-wrap">
        <div class="page-heading">
            <div>
                <p class="eyebrow">MONITORING TTE</p>
                <h1>Ringkasan TTE Kabupaten Subang</h1>
                <p class="subtitle">Pantau penerbitan sertifikat dan progres unit kerja.</p>
            </div>
            <div class="sync-meta"><span class="status-dot"></span>Data tersinkron@if ($lastSyncedAt)
                    ·
                    {{ \Illuminate\Support\Carbon::parse($lastSyncedAt)->timezone(config('tte.timezone', 'Asia/Jakarta'))->format('d M Y, H:i') }}
                @endif
            </div>
        </div>

        <form class="filter-row" method="GET" action="{{ route('dashboard') }}">
            <label>Unit kerja
                <select name="unit" onchange="this.form.submit()">
                    <option value="">Semua unit kerja</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit }}" @selected($selectedUnit === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
            </label>
            @if ($selectedUnit)
                <a class="clear-filter" href="{{ route('dashboard') }}">Hapus filter</a>
            @endif
        </form>

        <section class="kpi-grid" aria-label="Indikator utama TTE">
            <article class="kpi-card"><span class="kpi-label">Data
                    pegawai</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong><small>Total baris
                    data tersinkron</small></article>
            <article class="kpi-card"><span class="kpi-label">User
                    terdaftar</span><strong>{{ number_format($summary['registered'], 0, ',', '.') }}</strong><small>Status
                    pengguna terdaftar</small></article>
            <article class="kpi-card"><span class="kpi-label">Sudah
                    aktivasi</span><strong>{{ number_format($summary['activated'], 0, ',', '.') }}</strong><small>{{ number_format($summary['activation_rate'], 2, ',', '.') }}%
                    dari user terdaftar</small><span class="meter"><i
                        style="width: {{ min(100, $summary['activation_rate']) }}%"></i></span></article>
            <article class="kpi-card"><span class="kpi-label">TTE
                    terbit</span><strong>{{ number_format($summary['issued'], 0, ',', '.') }}</strong><small>{{ number_format($summary['issuance_rate'], 2, ',', '.') }}%
                    dari user terdaftar</small><span class="meter"><i
                        style="width: {{ min(100, $summary['issuance_rate']) }}%"></i></span></article>
            <article class="kpi-card kpi-warn"><span class="kpi-label">Sertifikat
                    expired</span><strong>{{ number_format($summary['expired'], 0, ',', '.') }}</strong><small>Rincian
                    tersedia untuk admin</small></article>
        </section>

        <section class="chart-grid">
            <article class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Status Kepegawaian</h2>
                        <p>Komposisi pegawai berdasarkan data sumber</p>
                    </div>
                </div>
                @php($employmentTotal = max(1, (int) $summary['total']))
                <div class="bar-list">
                    @forelse ($summary['employment'] as $item)
                        @php($percent = round(($item->total / $employmentTotal) * 100, 1))
                        <div class="bar-item">
                            <div class="bar-caption">
                                <span>{{ $item->status_kepegawaian }}</span><strong>{{ number_format($item->total, 0, ',', '.') }}
                                    <small>({{ number_format($percent, 1, ',', '.') }}%)</small></strong></div>
                            <div class="bar-track"><i style="width: {{ min(100, $percent) }}%"></i></div>
                        </div>
                    @empty <p class="empty-state">Belum ada data status kepegawaian.</p>
                    @endforelse
                </div>
            </article>
            <article class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Status Pegawai</h2>
                        <p>Perbandingan pegawai aktif dan nonaktif</p>
                    </div>
                </div>
                @php($employeeTotal = max(1, (int) $summary['employee_status']->sum('total')))
                <div class="bar-list">
                    @forelse ($summary['employee_status'] as $item)
                        @php($percent = round(($item->total / $employeeTotal) * 100, 1))
                        <div class="bar-item">
                            <div class="bar-caption">
                                <span>{{ $item->status_pegawai }}</span><strong>{{ number_format($item->total, 0, ',', '.') }}
                                    <small>({{ number_format($percent, 1, ',', '.') }}%)</small></strong></div>
                            <div class="bar-track"><i class="bar-alt" style="width: {{ min(100, $percent) }}%"></i></div>
                        </div>
                    @empty <p class="empty-state">Belum ada data status pegawai.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="panel progress-panel" id="progress">
            <div class="panel-heading">
                <div>
                    <h2>Progress Penerbitan per Unit Kerja</h2>
                    <p>Agregat jumlah TTE terbit dibandingkan seluruh data pada unit kerja.</p>
                </div><span class="public-note">Data agregat</span>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Unit kerja</th>
                            <th>Total pegawai</th>
                            <th>TTE terbit</th>
                            <th>Progress penerbitan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary['unit_totals'] as $unitRow)
                            @php($unitPercent = $unitRow->total > 0 ? round(($unitRow->issued / $unitRow->total) * 100, 1) : 0)
                            <tr>
                                <td>{{ $unitRow->unit_kerja }}</td>
                                <td>{{ number_format($unitRow->total, 0, ',', '.') }}</td>
                                <td>{{ number_format($unitRow->issued, 0, ',', '.') }}</td>
                                <td>
                                    <div class="inline-progress">
                                        <span>{{ number_format($unitPercent, 1, ',', '.') }}%</span><i><b
                                                style="width: {{ min(100, $unitPercent) }}%"></b></i></div>
                                </td>
                            </tr>
                        @empty <tr>
                                <td colspan="4" class="empty-state">Data belum tersedia. Jalankan sinkronisasi Google
                                    Spreadsheet terlebih dahulu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
