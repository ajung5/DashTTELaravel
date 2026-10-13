<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TteRecord;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;

class TteRecordController extends Controller
{
    public function index(Request $request, string $type = 'all'): View
    {
        abort_unless(in_array($type, ['all', 'upcoming', 'expired'], true), 404);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:190'],
        ]);
        $query = TteRecord::query();

        if (! empty($filters['q'])) {
            $search = trim($filters['q']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('unit_kerja', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['unit'])) {
            $query->where('unit_kerja', $filters['unit']);
        }

        $today = CarbonImmutable::today(config('tte.timezone', 'Asia/Jakarta'));

        if ($type === 'upcoming') {
            $query->whereBetween('tanggal_berakhir', [$today->toDateString(), $today->addDays((int) config('tte.expiry_warning_days', 30))->toDateString()]);
        } elseif ($type === 'expired') {
            $expiredStatuses = array_map('mb_strtolower', config('tte.status_values.expired', []));
            $query->where(function (Builder $builder) use ($expiredStatuses, $today): void {
                $builder->whereIn(new Expression('LOWER(status_sertifikat)'), $expiredStatuses)
                    ->orWhere(function (Builder $dateQuery) use ($today): void {
                        $dateQuery->whereNotNull('tanggal_berakhir')->whereDate('tanggal_berakhir', '<', $today->toDateString());
                    });
            });
        }

        $records = $query->orderBy('tanggal_berakhir')->orderBy('nama')->paginate(25)->withQueryString();

        return view('tte.admin.records', [
            'records' => $records,
            'type' => $type,
            'queryText' => $filters['q'] ?? '',
            'selectedUnit' => $filters['unit'] ?? '',
            'units' => TteRecord::query()->whereNotNull('unit_kerja')->distinct()->orderBy('unit_kerja')->pluck('unit_kerja'),
            'warningDays' => (int) config('tte.expiry_warning_days', 30),
        ]);
    }
}
