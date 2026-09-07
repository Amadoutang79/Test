<?php

namespace Tests\Feature;

use App\Models\Cause;
use App\Models\IdempotencyKey;
use App\Models\Pledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer la table idempotency_keys si elle n'existe pas
        // (RefreshDatabase le fait automatiquement)
    }

    public function test_idempotency_prevents_duplicate_pledges()
    {
        // Créer une cause
        $cause = Cause::factory()->create([
            'goal_amount' => 1000,
            'is_active' => true,
        ]);

        $uniqueKey = 'pledge-'.uniqid();

        // 1ère requête - Crée le pledge
        $response1 = $this->postJson("/api/causes/{$cause->id}/pledges", [
            'pledger_name' => 'Jean Dupont',
            'pledger_email' => 'jean@test.com',
            'amount' => 100.50,
        ], [
            'Idempotency-Key' => $uniqueKey,
        ]);

        $response1->assertStatus(201);
        $this->assertDatabaseCount('pledges', 1);

        $firstPledgeId = $response1->json('data.id');

        // 2ème requête - Même clé
        $response2 = $this->postJson("/api/causes/{$cause->id}/pledges", [
            'pledger_name' => 'Jean Dupont',
            'pledger_email' => 'jean@test.com',
            'amount' => 100.50,
        ], [
            'Idempotency-Key' => $uniqueKey,
        ]);

        $response2->assertStatus(200);
        $this->assertDatabaseCount('pledges', 1);

        // Vérifier que c'est le même pledge
        $this->assertEquals(
            $firstPledgeId,
            $response2->json('data.id')
        );
    }

    public function test_different_idempotency_keys_create_different_pledges()
    {
        $cause = Cause::factory()->create([
            'goal_amount' => 1000,
            'is_active' => true,
        ]);

        // Première requête avec clé A
        $response1 = $this->postJson("/api/causes/{$cause->id}/pledges", [
            'pledger_name' => 'Alice',
            'pledger_email' => 'alice@test.com',
            'amount' => 100,
        ], [
            'Idempotency-Key' => 'key-A-123',
        ]);

        $response1->assertStatus(201);
        $this->assertDatabaseCount('pledges', 1);

        // Deuxième requête avec clé B
        $response2 = $this->postJson("/api/causes/{$cause->id}/pledges", [
            'pledger_name' => 'Bob',
            'pledger_email' => 'bob@test.com',
            'amount' => 150,
        ], [
            'Idempotency-Key' => 'key-B-456',
        ]);

        $response2->assertStatus(201);
        $this->assertDatabaseCount('pledges', 2);

        // Les IDs doivent être différents
        $this->assertNotEquals(
            $response1->json('data.id'),
            $response2->json('data.id')
        );
    }

    public function test_idempotency_without_key_creates_pledge()
    {
        $cause = Cause::factory()->create([
            'goal_amount' => 1000,
            'is_active' => true,
        ]);

        // Requête SANS en-tête Idempotency-Key
        $response = $this->postJson("/api/causes/{$cause->id}/pledges", [
            'pledger_name' => 'Sans clé',
            'pledger_email' => 'sans@test.com',
            'amount' => 50,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('pledges', 1);

        // La même requête sans clé crée un autre pledge
        $response2 = $this->postJson("/api/causes/{$cause->id}/pledges", [
            'pledger_name' => 'Sans clé',
            'pledger_email' => 'sans@test.com',
            'amount' => 50,
        ]);

        $response2->assertStatus(201);
        $this->assertDatabaseCount('pledges', 2); // 2 pledges créés
    }

    public function test_idempotency_keys_clean_old_entries()
    {
        // Créer une clé expirée
        IdempotencyKey::create([
            'key' => 'old-key',
            'response' => json_encode(['message' => 'old']),
            'expires_at' => now()->subHours(1),
        ]);

        $cause = Cause::factory()->create([
            'goal_amount' => 1000,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/causes/{$cause->id}/pledges", [
            'pledger_name' => 'Test',
            'pledger_email' => 'test@test.com',
            'amount' => 50,
        ], [
            'Idempotency-Key' => 'old-key',
        ]);

        // La clé expirée doit être ignorée
        $response->assertStatus(201);
        $this->assertDatabaseCount('pledges', 1);
    }
}
