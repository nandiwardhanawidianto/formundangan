<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wedding_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_order_id')->constrained('wedding_orders')->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type', 60);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('wedding_photos'); }
};
