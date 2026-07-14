<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Document extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            $document->id ??= 'doc_'.Str::uuid();
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
