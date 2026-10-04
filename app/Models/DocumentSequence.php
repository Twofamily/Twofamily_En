<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ตัวนับเลขที่เอกสาร
 * ห้ามแก้ last_number ตรง ๆ ให้ใช้ผ่าน DocumentNumberService เท่านั้น
 */
class DocumentSequence extends Model
{
    protected $table = 'document_sequences';

    protected $fillable = ['doc_type', 'period', 'last_number'];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }
}