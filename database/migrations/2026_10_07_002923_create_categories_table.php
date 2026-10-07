<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('type', 10); // income | expense
            $table->string('color', 7)->default('#10b981');
            $table->timestamps();

            // Satu nama kategori hanya boleh sekali per pengguna per tipe
            $table->unique(['user_id', 'type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};