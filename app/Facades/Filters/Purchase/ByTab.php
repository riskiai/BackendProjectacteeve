<?php

namespace App\Facades\Filters\Purchase;

use Closure;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;

class ByTab
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('tab')) {
            return $next($query);
        }

        // Filter records based on the selected tab
        switch (request('tab')) {
            case Purchase::TAB_SUBMIT:
                $query->where('status', 'submit');
                break;
            case Purchase::TAB_VERIFIED:
                $query->where('status', 'verified');
                break;
            case Purchase::TAB_PAYMENT_REQUEST:
                $query->where('status', 'payment_request');
                break;
            case Purchase::TAB_PAID:
                $query->where('status', 'paid');
                break;
        }

        return $next($query);
    }
}
