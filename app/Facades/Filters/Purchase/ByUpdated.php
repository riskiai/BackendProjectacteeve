<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByUpdated
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('updated_at')) {
            return $next($query);
        }

        $dates = explode(", ", str_replace(['[', ']'], '', request('updated_at')));
        $startDate = new \DateTime($dates[0]);
        $endDate = new \DateTime($dates[1]);

        $query->whereBetween('updated_at', [$startDate, $endDate]);

        return $next($query);
    }
}
