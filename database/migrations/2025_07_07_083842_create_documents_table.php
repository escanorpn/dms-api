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
      Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('id_number')->unique(); // Key used for searching
    $table->string('filename'); // Obfuscated/secret stored filename
    $table->string('original_name');
    $table->string('mime_type');
    $table->integer('size');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
