@extends('tte.layout')

@section('title',
    $type === 'upcoming'
    ? 'Akan Expired'
    : ($type === 'expired'
    ? 'Sudah Expired'
    : 'User per Unit
    Kerja'))

@section('content')
    <div class="content-wrap">
        <div class="page-heading">
            <div>
                <p class="eyebrow">DATA ADMIN</p>
                <h1>{{ $type === 'upcoming' ? 'Sertifikat Akan Expired' : ($type === 'expired' ? 'Sertifikat Sudah Expired' : 'User per Unit Kerja') }}
                </h1>
                <p class="subtitle">Daftar rinci ini hanya dapat diakses setelah admin login.</p>
            </div>
            <div class="admin-badge">Admin: {{ auth()->user()->name }}</div>
        </div>

        <form class="filter-row records-filter" method="GET"
            action="{{ $type === 'upcoming' ? route('admin.records.upcoming') : ($type === 'expired' ? route('admin.records.expired') : route('admin.records.index')) }}">
            <label>Pencarian
                <input type="search" name="q" value="{{ $queryText }}" placeholder="Nama, email, atau unit kerja">
            </label>
            <label>Unit kerja
                <select name="unit">
                    <option value="">Semua unit kerja</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit }}" @selected($selectedUnit === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button button-primary" type="submit">Terapkan</button>
            @if ($queryText || $selectedUnit)
                <a class="clear-filter"
                    href="{{ $type === 'upcoming' ? route('admin.records.upcoming') : ($type === 'expired' ? route('admin.records.expired') : route('admin.records.index')) }}">Reset</a>
            @endif
        </form>

        <section class="panel records-panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ number_format($records->total(), 0, ',', '.') }} data</h2>
                    <p>
                        @if ($type === 'upcoming')
                            Sertifikat dengan tanggal berakhir sampai {{ $warningDays }} hari ke depan.
                        @elseif ($type === 'expired')
                            Sertifikat berstatus expired atau tanggal berakhir telah lewat.
                        @else
                            Data pengguna dari spreadsheet.
                        @endif
                    </p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIK (tersamarkan)</th>
                            <th>Email Gov</th>
                            <th>Unit Kerja</th>
                            <th>Nama UNOR</th>
                            <th>Status Pengguna</th>
                            <th>Status Sertifikat</th>
                            <th>Tanggal Berakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td>{{ $record->nama ?: '—' }}</td>
                                <td class="nowrap">{{ $record->maskedNik() }}</td>
                                <td>{{ $record->email ?: '—' }}</td>
                                <td>{{ $record->unit_kerja ?: '—' }}</td>
                                <td>{{ $record->nama_unor ?: '—' }}</td>
                                <td><span class="status-tag">{{ $record->status_pengguna ?: '—' }}</span></td>
                                <td><span
                                        class="status-tag {{ mb_strtolower((string) $record->status_sertifikat) === 'expired' ? 'status-danger' : '' }}">{{ $record->status_sertifikat ?: '—' }}</span>
                                </td>
                                <td class="nowrap">{{ $record->tanggal_berakhir?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @empty <tr>
                                <td colspan="8" class="empty-state">Tidak ada data yang cocok dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $records->links() }}</div>
        </section>
    </div>
@endsection
