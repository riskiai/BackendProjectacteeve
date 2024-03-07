<?php

namespace App\Http\Controllers;

use App\Facades\MessageActeeve;
use App\Models\PurchaseStatus;
use Illuminate\Http\Request;

class PurchaseStatusController extends Controller
{
    public function index()
    {
        $purchaseStatus = PurchaseStatus::whereNotIn('id', [PurchaseStatus::VERIFIED])->get();

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => $purchaseStatus
        ]);
    }

    public function show($id)
    {
        $purchaseStatus = PurchaseStatus::find($id);
        if (!$purchaseStatus) {
            return MessageActeeve::notFound('data not found!');
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => $purchaseStatus
        ]);
    }
}
