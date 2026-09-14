<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->unsignedBigInteger('central_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->dropColumn('central_id');
        });
    }
};
