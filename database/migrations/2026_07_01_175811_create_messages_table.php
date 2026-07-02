<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Anonymous client UUID
            $table->uuid('client_id')->index();

            // Example: Guest 1234
            $table->string('guest_name', 50);

            // Chat message
            $table->text('message');

            // JSON column to store emoji reaction counts (e.g. {"thumbs_up": 2, "heart": 1})
            $table->json('reactions_count')->nullable();

            $table->timestamps();

            // Useful for loading latest messages
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};