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
        return [
            'client_id' => 'required|exists:companies,id',
            'project_id' => 'required|exists:projects,id',
            'tax_id' => 'required|exists:taxs,id',
            'description' => 'required',
            'remarks' => 'required',
            'sub_total' => 'required|numeric',
            'total' => 'required|numeric',
            'attachment_file' => 'file|mimes:pdf',
            'date' => 'required|date',
            'due_date' => 'required|date',
        ];
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
