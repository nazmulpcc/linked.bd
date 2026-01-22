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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('password');
            $table->string('oauth_provider')->nullable()->after('avatar');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider');
            $table->unique(['oauth_provider', 'oauth_provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_oauth_provider_oauth_provider_id_unique');
            $table->dropColumn(['avatar', 'oauth_provider', 'oauth_provider_id']);
        });
    }
};
