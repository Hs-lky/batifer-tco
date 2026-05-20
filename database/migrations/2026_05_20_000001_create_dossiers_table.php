<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ref', 50);
            $table->string('frs', 100);
            $table->string('pays', 50)->nullable();
            $table->string('incoterm', 3)->default('CFR');
            $table->string('famille', 50)->nullable()->default('AUTRE');
            $table->string('devise', 3)->default('EUR');
            $table->string('unite', 20)->nullable()->default('KG');
            $table->string('cert_origine', 10)->default('non');
            $table->decimal('px_devise', 12, 2)->default(0);
            $table->decimal('taux', 8, 4)->default(10.7200);
            $table->decimal('qte', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->decimal('fret_montant_orig', 12, 2)->default(0);
            $table->string('fret_devise_orig', 3)->default('MAD');
            $table->decimal('fret_taux_orig', 8, 4)->default(10.7200);
            $table->string('user_id', 50)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossiers');
    }
};
