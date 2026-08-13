<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refund_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('refund_request_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable(); // 'image' or 'video'
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('refund_request_id')->references('id')->on('refund_requests')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_chat_messages');
    }
};
