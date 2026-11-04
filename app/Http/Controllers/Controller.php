<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', static::DEFAULT_PER_PAGE);

        return in_array($perPage, static::PER_PAGE_OPTIONS, true)
            ? $perPage
            : static::DEFAULT_PER_PAGE;
    }

    protected function searchTerm(Request $request): string
    {
        return trim((string) $request->input('search', ''));
    }

    protected function dateRange(Request $request): array
    {
        $from = $this->dateInput($request, 'date_from');
        $to   = $this->dateInput($request, 'date_to');

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    protected function applyDateRange($query, string $column, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate($column, '<=', $to));
    }

    protected function statusesMatching(string $search, array $labels): array
    {
        if ($search === '') {
            return [];
        }

        return collect($labels)
            ->map(fn ($label) => is_array($label) ? ($label['label'] ?? '') : $label)
            ->filter(fn ($label) => mb_stripos($label, $search) !== false)
            ->keys()
            ->all();
    }

    protected function dateInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }

    protected function statusFilter(Request $request, array $allowed, string $key = 'status'): ?string
    {
        $status = $request->input($key);

        return in_array($status, $allowed, true) ? $status : null;
    }

    protected function numberRanges(Request $request, array $defs): array
    {
        $ranges = [];

        foreach ($defs as $key => $def) {
            $min = $this->numberInput($request, "{$key}_min");
            $max = $this->numberInput($request, "{$key}_max");

            if ($min !== null && $max !== null && (float) $min > (float) $max) {
                [$min, $max] = [$max, $min];
            }

            $min = $min !== null ? $this->trimNumber($min) : null;
            $max = $max !== null ? $this->trimNumber($max) : null;

            $ranges[$key] = [
                'label' => $def['label'] ?? $key,
                'unit'  => $def['unit'] ?? null,
                'step'  => $def['step'] ?? 'any',
                'min'   => $min,
                'max'   => $max,
                'options' => isset($def['options'])
                    ? collect($def['options'])
                        ->filter(fn ($v) => is_numeric($v))
                        ->map(fn ($v) => $this->trimNumber($v))
                        ->unique()
                        ->sortBy(fn ($v) => (float) $v)
                        ->values()
                        ->all()
                    : null,
            ];
        }

        return $ranges;
    }

    protected function applyNumberRange($query, $column, ?array $range)
    {
        if (! $range) {
            return $query;
        }

        $isRaw  = $column instanceof \Illuminate\Contracts\Database\Query\Expression;
        $grammar = $isRaw ? $query->getQuery()->getGrammar() : null;
        $sql    = $isRaw ? $column->getValue($grammar) : null;

        if ($range['min'] !== null) {
            $isRaw
                ? $query->whereRaw("({$sql}) >= ?", [$range['min']])
                : $query->where($column, '>=', $range['min']);
        }

        if ($range['max'] !== null) {
            $isRaw
                ? $query->whereRaw("({$sql}) <= ?", [$range['max']])
                : $query->where($column, '<=', $range['max']);
        }

        return $query;
    }

    protected function trimNumber($value): string
    {
        $s = (string) $value;

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }

    protected function numberInput(Request $request, string $key): ?string
    {
        $value = str_replace([',', ' '], '', (string) $request->input($key, ''));

        return is_numeric($value) ? $value : null;
    }
}