<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_request_id',
        'user_id',
        'status',
        'reason',
        'type',
    ];

    protected $casts = [
        // Kita tidak bisa langsung cast ke satu Enum karena bisa berisi nilai dari dua Enum yang berbeda.
        // 'status' => RequestStatus::class,
    ];

    protected $appends = [
        'status_label',
        'status_enum',
    ];

    /**
     * Accessor untuk mendapatkan objek Enum yang benar (RequestStatus atau VerificationStatus).
     */
    public function getStatusEnumAttribute(): RequestStatus | VerificationStatus
    {
        // Coba buat Enum dari RequestStatus
        $requestStatus = RequestStatus::tryFrom($this->status);
        if ($requestStatus) {
            return $requestStatus;
        }

        // Jika gagal, coba buat dari VerificationStatus
        // Kita asumsikan jika bukan RequestStatus, pasti VerificationStatus
        return VerificationStatus::from($this->status);
    }

    /**
     * Accessor untuk mendapatkan label status yang bisa dibaca manusia.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->getStatusEnumAttribute()->label();
    }

    /**
     * Get the app request that owns the history.
     */
    public function appRequest(): BelongsTo
    {
        return $this->belongsTo(AppRequest::class);
    }

    /**
     * Get the user (admin) that created the history record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the support documents for the history record.
     */
    public function docSupports(): HasMany
    {
        return $this->hasMany(RequestDocSupport::class);
    }

    /**
     * Get the support images for the history record.
     */
    public function imageSupports(): HasMany
    {
        return $this->hasMany(RequestImageSupport::class);
    }
}
