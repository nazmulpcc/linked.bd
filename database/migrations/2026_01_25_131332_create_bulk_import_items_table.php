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
        Schema::create('bulk_import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')
                ->constrained('bulk_import_jobs')
                ->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->text('source_url');
            $table->string('status')->index();
            $table->foreignId('link_id')->nullable()->constrained()->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->string('qr_status')->nullable();
            $table->timestamps();

            $table->index(['job_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_import_items');
    }
};
