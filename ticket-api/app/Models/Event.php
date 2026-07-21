<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'description', 'category_id', 'venue_id',
        'image_url', 'banner_url', 'start_date', 'end_date',
        'status', 'is_featured', 'views', 'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Un evento se puede publicar solo si tiene sectores con asientos generados.
     */
    public function canBePublished(): bool
    {
        return $this->sectors()->whereHas('seats')->exists();
    }

    /**
     * Un evento no se puede modificar/eliminar si ya tiene tickets vendidos.
     */
    public function hasSoldTickets(): bool
    {
        return $this->tickets()->exists();
    }
}
