<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'name', 'price', 'capacity',
        'available', 'color', 'layout_config',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'price' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function hasSoldTickets(): bool
    {
        return $this->tickets()->exists();
    }
}
