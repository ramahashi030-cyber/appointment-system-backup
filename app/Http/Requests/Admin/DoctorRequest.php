<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to manage staff records.
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
        $routeStaff = $this->route('staff');
        $staffId = $routeStaff instanceof Staff ? $routeStaff->getKey() : $routeStaff;
        $passwordRules = $this->isMethod('post')
            ? ['required', 'string', 'min:8', 'confirmed']
            : ['nullable', 'string', 'min:8', 'confirmed'];
        $consultationTypeRules = $this->isMethod('put')
            ? ['required', 'string', 'max:100']
            : ['prohibited'];

        return [
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('staff', 'username')->ignore($staffId),
            ],
            'password' => $passwordRules,
            'employee_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('staff', 'employee_id')->ignore($staffId),
            ],
            'legacy_doctor_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('staff', 'legacy_doctor_id')->ignore($staffId),
            ],
            'email' => $this->isMethod('post')
                ? ['required', 'email', 'max:191']
                : ['nullable', 'email', 'max:191'],
            'contactno' => ['required', 'string', 'max:11'],
            'consultation_type' => $consultationTypeRules,
            'site' => ['nullable', Rule::in(['TELE', 'FACE', 'BOTH'])],
            'is_active' => ['sometimes', 'boolean'],
            'availability_days' => ['sometimes', 'array'],
            'availability_days.*' => ['string', Rule::in([
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ])],
            'shift_start' => ['nullable', 'date_format:H:i'],
            'shift_end' => ['nullable', 'date_format:H:i', 'after:shift_start'],
        ];
    }

    /**
     * Normalize the checkbox collection before validation.
     */
    public function prepareForValidation(): void
    {
        $days = $this->input('availability_days', []);

        $this->merge([
            'availability_days' => is_array($days) ? array_values(array_filter($days)) : [],
        ]);
    }
}
