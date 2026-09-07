<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CauseResource;
use App\Models\Cause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CauseController extends Controller
{
    /**
     * Liste publique des causes (avec featured en premier)
     */
    public function index()
    {
        $causes = Cause::withCount('pledges')
            ->withSum('pledges', 'amount')
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return CauseResource::collection($causes);
    }

    /**
     * Créer une cause (admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'goal_amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cause = Cause::create([
            'title' => $request->title,
            'description' => $request->description,
            'goal_amount' => $request->goal_amount,
            'is_active' => true,
            'is_featured' => false,
        ]);

        return new CauseResource($cause);
    }

    /**
     * Voir une cause
     */
    public function show(Cause $cause)
    {
        $cause->loadCount('pledges');
        $cause->loadSum('pledges', 'amount');

        return new CauseResource($cause);
    }

    /**
     * Mettre à jour une cause (admin)
     */
    public function update(Request $request, Cause $cause)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'goal_amount' => 'sometimes|numeric|min:0.01',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Mettre à jour uniquement les champs autorisés
        $cause->update($request->only([
            'title',
            'description',
            'goal_amount',
            'is_active',
            'is_featured',
        ]));

        return new CauseResource($cause);
    }

    /**
     * Supprimer une cause (admin)
     */
    public function destroy(Cause $cause)
    {
        $cause->delete();

        return response()->json(['message' => 'Cause supprimée'], 204);
    }

    /**
     * Marquer/Démarquer comme featured (admin)
     */
    public function toggleFeatured(Cause $cause)
    {
        $cause->update(['is_featured' => ! $cause->is_featured]);

        return new CauseResource($cause);
    }
}
