<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use App\Enums\RequestStatus;
use App\Enums\Instansi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'instansi',
        'file_path',
        'status',
        'verification_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'instansi' => Instansi::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => RequestStatus::class,
        'verification_status' => VerificationStatus::class,
    ];

    /**
     * Get the user that owns the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the history for the AppRequest.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(RequestHistory::class)->latest();
    }

    /**
     * Get all of the development activities for the AppRequest.
     */
    public function developmentActivities(): HasMany
    {
        return $this->hasMany(DevelopmentActivity::class);
    }
}
