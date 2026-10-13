<?php

return [
    'spreadsheet_id' => env('TTE_GOOGLE_SPREADSHEET_ID'),
    'range' => env('TTE_GOOGLE_SHEET_RANGE', 'Data!A1:Z'),
    'credentials_path' => env('TTE_GOOGLE_CREDENTIALS_PATH', 'storage/app/private/google-service-account.json'),
    'expiry_warning_days' => (int) env('TTE_EXPIRY_WARNING_DAYS', 30),
    'timezone' => env('TTE_TIMEZONE', 'Asia/Jakarta'),
    'date_format' => env('TTE_SHEET_DATE_FORMAT', 'd/m/Y'),
    'columns' => [
        'nama' => env('TTE_COL_NAMA', 'NAMA'),
        'nik' => env('TTE_COL_NIK', 'NIK'),
        'email' => env('TTE_COL_EMAIL', 'Email Gov'),
        'unit_kerja' => env('TTE_COL_UNIT_KERJA', 'UNIT KERJA'),
        'nama_unor' => env('TTE_COL_NAMA_UNOR', 'NAMA UNOR'),
        'status_kepegawaian' => env('TTE_COL_STATUS_KEPEGAWAIAN', 'STATUS KEPEGAWAIAN'),
        'status_pegawai' => env('TTE_COL_STATUS_PEGAWAI', 'STATUS PEGAWAI'),
        'status_pengguna' => env('TTE_COL_STATUS_PENGGUNA', 'Status Pengguna'),
        'status_sertifikat' => env('TTE_COL_STATUS_SERTIFIKAT', 'Status Sertifikat'),
        'tanggal_berakhir' => env('TTE_COL_TANGGAL_BERAKHIR', 'Tanggal berakhir'),
        'rumpun_jabatan' => env('TTE_COL_RUMPUN_JABATAN', 'RUMPUN JABATAN'),
        'jenis_jabatan' => env('TTE_COL_JENIS_JABATAN', 'JENIS JABATAN'),
    ],
    'status_values' => [
        'registered' => ['verified', 'new', 'terdaftar', 'sudah daftar'],
        'activated' => ['verified', 'aktif', 'sudah aktivasi'],
        'issued' => ['issued', 'terbit', 'aktif'],
        'expired' => ['expired', 'kedaluwarsa', 'kadaluarsa'],
        'not_registered' => ['belum daftar'],
    ],
];
