<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByDueDate
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('due_date')) {
            return $next($query);
        }

        $dates = explode(", ", str_replace(['[', ']'], '', request('due_date')));
        $startDate = $dates[0];
        $endDate = $dates[1];

        $query->whereBetween('due_date', [$startDate, $endDate]);

        return $next($query);
    }
}
