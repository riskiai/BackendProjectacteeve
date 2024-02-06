<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByDate
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('date')) {
            return $next($query);
        }

        $request = str_replace(['[', ']'], '', request('date'));
        $request = explode(", ", $request);

        $query->whereBetween('created_at', $request);

        return $next($query);
    }
}
