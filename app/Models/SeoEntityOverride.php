<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEntityOverride extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'meta_title',
        'meta_description',
        'robots',
        'canonical_override',
        'og_image_path',
        'keywords_target',
        'jsonld_override',
    ];
}
