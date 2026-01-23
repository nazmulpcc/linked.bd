<?php

use App\Models\Link;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->unique()->after('id');
        });

        Link::query()
            ->whereNull('ulid')
            ->orderBy('id')
            ->chunkById(200, function ($links) {
                foreach ($links as $link) {
                    $link->forceFill([
                        'ulid' => (string) Str::ulid(),
                    ])->save();
                }
            });

        Schema::table('links', function (Blueprint $table) {
            $table->ulid('ulid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropUnique(['ulid']);
            $table->dropColumn('ulid');
        });
    }
};
