<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->unsignedInteger('estimate_hours')->nullable()->after('tags');
            $table->unsignedInteger('estimate_minutes')->nullable()->after('estimate_hours');
            $table->timestamp('estimate_accepted_at')->nullable()->after('estimate_minutes');
            $table->unsignedBigInteger('estimate_accepted_by_id')->nullable()->after('estimate_accepted_at');
            $table->string('estimate_accepted_by_name')->nullable()->after('estimate_accepted_by_id');

            $table->index('estimate_accepted_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->dropColumn([
                'estimate_hours',
                'estimate_minutes',
                'estimate_accepted_at',
                'estimate_accepted_by_id',
                'estimate_accepted_by_name',
            ]);
        });
    }
};
