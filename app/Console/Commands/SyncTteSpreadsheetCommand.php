<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SyncTteSpreadsheetCommand extends Command {
    protected $signature = 'tte:sync-google-sheet';

    protected $description = 'Sinkronkan data TTE dari Google Spreadsheet ke database aplikasi';

    public function handle(GoogleSheetsClient $sheets): int {
        try {
            $values = $sheets->fetchValues();

            if (count($values) < 2) {
                $this->warn('Spreadsheet belum berisi baris data. Data lama dipertahankan.');

                return self::SUCCESS;
            }

            $headers = array_map(fn(mixed $value): string => $this->normalize((string) $value), $values[0]);
            $columnIndexes = $this->columnIndexes($headers);

            $requiredColumns = ['nama', 'unit_kerja', 'status_pengguna', 'status_sertifikat', 'tanggal_berakhir'];
            $missingColumns = array_diff($requiredColumns, array_keys($columnIndexes));

            if ($missingColumns !== []) {
                $missingLabels = array_map(
                    fn(string $field): string => (string) config('tte.columns.' . $field),
                    $missingColumns
                );
                $this->error(
                    'Header kolom penting tidak ditemukan: ' .
                        implode(', ', $missingLabels) .
                        '. Sesuaikan TTE_COL_* di .env.'
                );

                return self::FAILURE;
            }

            $batch = (string) Str::uuid();
            $now = now();
            $records = [];

            foreach (array_slice($values, 1, null, true) as $offset => $row) {
                if (
                    !is_array($row) ||
                    count(array_filter($row, fn(mixed $value): bool => trim((string) $value) !== '')) === 0
                ) {
                    continue;
                }

                $record = [
                    'source_row' => (int) $offset + 1,
                    'sync_batch' => $batch,
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                foreach (array_keys(config('tte.columns', [])) as $field) {
                    $index = $columnIndexes[$field] ?? null;
                    $value = $index === null ? null : trim((string) ($row[$index] ?? ''));
                    $record[$field] = $value === '' ? null : $value;
                }

                $record['nik'] = $this->maskNik($record['nik'] ?? null);
                $record['tanggal_berakhir'] = $this->parseDate($record['tanggal_berakhir'] ?? null);
                $records[] = $record;
            }

            if ($records === []) {
                $this->warn('Tidak ada baris pegawai valid. Data lama dipertahankan.');

                return self::SUCCESS;
            }

            DB::transaction(function () use ($records, $batch): void {
                foreach (array_chunk($records, 500) as $chunk) {
                    DB::table('tte_records')->upsert(
                        $chunk,
                        ['source_row'],
                        [
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
                            'jenis_jabatan',
                            'updated_at'
                        ]
                    );
                }

                DB::table('tte_records')->where('sync_batch', '!=', $batch)->delete();
            }, attempts: 3);

            Cache::forget('tte.dashboard.summary');
            Cache::forget('tte.dashboard.progress');
            Cache::put('tte.last_synced_at', $now->toIso8601String(), now()->addYear());

            $this->info('Sinkronisasi berhasil: ' . number_format(count($records), 0, ',', '.') . ' baris.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Sinkronisasi gagal. Periksa konfigurasi spreadsheet, service account, dan log aplikasi.');

            return self::FAILURE;
        }
    }

    /** @return array<string, int> */
    private function columnIndexes(array $headers): array {
        $normalizedHeaders = array_flip($headers);
        $indexes = [];

        foreach (config('tte.columns', []) as $field => $label) {
            $normalizedLabel = $this->normalize((string) $label);

            if (isset($normalizedHeaders[$normalizedLabel])) {
                $indexes[$field] = $normalizedHeaders[$normalizedLabel];
            }
        }

        return $indexes;
    }

    private function normalize(string $value): string {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim($value))) ?? '';
    }

    private function parseDate(?string $value): ?string {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $formats = array_unique([(string) config('tte.date_format', 'd/m/Y'), 'Y-m-d', 'd-m-Y', 'd/m/y']);

        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!' . $format, trim($value));

                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                // Try the next configured display format.
            }
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function maskNik(?string $value): ?string {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $nik = preg_replace('/\s+/', '', trim($value)) ?? '';

        if (str_contains($nik, '*')) {
            return $nik;
        }

        if (strlen($nik) <= 8) {
            return str_repeat('*', strlen($nik));
        }

        return substr($nik, 0, 6) . str_repeat('*', 6) . substr($nik, -4);
    }
}
