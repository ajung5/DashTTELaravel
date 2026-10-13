<?php

namespace App\Http\Controllers;

use App\Models\TteRecord;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller {
    public function index(Request $request): View {
        $filters = $request->validate([
            'unit' => ['nullable', 'string', 'max:190']
        ]);
        $unit = $filters['unit'] ?? null;
        $summary = Cache::remember('tte.dashboard.summary.' . md5((string) $unit), 60, function () use ($unit): array {
            $query = TteRecord::query();

            if ($unit !== null && $unit !== '') {
                $query->where('unit_kerja', $unit);
            }

            $registeredStatuses = $this->normalizedStatuses('registered');
            $activatedStatuses = $this->normalizedStatuses('activated');
            $issuedStatuses = $this->normalizedStatuses('issued');
            $expiredStatuses = $this->normalizedStatuses('expired');

            $registered = (clone $query)
                ->whereIn(new Expression('LOWER(status_pengguna)'), $registeredStatuses)
                ->count();
            $activated = (clone $query)->whereIn(new Expression('LOWER(status_pengguna)'), $activatedStatuses)->count();
            $issued = (clone $query)->whereIn(new Expression('LOWER(status_sertifikat)'), $issuedStatuses)->count();
            $today = CarbonImmutable::today(config('tte.timezone', 'Asia/Jakarta'))->toDateString();
            $expired = (clone $query)
                ->where(function ($builder) use ($expiredStatuses, $today): void {
                    $builder
                        ->whereIn(new Expression('LOWER(status_sertifikat)'), $expiredStatuses)
                        ->orWhere(function ($dateQuery) use ($today): void {
                            $dateQuery->whereNotNull('tanggal_berakhir')->whereDate('tanggal_berakhir', '<', $today);
                        });
                })
                ->count();
            $total = (clone $query)->count();

            $employment = (clone $query)
                ->whereNotNull('status_kepegawaian')
                ->select('status_kepegawaian')
                ->selectRaw('COUNT(*) AS total')
                ->groupBy('status_kepegawaian')
                ->orderByDesc('total')
                ->get();

            $employeeStatus = (clone $query)
                ->whereNotNull('status_pegawai')
                ->select('status_pegawai')
                ->selectRaw('COUNT(*) AS total')
                ->groupBy('status_pegawai')
                ->orderByDesc('total')
                ->get();

            $unitTotals = (clone $query)
                ->whereNotNull('unit_kerja')
                ->select('unit_kerja')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw(
                    'SUM(CASE WHEN LOWER(status_sertifikat) IN (' .
                        implode(',', array_fill(0, count($issuedStatuses), '?')) .
                        ') THEN 1 ELSE 0 END) AS issued',
                    $issuedStatuses
                )
                ->groupBy('unit_kerja')
                ->orderByDesc('issued')
                ->get();

            return [
                'total' => $total,
                'registered' => $registered,
                'activated' => $activated,
                'issued' => $issued,
                'expired' => $expired,
                'issuance_rate' => $registered > 0 ? round(($issued / $registered) * 100, 2) : 0,
                'activation_rate' => $registered > 0 ? round(($activated / $registered) * 100, 2) : 0,
                'employment' => $employment,
                'employee_status' => $employeeStatus,
                'unit_totals' => $unitTotals
            ];
        });

        return view('tte.dashboard', [
            'summary' => $summary,
            'units' => TteRecord::query()
                ->whereNotNull('unit_kerja')
                ->distinct()
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja'),
            'selectedUnit' => $unit,
            'lastSyncedAt' => Cache::get('tte.last_synced_at')
        ]);
    }

    /** @return list<string> */
    private function normalizedStatuses(string $key): array {
        return array_map('mb_strtolower', config('tte.status_values.' . $key, []));
    }
}
