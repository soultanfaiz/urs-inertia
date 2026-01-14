<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportingNote extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'app_request_id',
        'user_id',
        'title',
        'note',
        'image_path',
    ];

    public function appRequest(): BelongsTo
    {
        return $this->belongsTo(AppRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
