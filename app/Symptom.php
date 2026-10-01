<?php

namespace App;

enum Symptom: string
{
    case Headache = 'headache';
    case Dizziness = 'dizziness';
    case BlurringOfVision = 'blurring_of_vision';
    case Fainting = 'fainting';
    case WeightLossOrGain = 'weight_loss_or_gain';
    case AppetiteChanges = 'appetite_changes';
    case Weakness = 'weakness';
    case FeverOrChills = 'fever_or_chills';
    case Colds = 'colds';
    case Cough = 'cough';
    case EasilyFatigability = 'easily_fatigability';
    case Dyspnea = 'dyspnea';
    case ChestPain = 'chest_pain';
    case Palpitation = 'palpitation';
    case Edema = 'edema';
    case OrthopneaPnd = 'orthopnea_pnd';
    case Nausea = 'nausea';
    case AbdominalPain = 'abdominal_pain';
    case Vomiting = 'vomiting';
    case ConstipationOrLbm = 'constipation_or_lbm';
    case Dysuria = 'dysuria';
    case UrinaryFrequency = 'urinary_frequency';
    case Hematuria = 'hematuria';
    case AnyBleeding = 'any_bleeding';

    public function label(): string
    {
        return match ($this) {
            self::Headache => 'Headache / Sakit ng ulo',
            self::Dizziness => 'Dizziness / Pagkahilo',
            self::BlurringOfVision => 'Blurring of Vision / Malabong paningin',
            self::Fainting => 'Fainting / Nahimatay',
            self::WeightLossOrGain => 'Weight Loss/Gain / Pagbaba o pagtaas ng timbang',
            self::AppetiteChanges => 'Appetite changes / Pagbabago sa gana kumain',
            self::Weakness => 'Weakness / Panghihina',
            self::FeverOrChills => 'Fever/Chills / Lagnat o ginaw',
            self::Colds => 'Colds / Sipon',
            self::Cough => 'Cough / Ubo',
            self::EasilyFatigability => 'Easily Fatigability / Madaling mapagod',
            self::Dyspnea => 'Dyspnea / Hirap sa paghinga',
            self::ChestPain => 'Chest Pain / Pananakit ng dibdib',
            self::Palpitation => 'Palpitation / Mabilis na tibok ng puso',
            self::Edema => 'Edema / Pamamaga',
            self::OrthopneaPnd => 'Orthopnea/PND / Hirap huminga kapag nakahiga',
            self::Nausea => 'Nausea / Pagduduwal',
            self::AbdominalPain => 'Abdominal Pain / Sakit ng tiyan',
            self::Vomiting => 'Vomiting / Pagsusuka',
            self::ConstipationOrLbm => 'Constipation/LBM / Pagtatae o hirap dumumi',
            self::Dysuria => 'Dysuria / Mahapdi sa pag-ihi',
            self::UrinaryFrequency => 'Urinary Frequency / Madalas umihi',
            self::Hematuria => 'Hematuria / May dugo sa ihi',
            self::AnyBleeding => 'Any Bleeding / Anumang pagdurugo',
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
