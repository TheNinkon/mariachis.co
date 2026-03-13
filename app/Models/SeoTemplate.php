<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTemplate extends Model
{
    protected $fillable = [
        'template_key',
        'title_template',
        'description_template',
        'robots',
        'og_image_path',
        'keywords_target',
        'jsonld_template',
    ];
}
