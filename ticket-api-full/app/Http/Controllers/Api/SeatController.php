<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SeatController extends Controller
{
    /**
     * Genera asientos automáticamente en grid para un sector.
     * Config esperada: { rows: 10, seats_per_row: 20, start_row: 'A', skip_seats: ['A1','A2'] }
     */
    public function generate(Request $request, $sectorId)
    {
        $sector = Sector::findOrFail($sectorId);

        if ($sector->hasSoldTickets()) {
            return response()->json([
                'message' => 'No se pueden regenerar asientos de un sector con tickets vendidos.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rows' => ['required', 'integer', 'min:1', 'max:26'],
            'seats_per_row' => ['required', 'integer', 'min:1', 'max:100'],
            'start_row' => ['nullable', 'string', 'size:1'],
            'skip_seats' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rows = $request->rows;
        $seatsPerRow = $request->seats_per_row;
        $startRow = strtoupper($request->get('start_row', 'A'));
        $skipSeats = collect($request->get('skip_seats', []));

        return DB::transaction(function () use ($sector, $rows, $seatsPerRow, $startRow, $skipSeats) {
            // Elimina asientos existentes que no tengan ticket asociado
            $sector->seats()->whereDoesntHave('ticket')->delete();

            $startIndex = ord($startRow);
            $seatsToInsert = [];

            for ($r = 0; $r < $rows; $r++) {
                $rowChar = chr($startIndex + $r);

                for ($s = 1; $s <= $seatsPerRow; $s++) {
                    $code = $rowChar . $s;

                    if ($skipSeats->contains($code)) {
                        continue;
                    }

                    $seatsToInsert[] = [
                        'sector_id' => $sector->id,
                        'row_char' => $rowChar,
                        'seat_number' => $s,
                        'code' => $sector->id . '-' . $code,
                        'is_reserved' => false,
                        'is_available' => true,
                        'status' => 'available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($seatsToInsert, 500) as $chunk) {
                Seat::insert($chunk);
            }

            $total = count($seatsToInsert);
            $sector->update([
                'capacity' => $total,
                'available' => $total,
            ]);

            return response()->json([
                'message' => "Se generaron {$total} asientos correctamente.",
                'total' => $total,
            ], 201);
        });
    }

    /**
     * Devuelve el layout de asientos de un sector agrupado por fila.
     */
    public function getLayout($sectorId)
    {
        $sector = Sector::with(['seats' => function ($q) {
            $q->orderBy('row_char')->orderBy('seat_number');
        }])->findOrFail($sectorId);

        // Liberar reservas expiradas antes de responder
        $this->releaseExpiredReservations($sector->id);

        $layout = $sector->seats()
            ->orderBy('row_char')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_char')
            ->map(fn ($seats) => $seats->values());

        return response()->json([
            'sector' => $sector->only(['id', 'name', 'price', 'color']),
            'layout' => $layout,
        ]);
    }

    /**
     * Reserva una lista de asientos por 15 minutos.
     */
    public function reserveSeats(Request $request, $sectorId)
    {
        $validator = Validator::make($request->all(), [
            'seat_ids' => ['required', 'array', 'min:1', 'max:10'],
            'seat_ids.*' => ['integer', 'exists:seats,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->releaseExpiredReservations($sectorId);

        return DB::transaction(function () use ($request, $sectorId) {
            $seats = Seat::where('sector_id', $sectorId)
                ->whereIn('id', $request->seat_ids)
                ->lockForUpdate()
                ->get();

            $unavailable = $seats->where('status', '!=', 'available');

            if ($unavailable->isNotEmpty()) {
                return response()->json([
                    'message' => 'Algunos asientos ya no están disponibles.',
                    'unavailable_seats' => $unavailable->pluck('code'),
                ], 409);
            }

            $until = now()->addMinutes(15);

            Seat::whereIn('id', $seats->pluck('id'))->update([
                'status' => 'reserved',
                'is_reserved' => true,
                'reserved_at' => now(),
                'reserved_until' => $until,
            ]);

            return response()->json([
                'message' => 'Asientos reservados por 15 minutos.',
                'reserved_until' => $until,
                'seats' => $seats->pluck('id'),
            ]);
        });
    }

    /**
     * Libera asientos reservados manualmente (ej. usuario cancela selección).
     */
    public function releaseSeats(Request $request, $sectorId)
    {
        $validator = Validator::make($request->all(), [
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['integer', 'exists:seats,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Seat::where('sector_id', $sectorId)
            ->whereIn('id', $request->seat_ids)
            ->where('status', 'reserved')
            ->update([
                'status' => 'available',
                'is_reserved' => false,
                'reserved_at' => null,
                'reserved_until' => null,
            ]);

        return response()->json(['message' => 'Asientos liberados correctamente.']);
    }

    private function releaseExpiredReservations($sectorId): void
    {
        Seat::where('sector_id', $sectorId)
            ->where('status', 'reserved')
            ->where('reserved_until', '<', now())
            ->update([
                'status' => 'available',
                'is_reserved' => false,
                'reserved_at' => null,
                'reserved_until' => null,
            ]);
    }
}
