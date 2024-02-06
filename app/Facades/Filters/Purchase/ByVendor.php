<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByVendor
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('vendor')) {
            return $next($query);
        }

        $query->where('company_id', request('vendor'));

        return $next($query);
    }
}
