<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByDocType
{
    public function handle(Builder $query, Closure $next)
    {
        if (request()->has('doc_type')) {
            $query->where('doc_type', request('doc_type'));
        }

        return $next($query);
    }
}
