<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to_id')->nullable()->after('user_id');

            $table->index('assigned_to_id');
        });

        Schema::table('recorded_bug_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('message');
            $table->string('attachment_type')->nullable()->after('attachment_path');
        });
    }

    public function down()
    {
        Schema::table('recorded_bugs', function (Blueprint $table) {
            $table->dropColumn('assigned_to_id');
        });

        Schema::table('recorded_bug_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_type']);
        });
    }
};
