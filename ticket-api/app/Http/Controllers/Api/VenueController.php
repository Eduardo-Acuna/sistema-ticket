<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VenueController extends Controller
{
    public function index()
    {
        return response()->json(Venue::withCount('events')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $venue = Venue::create($validator->validated());

        return response()->json($venue, 201);
    }

    public function show(Venue $venue)
    {
        return response()->json($venue->loadCount('events'));
    }

    public function update(Request $request, Venue $venue)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $venue->update($validator->validated());

        return response()->json($venue);
    }

    public function destroy(Venue $venue)
    {
        if ($venue->events()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un lugar con eventos asociados.',
            ], 422);
        }

        $venue->delete();

        return response()->json(['message' => 'Lugar eliminado correctamente.']);
    }
}
