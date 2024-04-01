<?php

namespace App\Http\Requests\Purchase;

use App\Facades\MessageActeeve;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UpdateRequest extends FormRequest
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
            'purchase_id' => 'nullable|in:1,2',
            'purchase_category_id' => 'nullable|exists:purchase_category,id',
            'client_id' => 'nullable|exists:companies,id',
            'tax_ppn' => 'nullable|string',
            'sub_total' => 'nullable|numeric',
            'attachment_file' => 'array',
            'attachment_file.*' => 'mimes:pdf,png,jpg,jpeg,xlsx,xls|max:3072',
            'date' => 'nullable|date',
            'due_date' => 'nullable|date',
            "created_at" => 'nullable|date',
            "updated_at" => 'nullable|date',
        ];

        if (request()->purchase_id == 1) {
            $request['project_id'] = 'nullable|exists:projects,id';
        }

        return $request;
    }

    public function attributes()
    {
        return [
            'client_id' => 'client',
            'project_id' => 'project',
            'tax_id' => 'tax',
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
