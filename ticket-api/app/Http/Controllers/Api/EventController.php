<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Listado público/admin de eventos con filtros, búsqueda, orden y paginación.
     */
    public function index(Request $request)
    {
        $query = Event::query()->with(['category', 'venue']);

        // Si no es una petición admin, solo mostrar publicados
        if (! $request->boolean('admin')) {
            $query->published();
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->venue_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        $sortBy = $request->get('sort_by', 'start_date');
        $sortDir = $request->get('sort_dir', 'asc');
        $allowedSorts = ['start_date', 'title', 'views', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min((int) $request->get('per_page', 12), 50);

        return response()->json($query->paginate($perPage));
    }

    public function featured()
    {
        $events = Event::published()
            ->featured()
            ->with(['category', 'venue'])
            ->orderBy('start_date')
            ->take(8)
            ->get();

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'venue_id' => ['required', 'exists:venues,id'],
            'image_url' => ['nullable', 'string'],
            'banner_url' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:draft,published,cancelled,sold_out'],
            'is_featured' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] ?? 'draft';

        $event = Event::create($data);

        return response()->json($event->load(['category', 'venue']), 201);
    }

    public function show(Event $event)
    {
        $event->increment('views');

        return response()->json(
            $event->load(['category', 'venue', 'sectors'])
        );
    }

    public function update(Request $request, Event $event)
    {
        if ($event->hasSoldTickets()) {
            return response()->json([
                'message' => 'No se puede modificar un evento que ya tiene tickets vendidos.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'venue_id' => ['sometimes', 'required', 'exists:venues,id'],
            'image_url' => ['nullable', 'string'],
            'banner_url' => ['nullable', 'string'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:draft,published,cancelled,sold_out'],
            'is_featured' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event->update($validator->validated());

        return response()->json($event->load(['category', 'venue']));
    }

    public function destroy(Event $event)
    {
        if ($event->hasSoldTickets()) {
            return response()->json([
                'message' => 'No se puede eliminar un evento que ya tiene tickets vendidos.',
            ], 422);
        }

        $event->delete();

        return response()->json(['message' => 'Evento eliminado correctamente.']);
    }

    public function publish(Event $event)
    {
        if (! $event->canBePublished()) {
            return response()->json([
                'message' => 'El evento debe tener al menos un sector con asientos generados antes de publicarse.',
            ], 422);
        }

        $event->update(['status' => 'published']);

        return response()->json($event);
    }

    /**
     * Duplica un evento junto con todos sus sectores y asientos (sin tickets vendidos).
     */
    public function duplicate(Event $event)
    {
        return DB::transaction(function () use ($event) {
            $newEvent = $event->replicate(['views']);
            $newEvent->title = $event->title . ' (Copia)';
            $newEvent->status = 'draft';
            $newEvent->is_featured = false;
            $newEvent->views = 0;
            $newEvent->save();

            foreach ($event->sectors as $sector) {
                $newSector = $sector->replicate(['available']);
                $newSector->event_id = $newEvent->id;
                $newSector->available = $sector->capacity;
                $newSector->save();

                $seatsToInsert = [];
                foreach ($sector->seats as $seat) {
                    $seatsToInsert[] = [
                        'sector_id' => $newSector->id,
                        'row_char' => $seat->row_char,
                        'seat_number' => $seat->seat_number,
                        'code' => $newSector->id . '-' . $seat->row_char . $seat->seat_number . '-' . uniqid(),
                        'is_reserved' => false,
                        'is_available' => true,
                        'status' => 'available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($seatsToInsert)) {
                    Seat::insert($seatsToInsert);
                }
            }

            return response()->json($newEvent->load(['category', 'venue', 'sectors']), 201);
        });
    }
}
