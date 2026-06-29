<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HabboImagingAsset extends Model
{
    protected $table = 'habbo_imaging_assets';

    protected $fillable = [
        'version_id',
        'library_name',
        'source_url',
        'source_path',
        'extracted_path',
        'extension',
        'status',
        'checksum',
        'extracted_file_count',
        'metadata',
        'synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    public function version()
    {
        return $this->belongsTo(HabboImagingVersion::class, 'version_id');
    }
}
