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
        Schema::create('cout_dossiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('dossier_id')
                ->constrained('dossiers')
                ->cascadeOnDelete();
            $table->string('cout_id', 20);
            $table->decimal('montant', 12, 2)->default(0);
            $table->timestamp('created_at')->nullable();

            $table->unique(['dossier_id', 'cout_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cout_dossiers');
    }
};
