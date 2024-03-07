<?php

namespace App\Facades\Filters\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseStatus;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class ByStatus
{
    public function handle(Builder $query, Closure $next)
    {
        if (!request()->has('status')) {
            return $next($query);
        }

        if (in_array(request('status'), [PurchaseStatus::AWAITING, PurchaseStatus::REJECTED, PurchaseStatus::PAID])) {
            $query->where('purchase_status_id', request('status'));
        } else {
            $now = Carbon::now();
            $query->whereNotIn('purchase_status_id', [PurchaseStatus::AWAITING, PurchaseStatus::REJECTED, PurchaseStatus::PAID]);
            if (request('status') == PurchaseStatus::OPEN) {
                $query->where('due_date', '>', $now->toDateString());
            } elseif (request('status') == PurchaseStatus::OVERDUE) {
                $query->where('due_date', '<', $now->toDateString());
            } else {
                $query->whereDate('due_date', $now->toDateString());
            }
        }
        return $next($query);
    }
}
