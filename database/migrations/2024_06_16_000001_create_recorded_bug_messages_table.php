<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recorded_bug_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recorded_bug_id')->constrained('recorded_bugs')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable(); // Afsenderen
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('recorded_bug_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('recorded_bug_messages');
    }
};
