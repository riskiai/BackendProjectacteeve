<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByPurchaseID
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('purchase_id')) {
            return $next($query);
        }

        $query->where('purchase_id', request('purchase_id'));

        return $next($query);
    }
}
