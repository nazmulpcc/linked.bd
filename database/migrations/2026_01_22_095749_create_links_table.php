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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('alias')->nullable();
            $table->text('destination_url');
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('click_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('qr_path')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'code']);
            $table->unique(['domain_id', 'alias']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
