<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Seat;
use App\Models\Sector;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@ticketsystem.com')->first();

        // Orden 1: dos asientos numerados de un sector con mapa de asientos
        $sectorWithSeats = Sector::whereHas('seats')->first();

        if ($sectorWithSeats) {
            $seats = $sectorWithSeats->seats()->available()->take(2)->get();

            if ($seats->isNotEmpty()) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => Order::generateOrderNumber(),
                    'subtotal' => $sectorWithSeats->price * $seats->count(),
                    'tax' => 0,
                    'total' => $sectorWithSeats->price * $seats->count(),
                    'status' => 'paid',
                    'payment_method' => 'card',
                    'paid_at' => now()->subDays(2),
                ]);

                foreach ($seats as $seat) {
                    Ticket::create([
                        'order_id' => $order->id,
                        'event_id' => $sectorWithSeats->event_id,
                        'sector_id' => $sectorWithSeats->id,
                        'seat_id' => $seat->id,
                        'price' => $sectorWithSeats->price,
                        'qr_code' => Ticket::generateQrCode(),
                        'entry_code' => Ticket::generateEntryCode(),
                    ]);

                    $seat->update(['status' => 'sold', 'is_available' => false]);
                }
            }
        }

        // Orden 2: sector sin mapa de asientos (venta por cantidad)
        $sectorWithoutSeats = Sector::doesntHave('seats')->first();

        if ($sectorWithoutSeats) {
            $quantity = 3;

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $sectorWithoutSeats->price * $quantity,
                'tax' => 0,
                'total' => $sectorWithoutSeats->price * $quantity,
                'status' => 'paid',
                'payment_method' => 'card',
                'paid_at' => now()->subDay(),
            ]);

            for ($i = 0; $i < $quantity; $i++) {
                Ticket::create([
                    'order_id' => $order->id,
                    'event_id' => $sectorWithoutSeats->event_id,
                    'sector_id' => $sectorWithoutSeats->id,
                    'seat_id' => null,
                    'price' => $sectorWithoutSeats->price,
                    'qr_code' => Ticket::generateQrCode(),
                    'entry_code' => Ticket::generateEntryCode(),
                ]);
            }

            $sectorWithoutSeats->decrement('available', $quantity);
        }
    }
}
