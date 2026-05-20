<?php

namespace Tests\Feature;

use App\Models\Dossier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_returns_file(): void
    {
        Dossier::create([
            'ref' => 'EXP-001',
            'frs' => 'Fournisseur Export',
            'incoterm' => 'CFR',
            'devise' => 'EUR',
            'user_id' => 'anonymous',
        ]);

        $response = $this->get('/export/csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
