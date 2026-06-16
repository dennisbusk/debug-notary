<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recorded_bugs', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique()->nullable();
            $table->string('log_type')->default('system');
            $table->longText('message');
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->integer('count')->default(1);
            $table->json('trend_data')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->longText('screenshot')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('url')->nullable();
            $table->json('browser_data')->nullable();
            $table->longText('user_note')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_role')->nullable();
            $table->string('tenant_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // Indekser
            $table->index('user_id');
            $table->index('last_seen_at');
            $table->index('hash');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recorded_bugs');
    }
};
