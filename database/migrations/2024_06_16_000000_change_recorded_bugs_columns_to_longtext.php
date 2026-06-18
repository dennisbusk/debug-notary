<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->longText('message')->change();
            $table->longText('user_note')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->text('message')->change();
            $table->text('user_note')->nullable()->change();
        });
    }
};
