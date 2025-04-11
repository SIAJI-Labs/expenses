<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Internal
if(env('APP_ENV') === 'local'){
    Route::group([
        'prefix' => 'internal-Ns5W7OYK4q',
        'as' => 'internal.'
    ], function(){
        // Restore from xtrackr
        Route::group([
            'prefix' => 'xtrackr-restore',
            'as' => 'xtrackr-restore.'
        ], function(){
            $connectionName = 'mariadb_xtrackr';
            Route::get('/', function(){
                $route = [
                    'category' => route('internal.xtrackr-restore.category'),
                    'tags' => route('internal.xtrackr-restore.tags'),
                    'record' => route('internal.xtrackr-restore.record'),
                    'wallet' => route('internal.xtrackr-restore.wallet'),
                ];
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data fetched',
                    'result' => $route
                ]);
            });
    
            // Restore Category
            Route::get('category', function() use ($connectionName){
                $data = [];
                $user = [
                    'xtrackr' => (function() use ($connectionName){
                        return DB::connection($connectionName)
                            ->table('users')
                            ->where('id', 1)
                            ->first();
                    })(),
                    'expense' => \App\Models\User::first()
                ];
                
                // Override Data
                if(!empty($user['xtrackr'])){
                    $data = DB::connection($connectionName)
                        ->table('categories')
                        ->where('user_id', $user['xtrackr']->id)
                        ->get();
                }
    
                if(!empty($data)){
                    // Loop through the data
                    foreach($data as $item){
                        // Check if related data exists in database
                        $exists = \App\Models\Category::withTrashed()->find($item->id);
                        $restore = null;
                        if(empty($exists)){
                            // Create new
                            $restore = new \App\Models\Category();
                        } else {
                            // Update existing
                            $restore = $exists;
                        }
    
                        // Update the data
                        $columns = [
                            // index (xtrackr) => value (expense)
                            'id' => 'id',
                            'uuid' => 'uuid',
                            'parent_id' => 'parent_id',
                            'name' => 'name',
                            'order' => 'order',
                            'order_main' => 'order_main',
                            'created_at' => 'created_at',
                            'updated_at' => 'updated_at',
                            'deleted_at' => 'deleted_at'
                        ];
                        $restore->user_id = $user['expense']->id;
                        foreach($columns as $key => $column){
                            $restore->{$column} = $item->{$key};
                        }
                        $restore->saveQuietly();
                    }
                }
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data restored',
                    'result' => [
                        'user' => $user,
                        'data' => $data
                    ]
                ]);
            })->name('category');
    
            // Restore Tag
            Route::get('tags', function() use ($connectionName){
                $data = [];
                $user = [
                    'xtrackr' => (function() use ($connectionName){
                        return DB::connection($connectionName)
                            ->table('users')
                            ->where('id', 1)
                            ->first();
                    })(),
                    'expense' => \App\Models\User::first()
                ];
                
                // Override Data
                if(!empty($user['xtrackr'])){
                    $data = DB::connection($connectionName)
                        ->table('tags')
                        ->where('user_id', $user['xtrackr']->id)
                        ->get();
                }
    
                if(!empty($data)){
                    // Loop through the data
                    foreach($data as $item){
                        // Check if related data exists in database
                        $exists = \App\Models\Tag::withTrashed()->find($item->id);
                        $restore = null;
                        if(empty($exists)){
                            // Create new
                            $restore = new \App\Models\Tag();
                        } else {
                            // Update existing
                            $restore = $exists;
                        }
    
                        // Update the data
                        $columns = [
                            // index (xtrackr) => value (expense)
                            'id' => 'id',
                            'uuid' => 'uuid',
                            'name' => 'name',
                            'created_at' => 'created_at',
                            'updated_at' => 'updated_at',
                            'deleted_at' => 'deleted_at'
                        ];
                        $restore->user_id = $user['expense']->id;
                        foreach($columns as $key => $column){
                            $restore->{$column} = $item->{$key};
                        }
                        $restore->saveQuietly();
                    }
                }
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data restored',
                    'result' => [
                        'user' => $user,
                        'data' => $data
                    ]
                ]);
            })->name('tags');
    
            // Restore Records
            Route::get('record', function() use ($connectionName){
                $data = [];
                $user = [
                    'xtrackr' => (function() use ($connectionName){
                        return DB::connection($connectionName)
                            ->table('users')
                            ->where('id', 1)
                            ->first();
                    })(),
                    'expense' => \App\Models\User::first()
                ];
                
                // Override Data
                if(!empty($user['xtrackr'])){
                    $data = DB::connection($connectionName)
                        ->table('records')
                        ->where('user_id', $user['xtrackr']->id)
                        ->where('is_pending', 0)
                        ->get();
                }
    
                if(!empty($data)){
                    // Loop through the data
                    foreach($data as $item){
                        // Check if related data exists in database
                        $exists = \App\Models\Record::withTrashed()->find($item->id);
                        $restore = null;
                        if(empty($exists)){
                            // Create new
                            $restore = new \App\Models\Record();
                        } else {
                            // Update existing
                            $restore = $exists;
                        }
    
                        $proceed = true;
                        // Skip record data if it's transfer and has income type (only proceed transfer-expense type)
                        if(!empty($item->to_wallet_id)){
                            if($item->type === 'income'){
                                $proceed = false;
                            }
    
                            $item->type = 'transfer';
                        }
    
                        if($proceed){
                            // Update the data
                            $columns = [
                                // index (xtrackr) => value (expense)
                                'id' => 'id',
                                'uuid' => 'uuid',
                                'category_id' => 'category_id',
                                'type' => 'type',
                                'from_wallet_id' => 'from_wallet_id',
                                'to_wallet_id' => 'to_wallet_id',
                                'timestamp' => 'timestamp',
                                'amount' => 'amount',
                                'extra_amount' => 'extra_amount',
                                'extra_percentage' => 'extra_percentage',
                                'notes' => 'notes',
                                'is_hidden' => 'is_hidden',
                                'created_at' => 'created_at',
                                'updated_at' => 'updated_at',
                                'deleted_at' => 'deleted_at'
                            ];
                            $restore->user_id = $user['expense']->id;
                            foreach($columns as $key => $column){
                                $restore->{$column} = $item->{$key};
                            }
                            $restore->saveQuietly();
                        }
                    }
                }
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data restored',
                    'result' => [
                        'user' => $user,
                        'data' => $data
                    ]
                ]);
            })->name('record');
    
            // Restore Wallet
            Route::get('wallet', function() use ($connectionName){
                $data = [];
                $user = [
                    'xtrackr' => (function() use ($connectionName){
                        return DB::connection($connectionName)
                            ->table('users')
                            ->where('id', 1)
                            ->first();
                    })(),
                    'expense' => \App\Models\User::first()
                ];
                
                // Override Data
                if(!empty($user['xtrackr'])){
                    $data = DB::connection($connectionName)
                        ->table('wallets')
                        ->where('user_id', $user['xtrackr']->id)
                        ->get();
                }
    
                if(!empty($data)){
                    // Loop through the data
                    foreach($data as $item){
                        // Check if related data exists in database
                        $exists = \App\Models\Wallet::withTrashed()->find($item->id);
                        $restore = null;
                        if(empty($exists)){
                            // Create new
                            $restore = new \App\Models\Wallet();
                        } else {
                            // Update existing
                            $restore = $exists;
                        }
    
                        // Update the data
                        $columns = [
                            // index (xtrackr) => value (expense)
                            'id' => 'id',
                            'uuid' => 'uuid',
                            'parent_id' => 'parent_id',
                            'name' => 'name',
                            'starting_balance' => 'initial_balance',
                            'order' => 'order',
                            'order_main' => 'order_main',
                            'created_at' => 'created_at',
                            'updated_at' => 'updated_at',
                            'deleted_at' => 'deleted_at'
                        ];
                        $restore->user_id = $user['expense']->id;
                        foreach($columns as $key => $column){
                            $restore->{$column} = $item->{$key};
                        }
                        $restore->saveQuietly();
                    }
                }
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data restored',
                    'result' => [
                        'user' => $user,
                        'data' => $data
                    ]
                ]);
            })->name('wallet');
        });
    });
}