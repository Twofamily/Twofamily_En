<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    public function next(string $type, ?CarbonInterface $date = null): string
    {
        $this->assertType($type);

        $date   ??= now();
        $period   = $this->periodKey($date);

        return DB::transaction(function () use ($type, $date, $period) {

            DB::table('document_sequences')->insertOrIgnore([
                'doc_type'    => $type,
                'period'      => $period,
                'last_number' => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $sequence = DocumentSequence::where('doc_type', $type)
                ->where('period', $period)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->last_number = max(
                $sequence->last_number,
                $this->highestExisting($type, $date)
            ) + 1;

            $sequence->save();

            return $this->format($type, $date, $sequence->last_number);
        });
    }

    public function peek(string $type, ?CarbonInterface $date = null): string
    {
        $this->assertType($type);

        $date ??= now();

        $last = (int) DocumentSequence::where('doc_type', $type)
            ->where('period', $this->periodKey($date))
            ->value('last_number');

        $last = max($last, $this->highestExisting($type, $date));

        return $this->format($type, $date, $last + 1);
    }

    public function format(string $type, CarbonInterface $date, int $number): string
    {
        $digits = Setting::get('doc.running_digits');

        return $this->stem($type, $date)
            . str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
    }

    private function stem(string $type, CarbonInterface $date): string
    {
        $period = $this->periodLabel($date);

        return $this->prefix($type) . '-' . ($period !== '' ? $period . '-' : '');
    }

    private function prefix(string $type): string
    {
        return Setting::get("doc.prefix.{$type}");
    }

    private function year(CarbonInterface $date): int
    {
        return Setting::get('doc.year_format') === 'be'
            ? $date->year + 543
            : $date->year;
    }

    private function periodLabel(CarbonInterface $date): string
    {
        return match (Setting::get('doc.reset_period')) {
            'monthly' => $this->year($date) . '-' . $date->format('m'),
            'never'   => '',
            default   => (string) $this->year($date),
        };
    }

    private function periodKey(CarbonInterface $date): string
    {
        return $this->periodLabel($date) ?: 'all';
    }

    private function highestExisting(string $type, CarbonInterface $date): int
    {
        $info   = Setting::documentTypes()[$type];
        $class  = $info['model'] ?? null;
        $column = $info['column'] ?? null;

        if (! $class || ! $column || ! class_exists($class)) {
            return 0;
        }

        $query = in_array(SoftDeletes::class, class_uses_recursive($class), true)
            ? $class::withTrashed()
            : $class::query();

        $stem    = $this->stem($type, $date);
        $pattern = '/^' . preg_quote($stem, '/') . '(\d+)$/';

        return (int) $query
            ->where($column, 'like', $stem . '%')
            ->pluck($column)
            ->map(fn ($code) => preg_match($pattern, (string) $code, $m) ? (int) $m[1] : 0)
            ->max();
    }

    private function assertType(string $type): void
    {
        if (! array_key_exists($type, Setting::documentTypes())) {
            throw new InvalidArgumentException("ไม่รู้จักประเภทเอกสาร: {$type}");
        }
    }
}