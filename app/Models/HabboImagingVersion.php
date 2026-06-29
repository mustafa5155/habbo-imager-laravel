<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HabboImagingVersion extends Model
{
    protected $table = 'habbo_imaging_versions';

    protected $fillable = [
        'hotel',
        'source_version',
        'status',
        'external_variables_url',
        'figuredata_url',
        'figuremap_url',
        'asset_base_url',
        'asset_name_template',
        'source_path',
        'parsed_path',
        'metadata',
        'last_error',
        'synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    public function assets()
    {
        return $this->hasMany(HabboImagingAsset::class, 'version_id');
    }
}
