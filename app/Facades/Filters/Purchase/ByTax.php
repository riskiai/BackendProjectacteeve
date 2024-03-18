<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByTax
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('tax') && !request()->has('pph_name')) {
            return $next($query);
        }
        
        $query->where(function ($query) {
            if (request()->has('tax')) {
                $query->whereHas('taxPpn', function ($query) {
                    $query->where('type', request('tax'));
                });
                $query->orWhereHas('taxPph', function ($query) {
                    $query->where('type', request('tax'));
                });
            }

            if (request()->has('pph_name')) {
                $query->whereHas('taxPph', function ($query) {
                    $query->where('name', request('pph_name'));
                });
            }
        });

        return $next($query);
    }
}
