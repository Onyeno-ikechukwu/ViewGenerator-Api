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
        Schema::create('posters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('video_url');
            $table->string('music_url');
            $table->enum('payment_package', ['small', 'medium', 'large']);
            $table->integer('amount');
            $table->integer('views')->default(0);
            $table->enum('status', ['paid', 'pending', 'expired']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posters');
    }
};
