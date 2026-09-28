<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = ['name', 'slug', 'band', 'part', 'color'];

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
