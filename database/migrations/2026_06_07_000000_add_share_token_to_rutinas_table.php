<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            if (!Schema::hasColumn('rutinas', 'share_token')) {
                $table->string('share_token', 32)->nullable()->unique()->after('publica');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            if (Schema::hasColumn('rutinas', 'share_token')) {
                $table->dropUnique(['share_token']);
                $table->dropColumn('share_token');
            }
        });
    }
};