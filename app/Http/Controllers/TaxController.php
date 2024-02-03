<?php

namespace App\Http\Controllers;

use App\Http\Resources\Tax\TaxCollection;
use App\Facades\MessageActeeve;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tax::query();

        $tax = $query->paginate($request->per_page);

        return new TaxCollection($tax);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $tax = Tax::create($request->all());

            DB::commit();
            return MessageActeeve::success("tax $tax->name has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tax = Tax::find($id);
        if (!$tax) {
            return MessageActeeve::notFound('data not found!');
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => [
                'id' => $tax->id,
                'name' => $tax->name,
                'description' => $tax->description,
                'percent' => $tax->percent,
                'type'=> $tax->type,
                'created_at' => $tax->created_at,
                'updated_at' => $tax->updated_at,
                ]
            ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        $tax = Tax::find($id);
        if (!$tax) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $tax->update($request->all());

            DB::commit();
            return MessageActeeve::success("tax $tax->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        $tax = Tax::find($id);
        if (!$tax) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $tax->delete();

            DB::commit();
            return MessageActeeve::success("tax $tax->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }
}
