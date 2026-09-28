<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Snapshot extends Model
{
    protected $fillable = ['name', 'slug', 'character_name', 'band', 'rarity', 'rarity_id', 'type', 'performance', 'technique', 'visual', 'level_growth', 'rank_growth', 'support_skill_1_id', 'support_skill_2_id', 'gekisou_support_1_id', 'gekisou_support_2_id', 'support_skill_levels', 'gekisou_support_levels', 'live_support', 'gekiso_support', 'diary', 'source_id', 'released_at', 'image_url', 'source_url'];

    protected function casts(): array
    {
        return ['performance' => 'float', 'technique' => 'float', 'visual' => 'float', 'released_at' => 'date', 'support_skill_levels' => 'array', 'gekisou_support_levels' => 'array'];
    }
}
