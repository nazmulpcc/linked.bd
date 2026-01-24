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
        Schema::table('link_visits', function (Blueprint $table) {
            $table->foreignId('link_rule_id')->nullable()->after('link_id')
                ->constrained('link_rules')
                ->nullOnDelete();
            $table->text('resolved_destination_url')->nullable()->after('country_code');

            $table->index(['link_id', 'link_rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('link_visits', function (Blueprint $table) {
            $table->dropIndex(['link_id', 'link_rule_id']);
            $table->dropConstrainedForeignId('link_rule_id');
            $table->dropColumn('resolved_destination_url');
        });
    }
};
