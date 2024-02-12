<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByTax
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('tax')) {
            return $next($query);
        }

        $query->where(function ($query) {
            $query->whereHas('taxPpn', function ($query) {
                $query->where('type', request('tax'));
            });
            $query->orWhereHas('taxPph', function ($query) {
                $query->where('type', request('tax'));
            });
        });

        return $next($query);
    }
}
