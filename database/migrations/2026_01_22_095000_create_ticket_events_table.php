<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_events')) {
            return;
        }

        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('actor_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // examples: created, updated, status_changed, assigned, forwarded, commented
            $table->string('event_type', 64);

            // for assignment/forwarding style events
            $table->foreignId('from_user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // flexible payload: changed_fields, old/new values, comment excerpt, etc.
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['ticket_id', 'event_type']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_events');
    }
};
