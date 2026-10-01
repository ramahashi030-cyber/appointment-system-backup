<?php

namespace App\Http\Requests\Patient;

use App\ConsultationReason;
use App\Symptom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTelemedAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services_tele,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_slot' => ['required', 'string', 'max:50'],
            'consultation_reason' => ['required', Rule::enum(ConsultationReason::class)],
            'symptoms' => ['required', 'array', 'list', 'min:1', 'max:3'],
            'symptoms.*' => ['required', 'string', 'distinct', Rule::enum(Symptom::class)],
            'complaint_details' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consultation_reason.required' => 'Please choose what you need to consult for.',
            'symptoms.required' => 'Please select at least 1 and maximum of 3 symptoms.',
            'symptoms.min' => 'Please select at least 1 and maximum of 3 symptoms.',
            'symptoms.max' => 'Please select no more than 3 symptoms.',
            'symptoms.*.distinct' => 'Please do not select the same symptom more than once.',
            'complaint_details.required' => 'Please provide details about your complaint.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'consultation_reason' => 'consultation reason',
            'symptoms' => 'symptoms',
            'complaint_details' => 'complaint details',
        ];
    }
}
