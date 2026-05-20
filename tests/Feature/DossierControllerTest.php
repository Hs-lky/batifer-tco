<?php

namespace Tests\Feature;

use App\Models\Dossier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DossierControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_dossier(): void
    {
        $response = $this->post('/dossiers', [
            'ref' => 'TEST-001',
            'frs' => 'Fournisseur Test',
            'incoterm' => 'CFR',
            'devise' => 'EUR',
            'pays' => 'FR',
            'famille' => 'ACIER',
            'unite' => 'KG',
            'px_devise' => 100.50,
            'taux' => 10.72,
            'qte' => 500,
            'notes' => 'Test dossier',
        ]);

        $response->assertRedirect(route('dossiers.index'));

        $this->assertDatabaseHas('dossiers', [
            'ref' => 'TEST-001',
            'frs' => 'Fournisseur Test',
        ]);
    }

    public function test_cannot_create_dossier_without_ref(): void
    {
        $response = $this->post('/dossiers', [
            'frs' => 'Fournisseur Test',
            'incoterm' => 'CFR',
            'devise' => 'EUR',
        ]);

        $response->assertSessionHasErrors(['ref']);
    }

    public function test_can_list_dossiers(): void
    {
        Dossier::create([
            'ref' => 'LIST-001',
            'frs' => 'Fournisseur Liste',
            'incoterm' => 'FOB',
            'devise' => 'USD',
            'user_id' => 'anonymous',
        ]);

        $response = $this->get('/dossiers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('dossiers'));
    }

    public function test_can_delete_dossier(): void
    {
        $dossier = Dossier::create([
            'ref' => 'DEL-001',
            'frs' => 'Fournisseur A Supprimer',
            'incoterm' => 'EXW',
            'devise' => 'EUR',
            'user_id' => 'anonymous',
        ]);

        $response = $this->delete("/dossiers/{$dossier->id}");

        $response->assertRedirect(route('dossiers.index'));

        $this->assertDatabaseMissing('dossiers', [
            'id' => $dossier->id,
        ]);
    }
}
