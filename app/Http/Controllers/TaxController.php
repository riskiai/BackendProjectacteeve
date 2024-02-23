<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;
use App\Facades\MessageActeeve;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tax\CreateRequest;
use App\Http\Requests\Tax\UpdateRequest;
use App\Http\Resources\Tax\TaxCollection;
use App\Models\Purchase;

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
    public function store(CreateRequest $request)
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
                'type' => $tax->type,
                'created_at' => $tax->created_at,
                'updated_at' => $tax->updated_at,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, $id)
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

    public function reportPpn()
    {
        $purchases = Purchase::whereExists(function ($query) {
            $query->where('ppn', '!=', null);
        })->get();

        $data = [];
        foreach ($purchases as $key => $purchase) {
            $data[$key] = [
                "doc_no" => $purchase->doc_no,
                "doc_type" => $purchase->doc_type,
                "purchase_type" => $purchase->purchase_id == Purchase::TYPE_EVENT ? Purchase::TEXT_EVENT : Purchase::TEXT_OPERATIONAL,
                "vendor_name" => $purchase->company->name,
                "status" => $this->getStatus($purchase),
                "description" => $purchase->description,
                "remarks" => $purchase->remarks,
                "sub_total" => $purchase->sub_total,
                "total" => $purchase->total,
                "file_attachment" => $this->getDocument($purchase),
                "date" => $purchase->date,
                "due_date" => $purchase->due_date,
                "ppn" => $this->getPpn($purchase),
                "created_at" => $purchase->created_at,
                "updated_at" => $purchase->updated_at,
            ];

            if ($purchase->purchase_id == Purchase::TYPE_EVENT) {
                $data[$key]['project'] = [
                    "id" => $purchase->project->id,
                    "name" => $purchase->project->name,
                ];
            }
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => $data
        ]);
    }

    protected function getPpn($purchase)
    {
        return ($purchase->sub_total * $purchase->ppn) / 100;
    }
}
