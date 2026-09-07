<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Récupérer la clé d'idempotence depuis l'en-tête
        $key = $request->header('Idempotency-Key');

        // 2. Si pas de clé, on laisse passer (pas d'idempotence)
        if (! $key) {
            return $next($request);
        }

        // 3. Vérifier si cette clé existe déjà en base
        $existing = IdempotencyKey::where('key', $key)->first();

        // 4. Si la clé existe, retourner la réponse stockée
        if ($existing) {
            if ($existing->expires_at->isFuture()) {
                return response()->json(
                    json_decode($existing->getRawOriginal('response'), true),
                    200,
                );
            }

            $existing->delete();
        }

        // 5. Première fois : exécuter la requête
        $response = $next($request);

        // 6. Stocker la réponse pour les futures requêtes
        //    UNIQUEMENT si la requête a réussi (status 2xx)
        if ($response->status() >= 200 && $response->status() < 300) {
            IdempotencyKey::create([
                'key' => $key,
                'response' => json_decode($response->getContent(), true),
                'expires_at' => now()->addHours(24), // Expire après 24h
            ]);
        }

        return $response;
    }
}
