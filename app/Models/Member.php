<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    protected $fillable = ['character_id', 'name', 'slug', 'rarity', 'rarity_id', 'type', 'performance', 'technique', 'visual', 'max_performance', 'max_technique', 'max_visual', 'skill_type', 'score_up', 'leader_skill', 'live_skill', 'gekisou_skill', 'leader_skill_levels', 'live_skill_levels', 'gekisou_skill_levels', 'leader_skill_id', 'live_skill_id', 'gekisou_skill_id', 'source_id', 'image_url', 'source_url', 'released_at'];

    protected function casts(): array
    {
        return ['released_at' => 'date', 'leader_skill_levels' => 'array', 'live_skill_levels' => 'array', 'gekisou_skill_levels' => 'array'];
    }

    protected $appends = ['display_performance', 'display_technique', 'display_visual', 'display_power'];

    /** @return BelongsTo<Character, $this> */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function getPowerAttribute(): int
    {
        return $this->display_power;
    }

    public function getDisplayPerformanceAttribute(): int
    {
        return $this->displayStat('max_performance', 'performance');
    }

    public function getDisplayTechniqueAttribute(): int
    {
        return $this->displayStat('max_technique', 'technique');
    }

    public function getDisplayVisualAttribute(): int
    {
        return $this->displayStat('max_visual', 'visual');
    }

    public function getDisplayPowerAttribute(): int
    {
        return $this->display_performance + $this->display_technique + $this->display_visual;
    }

    private function displayStat(string $maxColumn, string $fallbackColumn): int
    {
        $rate = match ((int) $this->rarity_id) {
            2 => 0.6571, // R: Lv.30 / 각성 0
            3 => 0.7000, // SR: Lv.40 / 각성 0
            default => 0.7333, // SSR: Lv.50 / 각성 0
        };

        $maximum = (int) $this->{$maxColumn};

        return $maximum > 0 ? (int) round($maximum * $rate) : (int) $this->{$fallbackColumn};
    }
}
