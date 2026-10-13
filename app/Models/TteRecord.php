<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TteRecord extends Model {
    protected $fillable = [
        'source_row',
        'sync_batch',
        'nama',
        'nik',
        'email',
        'unit_kerja',
        'nama_unor',
        'status_kepegawaian',
        'status_pegawai',
        'status_pengguna',
        'status_sertifikat',
        'tanggal_berakhir',
        'rumpun_jabatan',
        'jenis_jabatan'
    ];

    protected function casts(): array {
        return [
            'tanggal_berakhir' => 'date'
        ];
    }

    public function scopeWithCertificateStatus(Builder $query, string $status): Builder {
        return $query->whereRaw('LOWER(status_sertifikat) = ?', [mb_strtolower($status)]);
    }

    public function maskedNik(): string {
        $nik = preg_replace('/\s+/', '', (string) $this->nik) ?? '';

        if ($nik === '') {
            return '-';
        }

        if (str_contains($nik, '*')) {
            return $nik;
        }

        if (strlen($nik) <= 8) {
            return str_repeat('*', strlen($nik));
        }

        return substr($nik, 0, 6) . str_repeat('*', max(4, strlen($nik) - 10)) . substr($nik, -4);
    }
}
