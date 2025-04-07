<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ModelChangelogItem extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_changelog_id',
        'column',
        'original',
        'changed'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        // 'id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Primary Key Relation
     * 
     * @return model
     */
    // 

    /**
     * Foreign Key Relation
     * 
     * @return model
     */
    public function changelog()
    {
        return $this->belongsTo(\App\Models\ModelChangelog::class, 'model_changelog_id');
    }

    /**
     * The "boot" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Listen to Create Event
        static::creating(function ($model) {
            // Always generate UUID on Data Create
            $model->{'uuid'} = Str::uuid()->toString();
        });
    }
}
