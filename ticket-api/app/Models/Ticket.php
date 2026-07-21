<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'event_id', 'sector_id', 'seat_id',
        'price', 'qr_code', 'is_used', 'used_at', 'entry_code',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public static function generateQrCode(): string
    {
        return strtoupper(bin2hex(random_bytes(10)));
    }

    public static function generateEntryCode(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
