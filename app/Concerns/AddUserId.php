<?php

namespace App\Concerns;

trait AddUserId
{
    protected static function bootAddUserId()
    {
        static::creating(function ($model) {
            $model->user_id = auth()->user()->id;
        });
    }
}
