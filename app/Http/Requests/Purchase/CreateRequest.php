<?php

namespace App\Http\Requests\Purchase;

use App\Facades\MessageActeeve;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $request = [
            'purchase_id' => 'required|in:1,2',
            'purchase_category_id' => 'required|exists:purchase_category,id',
            'client_id' => 'required|exists:companies,id',
            'tax_ppn' => 'nullable|string',
            'sub_total' => 'required|numeric',
            // 'attachment_file' => 'nullable|array', 
            'date' => 'required|date',
            'due_date' => 'required|date',
        ];

        if ($this->hasFile('attachment_file')) {
            $request['attachment_file'] = 'array';
            $request['attachment_file.*'] = 'nullable|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072';
        }

        if (request()->purchase_id == 1) {
            $request['project_id'] = 'nullable|exists:projects,id';
        }

        return $request;
    }

    public function attributes()
    {
        return [
            'purchase_id' => 'purchase type',
            'purchase_category_id' => 'category purchase',
            'client_id' => 'client',
            'project_id' => 'project',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse([
            'status' => MessageActeeve::WARNING,
            'status_code' => MessageActeeve::HTTP_UNPROCESSABLE_ENTITY,
            'message' => $validator->errors()
        ], MessageActeeve::HTTP_UNPROCESSABLE_ENTITY);

        throw new ValidationException($validator, $response);
    }
}
