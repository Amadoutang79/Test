<?php

namespace App\Http\Controllers\Api;

use App\Events\CauseGoalReached;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePledgeRequest;
use App\Http\Resources\PledgeResource;
use App\Models\Cause;
use App\Models\Pledge;
use Illuminate\Support\Facades\DB;

class PledgeController extends Controller
{
    /**
     * Créer un pledge (avec gestion de la concurrence)
     */
    public function store(StorePledgeRequest $request, Cause $cause)
    {
        // Vérifier que la cause est active
        if (! $cause->is_active) {
            return response()->json([
                'message' => 'Cette cause n\'est plus active',
            ], 422);
        }

        // Transaction avec verrouillage pour éviter les problèmes de concurrence
        $pledge = DB::transaction(function () use ($request, $cause) {
            // Verrouillage pour la concurrence
            $cause = Cause::where('id', $cause->id)
                ->lockForUpdate()
                ->first();

            // Vérifier à nouveau (au cas où la cause a été désactivée)
            if (! $cause->is_active) {
                throw new \Exception('Cette cause n\'est plus active');
            }

            // Créer le pledge
            $pledge = $cause->pledges()->create([
                'pledger_name' => $request->pledger_name,
                'pledger_email' => $request->pledger_email,
                'amount' => $request->amount,
            ]);

            // Calculer le total
            $totalPledged = $cause->pledges()->sum('amount');

            // Vérifier si le but est atteint
            if ($totalPledged >= $cause->goal_amount && $cause->is_active) {
                $cause->update(['is_active' => false]);

                // Dispatch de l'événement
                event(new CauseGoalReached($cause));
            }

            return $pledge;
        });

        return new PledgeResource($pledge);
    }
}
