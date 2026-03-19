<?php

namespace App\Http\Requests\Tenant;

use App\Support\CentralDatabase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectPlanRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', Rule::exists(CentralDatabase::table('plans'), 'id')],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ];
    }
}
