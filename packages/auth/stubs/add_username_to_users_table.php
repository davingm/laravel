<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username')->nullable()->unique()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            });
        }
    }
};
