<?php

namespace App\Support;

use App\Models\Appointment;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

class AppointmentQrCode
{
    public function ensureToken(Appointment $appointment): Appointment
    {
        if (blank($appointment->qr_code_token)) {
            $appointment->forceFill([
                'qr_code_token' => Str::random(64),
            ])->save();
        }

        return $appointment;
    }

    public function payload(Appointment $appointment): string
    {
        $this->ensureToken($appointment);

        return "Appointment ID: {$appointment->id}\nVerification: {$appointment->qr_code_token}";
    }

    public function svg(Appointment $appointment): string
    {
        $qrCode = new QrCode(
            data: $this->payload($appointment),
            encoding: new Encoding('ISO-8859-1'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 512,
            margin: 4,
            roundBlockSizeMode: RoundBlockSizeMode::None,
        );

        return (new SvgWriter)->write(
            qrCode: $qrCode,
            options: [
                SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
            ],
        )->getString();
    }
}
