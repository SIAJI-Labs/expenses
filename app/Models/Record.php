<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Record extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_id',
        'user_id',
        'category_id',
        'type',
        'from_wallet_id',
        'to_wallet_id',
        'timestamp',
        'amount',
        'extra_amount',
        'extra_percentage',
        'notes',
        'is_hidden',
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
        return [
            'is_hidden' => 'boolean'
        ];
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
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id')->withTrashed();
    }
    public function fromWallet()
    {
        return $this->belongsTo(\App\Models\Wallet::class, 'from_wallet_id')->withTrashed();
    }
    public function toWallet()
    {
        return $this->belongsTo(\App\Models\Wallet::class, 'to_wallet_id')->withTrashed();
    }
    public function tags()
    {
        return $this->belongsToMany(\App\Models\Tag::class, (new \App\Models\RecordTag())->getTable(), 'record_id', 'tag_id');
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

        // Listen to Created Event
        static::created(function ($model) {
            // Store to Model Changelog
            $model->generateChangelog($model);
        });

        // Listen to Updated Event
        static::updated(function ($model) {
            // Generate Changelog
            $model->generateChangelogItem($model);
        });
    }

    /**
     *
     *
     * Generate Changelog
     */
    protected function scopeGenerateChangelog($query, $model, $message = null)
    {
        // Store to Model Changelog
        $cacheKey = "model_changelog-".get_class($model)."-".$model->id;
        $logs = \Illuminate\Support\Facades\Cache::remember($cacheKey, (15 * 60), function() use ($model){
            // Define changelog actor
            $actor = match(true){
                \Illuminate\Support\Facades\Auth::check() => [
                    'model' => get_class(\Illuminate\Support\Facades\Auth::user()),
                    'id' => \Illuminate\Support\Facades\Auth::user()->id
                ],
                default => [
                    'model' => null,
                    'id' => null
                ]
            };

            // Initialize model changelog
            $result = new \App\Models\ModelChangelog([
                'model_class' => get_class($model),
                'model_id' => $model->id,
                'message' => $message ?? 'Data successfully added!',
                'actor_id' => $actor['id'],
                'actor_model' => $actor['model'],
            ]);
            $result->save();

            return $result;
        });

        return $logs;
    }

    /**
     * Scope
     * 
     * Generate Changelog Item
     */
    protected function scopeGenerateChangelogItem($query, $model): void
    {
        // Validate if model are dirty (there's column change)
        if($model->isDirty()){
            // Initial variable state
            $items = [];

            // Skip this attribute / column
            $skipColumn = [
                'updated_at'
            ];

            // Get all casts
            $casts = $model->getCasts();
            // Filter to get only the keys where the cast is 'boolean'
            $booleanCastsKeys = array_keys(array_filter($casts, function ($cast) {
                return $cast === 'boolean';
            }));
            // Handle boolean key
            $booleanColumn = [
                ...$booleanCastsKeys,
            ];

            // Protect value from hidden column
            $hiddenColumn = [
                ...$model->getHidden(),
            ];

            foreach($model->getDirty() as $key => $value){
                $skip = false;
                $originalValue = $model->getOriginal($key) ?? null;
                if(in_array($key, $skipColumn)){
                    $skip = true;
                }

                // Override value if it's included in booleanColumn
                if(in_array($key, $booleanColumn)){
                    $value = $value ? 1 : 0;
                }

                // Validate if changes actually changed
                if($originalValue === $value){
                    // Skip due to change is irrelevant (sometime boolean or timestamp is record as dirty by laravel even when there's no change)
                    $skip = true;
                }

                if(!$skip){
                    // Make sure keep hidden column
                    if(in_array($key, $hiddenColumn)){
                        $originalValue = '[hidden]';
                        $value = '[hidden]';
                    }

                    $items[] = new \App\Models\ModelChangelogItem([
                        'column' => $key,
                        'original' => $originalValue,
                        'changed' => $value,
                    ]);
                }
            }

            // Store into model changelog record
            if(count($items) > 0){
                // Store to Model Changelog
                $logs = $model->generateChangelog($model, 'Updated fields: {counts}');

                if(!empty($logs) && $logs instanceof (new \App\Models\ModelChangelog()) && $logs->model_class === get_class($model)){
                    $logs->changelogItem()->saveMany($items);
                }
            }
        }
    }
}
