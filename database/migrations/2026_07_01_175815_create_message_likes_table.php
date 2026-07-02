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
        Schema::create('message_likes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id')
                ->constrained()
                ->cascadeOnDelete();

            // Anonymous client UUID
            $table->uuid('client_id');

            // Type of reaction (e.g. 'like', 'heart', 'laugh')
            $table->string('type', 20)->default('like');

            $table->timestamps();

            // Prevent same user from doing the SAME reaction twice on one message
            $table->unique(['message_id', 'client_id', 'type']);

            // Faster lookup
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_likes');
    }
};