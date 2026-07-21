<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectorController extends Controller
{
    public function index(Event $event)
    {
        return response()->json($event->sectors()->withCount('seats')->get());
    }

    public function store(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:20'],
            'layout_config' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['event_id'] = $event->id;
        $data['available'] = $data['capacity'];

        $sector = Sector::create($data);

        return response()->json($sector, 201);
    }

    public function update(Request $request, Sector $sector)
    {
        if ($sector->hasSoldTickets()) {
            return response()->json([
                'message' => 'No se puede modificar un sector con tickets vendidos.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:20'],
            'layout_config' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sector->update($validator->validated());

        return response()->json($sector);
    }

    public function destroy(Sector $sector)
    {
        if ($sector->hasSoldTickets()) {
            return response()->json([
                'message' => 'No se puede eliminar un sector con tickets vendidos.',
            ], 422);
        }

        $sector->delete();

        return response()->json(['message' => 'Sector eliminado correctamente.']);
    }
}
