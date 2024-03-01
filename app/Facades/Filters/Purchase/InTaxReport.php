<?php

namespace App\Facades\Filters\Purchase;

use App\Models\Role;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class InTaxReport
{
    public function handle(Builder $query, Closure $next)
    {
        $query->where('ppn', '!=', null);

        return $next($query);
    }
}
