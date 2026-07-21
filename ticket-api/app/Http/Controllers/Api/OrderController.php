<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Crea una orden con sus tickets a partir de asientos previamente reservados
     * por el usuario (o sectores sin mapa de asientos, usando solo cantidad).
     *
     * Payload esperado:
     * {
     *   "items": [
     *     { "sector_id": 1, "seat_ids": [10, 11] },
     *     { "sector_id": 2, "seat_ids": [] }  // sector sin asientos numerados
     *   ],
     *   "payment_method": "card"
     * }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.sector_id' => ['required', 'integer', 'exists:sectors,id'],
            'items.*.seat_ids' => ['nullable', 'array'],
            'items.*.seat_ids.*' => ['integer', 'exists:seats,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $subtotal = 0;
            $ticketsData = [];

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
            ]);

            foreach ($request->items as $item) {
                $sector = \App\Models\Sector::lockForUpdate()->findOrFail($item['sector_id']);
                $seatIds = $item['seat_ids'] ?? [];

                if (! empty($seatIds)) {
                    // Sector con mapa de asientos: deben estar reservados por este proceso
                    $seats = Seat::where('sector_id', $sector->id)
                        ->whereIn('id', $seatIds)
                        ->lockForUpdate()
                        ->get();

                    if ($seats->count() !== count($seatIds)) {
                        throw new \Exception('Alguno de los asientos seleccionados no existe.');
                    }

                    $invalid = $seats->whereNotIn('status', ['reserved', 'available']);
                    if ($invalid->isNotEmpty()) {
                        throw new \Exception('Alguno de los asientos ya no está disponible.');
                    }

                    foreach ($seats as $seat) {
                        $ticketsData[] = [
                            'order_id' => $order->id,
                            'event_id' => $sector->event_id,
                            'sector_id' => $sector->id,
                            'seat_id' => $seat->id,
                            'price' => $sector->price,
                            'qr_code' => Ticket::generateQrCode(),
                            'entry_code' => Ticket::generateEntryCode(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $subtotal += $sector->price;

                        $seat->update([
                            'status' => 'sold',
                            'is_reserved' => false,
                            'is_available' => false,
                            'reserved_until' => null,
                        ]);
                    }
                } else {
                    // Sector sin mapa de asientos: se compra por cantidad
                    $quantity = $item['quantity'] ?? 1;

                    if ($sector->available < $quantity) {
                        throw new \Exception("No hay suficiente disponibilidad en el sector {$sector->name}.");
                    }

                    for ($i = 0; $i < $quantity; $i++) {
                        $ticketsData[] = [
                            'order_id' => $order->id,
                            'event_id' => $sector->event_id,
                            'sector_id' => $sector->id,
                            'seat_id' => null,
                            'price' => $sector->price,
                            'qr_code' => Ticket::generateQrCode(),
                            'entry_code' => Ticket::generateEntryCode(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $subtotal += $sector->price;
                    }

                    $sector->decrement('available', $quantity);
                }
            }

            $tax = round($subtotal * 0.0, 2); // ajustar % de impuestos si aplica
            $total = $subtotal + $tax;

            Ticket::insert($ticketsData);

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'paid', // pago simulado
                'paid_at' => now(),
            ]);

            return response()->json(
                $order->load('tickets.event', 'tickets.sector', 'tickets.seat'),
                201
            );
        });
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json(
            $order->load('tickets.event', 'tickets.sector', 'tickets.seat')
        );
    }

    public function userTickets(Request $request)
    {
        $tickets = Ticket::whereHas('order', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)->where('status', 'paid');
        })
            ->with(['event', 'sector', 'seat', 'order'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($tickets);
    }

    public function tickets(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json(
            $order->tickets()->with(['event', 'sector', 'seat'])->get()
        );
    }
}
