<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByTab
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('tab')) {
            return $next($query);
        }

        $query->where('tab', request('tab', 1));

        return $next($query);
    }
}
