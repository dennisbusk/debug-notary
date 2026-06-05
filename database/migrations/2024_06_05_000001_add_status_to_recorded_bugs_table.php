<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            // Tilføj status kolonnen hvis den mangler (fra tidligere opdateringer)
            if (! Schema::hasColumn('recorded_bugs', 'status')) {
                $table->string('status')->default('open')->after('severity');
                $table->index('status');
            }

            // Gør file og line nullable for at undgå IntegrityConstraintViolation ved f.eks. JS fejl
            $table->string('file')->nullable()->change();
            $table->integer('line')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            if (Schema::hasColumn('recorded_bugs', 'status')) {
                $table->dropColumn('status');
            }
            $table->string('file')->nullable(false)->change();
            $table->integer('line')->nullable(false)->change();
        });
    }
};
