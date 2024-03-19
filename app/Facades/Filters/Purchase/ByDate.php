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

        $dates = explode(", ", str_replace(['[', ']'], '', request('date')));
        $startDate = $dates[0];
        $endDate = $dates[1];

        $query->whereBetween('date', [$startDate, $endDate]);

        return $next($query);
    }
}
