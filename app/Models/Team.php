<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// class Team extends \LaravelDaily\FilaTeams\Models\Team
class Team extends Model
{
    use SoftDeletes;

    // protected $fillable = [
    //     'name',
    //     'slug',
    //     'is_personal',
    // ];

    // protected $casts = [
    //     'is_personal' => 'boolean',
    // ];

    // public function members(): HasMany
    // {
    //     return $this->hasMany(TeamMember::class);
    // }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function googleDrives(): HasMany
    {
        return $this->hasMany(GoogleDrive::class);
    }
}
