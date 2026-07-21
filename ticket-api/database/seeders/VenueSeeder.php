<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Estadio Defensores del Chaco',
                'address' => 'Av. Artigas',
                'city' => 'Asunción',
                'country' => 'Paraguay',
                'capacity' => 45000,
                'description' => 'Estadio principal de la selección paraguaya.',
                'image_url' => 'https://picsum.photos/seed/venue1/800/400',
                'features' => ['Estacionamiento', 'Accesibilidad', 'Wifi'],
            ],
            [
                'name' => 'Jockey Club Paraguayo',
                'address' => 'Av. Aviadores del Chaco',
                'city' => 'Asunción',
                'country' => 'Paraguay',
                'capacity' => 8000,
                'description' => 'Centro de eventos y exposiciones.',
                'image_url' => 'https://picsum.photos/seed/venue2/800/400',
                'features' => ['Estacionamiento', 'Catering', 'Aire acondicionado'],
            ],
            [
                'name' => 'Teatro Municipal',
                'address' => 'Presidente Franco 371',
                'city' => 'Asunción',
                'country' => 'Paraguay',
                'capacity' => 1200,
                'description' => 'Teatro histórico en el centro de la ciudad.',
                'image_url' => 'https://picsum.photos/seed/venue3/800/400',
                'features' => ['Butacas numeradas', 'Aire acondicionado'],
            ],
            [
                'name' => 'Costanera de Asunción',
                'address' => 'Av. Costanera',
                'city' => 'Asunción',
                'country' => 'Paraguay',
                'capacity' => 20000,
                'description' => 'Espacio abierto para festivales al aire libre.',
                'image_url' => 'https://picsum.photos/seed/venue4/800/400',
                'features' => ['Espacio abierto', 'Food trucks'],
            ],
            [
                'name' => 'Centro de Convenciones CONMEBOL',
                'address' => 'Autopista Ñu Guasu',
                'city' => 'Luque',
                'country' => 'Paraguay',
                'capacity' => 3000,
                'description' => 'Espacio moderno para conferencias y congresos.',
                'image_url' => 'https://picsum.photos/seed/venue5/800/400',
                'features' => ['Wifi', 'Salas privadas', 'Estacionamiento'],
            ],
        ];

        foreach ($venues as $venue) {
            Venue::create($venue);
        }
    }
}
