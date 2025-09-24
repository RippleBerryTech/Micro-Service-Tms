<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaign_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient'); // email address
            $table->boolean('success')->default(false); // sent or failed
            $table->text('error_message')->nullable(); // store error if failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaign_logs');
    }
};
