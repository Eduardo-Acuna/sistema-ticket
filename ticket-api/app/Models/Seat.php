<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_id', 'row_char', 'seat_number', 'code',
        'is_reserved', 'is_available', 'status',
        'reserved_at', 'reserved_until',
    ];

    protected $casts = [
        'is_reserved' => 'boolean',
        'is_available' => 'boolean',
        'reserved_at' => 'datetime',
        'reserved_until' => 'datetime',
    ];

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Verifica si la reserva temporal de este asiento ya expiró.
     */
    public function reservationExpired(): bool
    {
        return $this->status === 'reserved'
            && $this->reserved_until !== null
            && $this->reserved_until->isPast();
    }
}
