<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevelopmentActivity extends Model
{
    use HasUuids, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'app_request_id',
        'iteration_count',
        'description',
        'start_date',
        'end_date',
        'is_completed',
        'pic',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'pic' => 'array',
    ];

    /**
     * Get the app request that owns the development activity.
     */
    public function appRequest(): BelongsTo
    {
        return $this->belongsTo(AppRequest::class);
    }

    /**
     * Get all of the sub-activities for the DevelopmentActivity.
     */
    public function subActivities(): HasMany
    {
        return $this->hasMany(SubDevelopmentActivity::class);
    }
}
