<?php

namespace App\Http\Controllers;

use App\Facades\MessageActeeve;
use App\Models\PurchaseCategory;
use Illuminate\Http\Request;

class PurchaseCategoryController extends Controller
{
    public function index()
    {
        $purchaseCategories = PurchaseCategory::all();

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => $purchaseCategories
        ]);
    }

    public function show($id)
    {
        $purchaseCategory = PurchaseCategory::find($id);
        if (!$purchaseCategory) {
            return MessageActeeve::notFound('data not found!');
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => $purchaseCategory
        ]);
    }
}
