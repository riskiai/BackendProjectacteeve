<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByProject
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('project')) {
            return $next($query);
        }

        $query->where('project_id', request('project'));

        return $next($query);
    }
}
