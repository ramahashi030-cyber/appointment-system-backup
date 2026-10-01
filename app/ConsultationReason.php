<?php

namespace App;

enum ConsultationReason: string
{
    case DentalCheckUp = 'dental_check_up';
    case PrescriptionRefill = 'prescription_refill';
    case LaboratoryDiagnosticRequests = 'laboratory_diagnostic_requests';
    case GeneralCheckUp = 'general_check_up';
    case MedicalCertificateIssuance = 'medical_certificate_issuance';
    case StableChronicDiseaseFollowUp = 'stable_chronic_disease_follow_up';
    case ResultsInterpretation = 'results_interpretation';
    case NoneOfTheAbove = 'none_of_the_above';

    public function label(): string
    {
        return match ($this) {
            self::DentalCheckUp => 'Dental check-up',
            self::PrescriptionRefill => 'Prescription Refill / Pag-request ng panibagong reseta',
            self::LaboratoryDiagnosticRequests => 'Laboratory / diagnostic requests / Request para sa laboratoryo o iba pang test',
            self::GeneralCheckUp => 'General check-up (asymptomatic or low-risk) / Pangkalahatang check-up (walang ibang nararamdaman)',
            self::MedicalCertificateIssuance => 'Medical certificate issuance / Pagkuha ng medical certificate',
            self::StableChronicDiseaseFollowUp => 'Stable chronic disease follow up / Follow-up para sa kontroladong kondisyon tulad ng high blood o diabetes',
            self::ResultsInterpretation => 'Results interpretation / Pagbasa o pagpapaliwanag ng mga resulta',
            self::NoneOfTheAbove => 'None of the above / Wala sa mga nabanggit',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
