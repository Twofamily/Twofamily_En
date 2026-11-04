<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Cancellable
{
    use SoftDeletes;

    public function cancel(string $reason, ?int $userId = null): void
    {
        $this->forceFill([
            'cancel_reason'        => $reason,
            'cancelled_by_user_id' => $userId ?? auth()->id(),
        ])->save();

        $this->delete();
    }

    public function isCancelled(): bool
    {
        return $this->trashed();
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function scopeCancelledFilter($query, ?string $status)
    {
        return $status === 'cancelled'
            ? $query->onlyTrashed()
            : $query->withTrashed()->when($status !== null, fn ($q) => $q->whereNull($this->getQualifiedDeletedAtColumn()));
    }
}
