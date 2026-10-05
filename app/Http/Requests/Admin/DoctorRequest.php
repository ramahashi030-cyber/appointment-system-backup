<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DoctorRequest extends FormRequest
{
    /**
     * Provider names accept letters only. Spaces are kept so compound names
     * ("Mary Ann", "Dela Cruz") stay possible, while leading, trailing and
     * repeated spaces are rejected to keep stored values clean.
     */
    private const NAME_PATTERN = '/^[A-Za-z]+(?: +[A-Za-z]+)*$/';

    /**
     * Contact number takes digits only. Spaces and hyphens are rejected so the
     * stored value stays dialable as typed.
     */
    private const CONTACT_PATTERN = '/^[0-9]+$/';

    /**
     * Employee ID takes digits only, with spaces and hyphens allowed as
     * separators between the digits, nothing else.
     */
    private const EMPLOYEE_ID_PATTERN = '/^[0-9]+(?:[ -]?[0-9]+)*$/';

    /** Shortest password the add doctor modal accepts. */
    public const PASSWORD_MIN = 6;

    /** Longest password the add doctor modal accepts. */
    public const PASSWORD_MAX = 15;

    /**
     * A password needs a special character only once it is longer than this.
     * Up to eight characters an uppercase + lowercase + number combination is
     * enough, which keeps short but still mixed passwords usable.
     */
    public const PASSWORD_SYMBOL_THRESHOLD = 8;

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
        $isAddingDoctor = $this->isMethod('post');
        $passwordRules = $isAddingDoctor
            ? [
                'required',
                Password::min(self::PASSWORD_MIN)->max(self::PASSWORD_MAX)->mixedCase()->numbers(),
                'confirmed',
                $this->requireSymbolWhenLongerThan(self::PASSWORD_SYMBOL_THRESHOLD),
            ]
            : ['nullable', 'string', 'min:8', 'confirmed'];
        $consultationTypeRules = $this->isMethod('put')
            ? ['required', 'string', 'max:100']
            : ['prohibited'];

        // Only the add doctor modal restricts names, employee ID, contact
        // number and password strength; editing an existing provider leaves
        // the stored values untouched.
        $nameRules = $isAddingDoctor
            ? ['regex:'.self::NAME_PATTERN]
            : [];
        $contactRules = $isAddingDoctor
            ? ['regex:'.self::CONTACT_PATTERN]
            : [];
        $employeeIdRules = $isAddingDoctor
            ? ['regex:'.self::EMPLOYEE_ID_PATTERN]
            : [];

        return [
            'firstname' => array_merge(['required', 'string', 'max:100'], $nameRules),
            'middlename' => array_merge(['nullable', 'string', 'max:100'], $nameRules),
            'lastname' => array_merge(['required', 'string', 'max:100'], $nameRules),
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('staff', 'username')->ignore($staffId),
            ],
            'password' => $passwordRules,
            'employee_id' => array_merge([
                'nullable',
                'string',
                'max:10',
                Rule::unique('staff', 'employee_id')->ignore($staffId),
            ], $employeeIdRules),
            'legacy_doctor_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('staff', 'legacy_doctor_id')->ignore($staffId),
            ],
            'email' => $this->isMethod('post')
                ? ['required', 'email', 'max:191']
                : ['nullable', 'email', 'max:191'],
            'contactno' => array_merge(['required', 'string', 'max:11'], $contactRules),
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
     * Build the closure that requires a special character in passwords longer
     * than the given length. Characters are counted the same way the framework
     * counts them for the `Password` rule: spaces, symbols and punctuation.
     */
    private function requireSymbolWhenLongerThan(int $length): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($length): void {
            if (! is_string($value)) {
                return;
            }

            if (strlen($value) > $length && preg_match('/\p{Z}|\p{S}|\p{P}/u', $value) !== 1) {
                $fail('The password must contain at least one special character.');
            }
        };
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
