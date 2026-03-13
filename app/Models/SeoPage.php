<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    protected $fillable = [
        'key',
        'path',
        'title',
        'meta_description',
        'keywords_target',
        'og_image',
        'robots',
        'canonical_override',
        'jsonld',
    ];
}
