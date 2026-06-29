<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habbo_imaging_asset_blobs', function (Blueprint $table): void {
            $table->id();
            $table->string('version_key', 191);
            $table->string('symbol_name', 191)->unique();
            $table->binary('image_data');
            $table->unsignedSmallInteger('width')->default(0);
            $table->unsignedSmallInteger('height')->default(0);
            $table->smallInteger('offset_x')->default(0);
            $table->smallInteger('offset_y')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['version_key']);
        });

        Schema::create('habbo_imaging_render_blobs', function (Blueprint $table): void {
            $table->id();
            $table->string('render_hash', 64)->unique();
            $table->binary('image_data');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('habbo_imaging_xml_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('version_key', 191);
            $table->string('name', 191);
            $table->string('kind', 64)->nullable();
            $table->mediumText('xml_content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['version_key', 'name']);
            $table->index(['version_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habbo_imaging_xml_documents');
        Schema::dropIfExists('habbo_imaging_render_blobs');
        Schema::dropIfExists('habbo_imaging_asset_blobs');
    }
};
