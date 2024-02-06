<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByStatus
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('status')) {
            return $next($query);
        }

        $query->where('purchase_status_id', request('status', 1));

        return $next($query);
    }
}
