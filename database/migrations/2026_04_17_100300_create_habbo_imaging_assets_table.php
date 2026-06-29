<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habbo_imaging_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('version_id')->constrained('habbo_imaging_versions')->cascadeOnDelete();
            $table->string('library_name', 191);
            $table->string('source_url')->nullable();
            $table->string('source_path')->nullable();
            $table->string('extracted_path')->nullable();
            $table->string('extension', 32)->nullable();
            $table->string('status', 64)->default('pending');
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('extracted_file_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['version_id', 'library_name']);
            $table->index(['version_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habbo_imaging_assets');
    }
};
