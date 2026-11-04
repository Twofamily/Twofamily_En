<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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