<?php

namespace App\Models\Concerns;

use App\Models\User;

trait RecordsCreator
{
    public static function bootRecordsCreator(): void
    {
        static::creating(function ($model) {
            $model->created_by_user_id ??= auth()->id();
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
