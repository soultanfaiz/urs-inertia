<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDocSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_history_id',
        'request_status',
        'file_path',
        'file_name',
        'verification_status',
        'reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_status' => \App\Enums\RequestStatus::class,
        'verification_status' => VerificationStatus::class,
    ];

    /**
     * Get the request history that owns the document.
     */
    public function requestHistory(): BelongsTo
    {
        return $this->belongsTo(RequestHistory::class);
    }
}
