<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wedding_orders', function (Blueprint $table) {
            $table->string('cms_sync_status', 32)->nullable();
            $table->string('cms_sync_error', 255)->nullable();
            $table->timestamp('cms_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wedding_orders', function (Blueprint $table) {
            $table->dropColumn(['cms_sync_status', 'cms_sync_error', 'cms_synced_at']);
        });
    }
};
