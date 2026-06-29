<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habbo_imaging_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('hotel', 16)->default('com');
            $table->string('source_version', 191)->unique();
            $table->string('status', 64)->default('idle');
            $table->string('external_variables_url')->nullable();
            $table->string('figuredata_url')->nullable();
            $table->string('figuremap_url')->nullable();
            $table->string('asset_base_url')->nullable();
            $table->string('asset_name_template')->nullable();
            $table->string('source_path')->nullable();
            $table->string('parsed_path')->nullable();
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habbo_imaging_versions');
    }
};
