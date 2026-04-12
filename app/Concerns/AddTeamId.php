<?php

namespace App\Concerns;

trait AddTeamId
{
    protected static function bootAddTeamId(): void
    {
        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->current_team_id) {
                $model->team_id = auth()->user()->current_team_id;
            }
        });
    }
}
