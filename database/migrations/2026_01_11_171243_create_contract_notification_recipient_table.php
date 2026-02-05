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
        Schema::create('contract_notification_recipient', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id');
            $table->foreignId('notification_recipient_id');

            $table->timestamps();

            // Unique pair
            $table->unique(
                ['contract_id', 'notification_recipient_id'],
                'contract_recipient_unique'
            );

            // Foreign keys
            $table->foreign('contract_id', 'fk_cnr_contract')
                ->references('id')->on('contracts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('notification_recipient_id', 'fk_cnr_recipient')
                ->references('id')->on('notification_recipients')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_notification_recipient');
    }
};
