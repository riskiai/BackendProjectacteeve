<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class BySearch
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('search')) {
            return $next($query);
        }

        $query->where(function (Builder $query) {
            $query->where('doc_no', 'like', '%' . request('search') . '%');
            $query->orWhere('doc_type', 'like', '%' . request('search') . '%');
            $query->orWhereHas('company', function (Builder $query) {
                $query->where('name', 'like', '%' . request('search') . '%');
            });
            $query->orWhereHas('project', function (Builder $query) {
                $query->where('name', 'like', '%' . request('search') . '%');
            });
        });

        return $next($query);
    }
}
