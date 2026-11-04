<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'id_payment';

    protected $fillable = [
        'id_invoice',
        'id_receipt',
        'paid_at',
        'amount',
        'method',
        'bank_name',
        'reference_no',
        'note',
        'slip_path',
        'slip_original_name',
        'slip_hash',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount'  => 'decimal:2',
    ];

    public const METHODS = [
        'transfer' => 'โอนเงิน',
        'cash'     => 'เงินสด',
        'cheque'   => 'เช็ค',
    ];

    public const SLIP_MIMES  = 'jpg,jpeg,png,pdf';
    public const SLIP_MAX_KB = 5120;

    public const SLIP_DIR = 'payment-slips';

    public const METHODS_WITHOUT_SLIP = ['cash'];

    public static function slipRules(): array
    {
        $rules = ['nullable', 'file', 'mimes:' . self::SLIP_MIMES, 'max:' . self::SLIP_MAX_KB];

        if (Setting::get('payment.require_slip', true)) {
            array_unshift($rules, 'required_unless:method,' . implode(',', self::METHODS_WITHOUT_SLIP));
        }

        return $rules;
    }

    public static function slipRequiredFor(?string $method): bool
    {
        return Setting::get('payment.require_slip', true)
            && ! in_array($method, self::METHODS_WITHOUT_SLIP, true);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'id_invoice', 'id_invoice')->withTrashed();
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'id_receipt', 'id_receipt')->withTrashed();
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    public function hasSlip(): bool
    {
        return ! empty($this->slip_path);
    }

    public function isPdfSlip(): bool
    {
        return $this->hasSlip() && str_ends_with(strtolower($this->slip_path), '.pdf');
    }

    public function ownerCode(): string
    {
        return $this->invoice->code_inv
            ?? $this->receipt->code_rc
            ?? '-';
    }
}
