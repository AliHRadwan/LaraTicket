<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'price',
        'total_tickets',
        'available_tickets',
        'start_datetime',
        'end_datetime',
        'location',
        'image_url',
    ];

    protected $appends = ['image'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
        ];
    }

    protected function image(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->image_url) {
                return null;
            }

            if (str_starts_with($this->image_url, 'http')) {
                return $this->image_url;
            }

            return Storage::disk('public')->url($this->image_url);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
