<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'order', // order of the group (based on parent_id)
        'order_main' // order of all data
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
    public function child()
    {
        return $this->hasMany(\App\Models\Category::class, 'parent_id');
    }

    /**
     * Foreign Key Relation
     * 
     * @return model
     */
    public function parent()
    {
        return $this->belongsTo(\App\Models\Category::class, 'parent_id');
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
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

            // Adjust Order
            $order = 0;
            $order_main = 0;
            // Check for order group
            if(!empty($model->parent_id)){
                $parent = \App\Models\Category::find($model->parent_id);
                $exists = \App\Models\Category::where('parent_id', $model->parent_id)
                    ->orderBy('order_main', 'desc')
                    ->first();
                if(!empty($exists)){
                    $order = $exists->order + 1;
                    $order_main = $exists->order_main + 1;
                } else {
                    // First child of the group
                    $order = $parent->order + 1;
                    $order_main = $parent->order_main + 1;
                }
            } else {
                $exists = \App\Models\Category::whereNull('parent_id')
                    ->orderBy('order_main', 'desc')
                    ->first();
                if(!empty($exists)){
                    $order_main = $exists->order_main + 1;
                }
            }

            // Adjust order & order_main accordingly
            $model->{'order'} = $order;
            $model->{'order_main'} = $order_main;
        });

        // Listen to Created Event
        static::created(function ($model) {
            // Store to Model Changelog
            $model->generateChangelog($model);

            // Adjust the order
            $model->adjustOrder();
        });

        // Listen to Updated Event
        static::updated(function ($model) {
            // Generate Changelog
            $model->generateChangelogItem($model);

            if($model->isDirty('parent_id')){
                $order = 0;
                $order_main = 0;
                if(empty($model->parent_id)){
                    $exists = \App\Models\Category::orderBy('order_main', 'desc')
                        ->first();
                    if(!empty($exists)){
                        $order_main = $exists->order_main + 1;
                    }
                } else {
                    $parent = \App\Models\Category::find($model->parent_id);
                    $exists = \App\Models\Category::where('parent_id', $model->parent_id)
                        ->orderBy('order_main', 'desc')
                        ->first();
                    if(!empty($exists)){
                        $order = $exists->order + 1;
                        $order_main = $exists->order_main + 1;
                    } else {
                        // First child of the group
                        $order = $parent->order + 1;
                        $order_main = $parent->order_main + 1;
                    }
                }
                $model->order = $order;
                $model->order_main = $order_main;
                $model->saveQuietly();

                // Adjust the order
                $model->adjustOrder();
            }
        });

        // Listen to Deleted Event
        static::deleted(function ($model) {
            $model->order = null;
            $model->order_main = null;
            $model->saveQuietly();

            // Adjust the order
            $model->adjustOrder();
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

    /**
     * Scope
     * 
     * Adjust wallet order
     */
    protected function scopeAdjustOrder($query): void
    {
        $data = \App\Models\Category::with([
                'child'
            ])
            ->whereNull('parent_id')
            ->orderBy('order_main')
            ->get();

        $order_main = 0;
        foreach($data as $item){
            $item->order_main = $order_main;
            $item->saveQuietly();

            // Loop for child
            if(count($item->child) > 0){
                $order = 0;
                foreach($item->child as $child){
                    $order += 1;
                    $order_main += 1;
                    
                    $child->order = $order;
                    $child->order_main = $order_main;
                    $child->saveQuietly();
                }
            }

            $order_main += 1;
        }
    }
}
