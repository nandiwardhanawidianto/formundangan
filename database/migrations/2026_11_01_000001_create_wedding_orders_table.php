<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wedding_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 80)->unique();
            $table->string('customer_whatsapp', 20);
            $table->string('shopee_username', 120)->nullable();
            $table->enum('product_type', ['digital', 'print'])->nullable();
            $table->enum('photo_package', ['with_photo', 'no_photo'])->nullable();
            $table->enum('display_order', ['female_first', 'male_first'])->default('female_first');
            $table->json('payload')->nullable();
            $table->enum('status', ['draft', 'submitted', 'needs_revision', 'approved'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('notification_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void { Schema::dropIfExists('wedding_orders'); }
};
