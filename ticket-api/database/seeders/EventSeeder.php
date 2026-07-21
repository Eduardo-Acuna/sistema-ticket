<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Seat;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Festival de Rock Nacional',
                'category_id' => 1,
                'venue_id' => 4,
                'days_from_now' => 15,
                'is_featured' => true,
                'sectors' => [
                    ['name' => 'General', 'price' => 80000, 'rows' => 0], // sin mapa de asientos
                    ['name' => 'VIP', 'price' => 250000, 'rows' => 5, 'seats_per_row' => 10],
                ],
            ],
            [
                'title' => 'Paraguay vs Argentina - Eliminatorias',
                'category_id' => 2,
                'venue_id' => 1,
                'days_from_now' => 30,
                'is_featured' => true,
                'sectors' => [
                    ['name' => 'Popular', 'price' => 120000, 'rows' => 0],
                    ['name' => 'Platea', 'price' => 350000, 'rows' => 10, 'seats_per_row' => 20],
                    ['name' => 'Palco VIP', 'price' => 800000, 'rows' => 3, 'seats_per_row' => 10],
                ],
            ],
            [
                'title' => 'Romeo y Julieta - Obra de Teatro',
                'category_id' => 3,
                'venue_id' => 3,
                'days_from_now' => 10,
                'is_featured' => false,
                'sectors' => [
                    ['name' => 'Platea Baja', 'price' => 100000, 'rows' => 12, 'seats_per_row' => 15],
                    ['name' => 'Platea Alta', 'price' => 70000, 'rows' => 8, 'seats_per_row' => 15],
                ],
            ],
            [
                'title' => 'Festival Gastronómico Internacional',
                'category_id' => 4,
                'venue_id' => 4,
                'days_from_now' => 20,
                'is_featured' => true,
                'sectors' => [
                    ['name' => 'Entrada General', 'price' => 50000, 'rows' => 0],
                ],
            ],
            [
                'title' => 'Congreso de Innovación Tecnológica',
                'category_id' => 5,
                'venue_id' => 5,
                'days_from_now' => 45,
                'is_featured' => false,
                'sectors' => [
                    ['name' => 'Asistente', 'price' => 150000, 'rows' => 0],
                    ['name' => 'Premium', 'price' => 400000, 'rows' => 4, 'seats_per_row' => 12],
                ],
            ],
            [
                'title' => 'Concierto Sinfónico de Año Nuevo',
                'category_id' => 1,
                'venue_id' => 3,
                'days_from_now' => 60,
                'is_featured' => true,
                'sectors' => [
                    ['name' => 'Platea', 'price' => 90000, 'rows' => 10, 'seats_per_row' => 18],
                ],
            ],
            [
                'title' => 'Torneo de Tenis Copa Paraguay',
                'category_id' => 2,
                'venue_id' => 2,
                'days_from_now' => 25,
                'is_featured' => false,
                'sectors' => [
                    ['name' => 'General', 'price' => 60000, 'rows' => 0],
                    ['name' => 'Cancha Central', 'price' => 180000, 'rows' => 6, 'seats_per_row' => 14],
                ],
            ],
            [
                'title' => 'Feria del Libro Asunción',
                'category_id' => 4,
                'venue_id' => 2,
                'days_from_now' => 35,
                'is_featured' => false,
                'sectors' => [
                    ['name' => 'Entrada General', 'price' => 25000, 'rows' => 0],
                ],
            ],
        ];

        foreach ($events as $eventData) {
            $event = Event::create([
                'title' => $eventData['title'],
                'description' => 'Descripción de ejemplo para ' . $eventData['title'] . '. Un evento imperdible que no te podés perder.',
                'category_id' => $eventData['category_id'],
                'venue_id' => $eventData['venue_id'],
                'image_url' => 'https://picsum.photos/seed/' . str_replace(' ', '-', $eventData['title']) . '/600/400',
                'banner_url' => 'https://picsum.photos/seed/' . str_replace(' ', '-', $eventData['title']) . '-banner/1200/400',
                'start_date' => now()->addDays($eventData['days_from_now']),
                'end_date' => now()->addDays($eventData['days_from_now'])->addHours(4),
                'status' => 'published',
                'is_featured' => $eventData['is_featured'],
                'views' => rand(50, 5000),
            ]);

            foreach ($eventData['sectors'] as $sectorData) {
                if ($sectorData['rows'] === 0) {
                    // Sector sin mapa de asientos (venta por cantidad)
                    Sector::create([
                        'event_id' => $event->id,
                        'name' => $sectorData['name'],
                        'price' => $sectorData['price'],
                        'capacity' => 500,
                        'available' => 500,
                        'color' => '#667eea',
                    ]);

                    continue;
                }

                $sector = Sector::create([
                    'event_id' => $event->id,
                    'name' => $sectorData['name'],
                    'price' => $sectorData['price'],
                    'capacity' => 0,
                    'available' => 0,
                    'color' => '#764ba2',
                ]);

                $seatsToInsert = [];
                for ($r = 0; $r < $sectorData['rows']; $r++) {
                    $rowChar = chr(ord('A') + $r);
                    for ($s = 1; $s <= $sectorData['seats_per_row']; $s++) {
                        $seatsToInsert[] = [
                            'sector_id' => $sector->id,
                            'row_char' => $rowChar,
                            'seat_number' => $s,
                            'code' => $sector->id . '-' . $rowChar . $s,
                            'is_reserved' => false,
                            'is_available' => true,
                            'status' => 'available',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                Seat::insert($seatsToInsert);

                $total = count($seatsToInsert);
                $sector->update(['capacity' => $total, 'available' => $total]);
            }
        }
    }
}
