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
        Schema::create('produits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('dossier_id')
                ->constrained('dossiers')
                ->cascadeOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('quantite', 12, 2)->default(0);
            $table->string('unite', 20)->default('KG');
            $table->decimal('ratio', 6, 4)->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
