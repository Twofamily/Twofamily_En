<?php

namespace App\Models\Concerns;

use App\Models\Setting;
use App\Services\DocumentNumberService;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use LogicException;

trait HasDocumentCode
{
    public static function bootHasDocumentCode(): void
    {
        static::creating(function ($model) {
            $column = static::documentCodeColumn();

            if (empty($model->{$column})) {
                $model->{$column} = app(DocumentNumberService::class)
                    ->next(static::documentType());
            }
        });
    }

    public static function documentType(): string
    {
        foreach (Setting::documentTypes() as $type => $info) {
            if (($info['model'] ?? null) === static::class) {
                return $type;
            }
        }

        throw new LogicException(
            static::class . ' ยังไม่ได้ลงทะเบียนใน config/settings.php → document_types'
        );
    }

    public static function documentCodeColumn(): string
    {
        return Setting::documentTypes()[static::documentType()]['column'];
    }

    public static function nextDocumentCode(?DateTimeInterface $date = null): string
    {
        return app(DocumentNumberService::class)->peek(
            static::documentType(),
            $date ? Carbon::instance($date) : null
        );
    }
}