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
        Schema::create('link_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_rule_id')->constrained()->cascadeOnDelete();
            $table->string('condition_type', 50);
            $table->string('operator', 50);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->index(['link_rule_id', 'condition_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_rule_conditions');
    }
};
