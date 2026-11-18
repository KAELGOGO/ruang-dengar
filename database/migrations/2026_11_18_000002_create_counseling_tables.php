<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Not "sessions": Laravel already uses that table for logins.
        Schema::create('counseling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listener_id')->constrained('users')->cascadeOnDelete();
            // Unique: one slot can never be booked twice, even with two requests at once.
            // Cancelling sets it to null, which frees the slot.
            $table->foreignId('availability_slot_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('topic');
            $table->enum('status', ['scheduled', 'active', 'ended', 'escalated', 'cancelled'])->default('scheduled');
            $table->dateTime('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counseling_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body'); // encrypted by the model cast
            // Set when the message is saved: encrypted text cannot be searched later.
            $table->boolean('flagged')->default(false);
            $table->timestamps();
        });

        Schema::create('escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counseling_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source', ['listener', 'keyword', 'user'])->default('listener');
            $table->text('reason');
            $table->foreignId('psychologist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['open', 'assigned', 'resolved'])->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counseling_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('escalations');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('counseling_sessions');
    }
};
