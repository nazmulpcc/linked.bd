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
        Schema::create('link_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('priority');
            $table->text('destination_url');
            $table->boolean('is_fallback')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['link_id', 'priority']);
            $table->index(['link_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_rules');
    }
};
