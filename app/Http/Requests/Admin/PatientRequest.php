<?php

namespace App\Http\Requests\Admin;

use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to manage patient records.
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
        $routePatient = $this->route('patient');
        $patientId = $routePatient instanceof Patient ? $routePatient->getKey() : $routePatient;
        $passwordRules = $this->isMethod('post')
            ? ['required', 'string', 'min:6', 'max:60', 'confirmed']
            : ['nullable', 'string', 'min:6', 'max:60', 'confirmed'];

        return [
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('patients', 'username')->ignore($patientId),
            ],
            'password' => $passwordRules,
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:20'],
            'contactno' => ['nullable', 'string', 'max:11', 'regex:/^[0-9]{11}$/'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'hospital_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]+$/',
                Rule::unique('patients', 'hospital_number')->ignore($patientId),
            ],
            'status' => ['sometimes', Rule::in(['Active', 'Pending'])],
        ];
    }
}
