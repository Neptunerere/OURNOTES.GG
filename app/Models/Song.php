<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $fillable = ['title', 'slug', 'band', 'type', 'easy', 'normal', 'hard', 'expert', 'gekiso', 'source_id', 'attribute', 'image_url', 'composer', 'lyricist', 'arranger', 'bpm', 'note_count', 'chart_url', 'source_url'];
}
