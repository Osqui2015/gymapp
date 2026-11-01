<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'user')->update(['role' => 'comun']);
        DB::table('users')->where('role', 'admin')->update(['role' => 'administrador']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'comun')->update(['role' => 'user']);
        DB::table('users')->where('role', 'administrador')->update(['role' => 'admin']);
    }
};
