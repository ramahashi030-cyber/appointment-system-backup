<?php

namespace App\Support;

class Settings
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/settings.json');

        if (! file_exists($this->path)) {
            file_put_contents($this->path, json_encode($this->defaults(), JSON_PRETTY_PRINT));
        }
    }

    public function all(): array
    {
        $content = file_get_contents($this->path);
        $data = json_decode($content, true);

        return is_array($data) ? $data : $this->defaults();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->all();

        return $data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $data = $this->all();
        $data[$key] = $value;
        $this->save($data);
    }

    public function setMany(array $values): void
    {
        $data = $this->all();
        foreach ($values as $key => $value) {
            $data[$key] = $value;
        }
        $this->save($data);
    }

    private function save(array $data): void
    {
        file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function defaults(): array
    {
        return [
            'hospital_name' => 'QMM C',
            'hospital_address' => '',
            'hospital_phone' => '',
            'hospital_email' => '',
            'hospital_logo' => '',
            'system_name' => 'QMM C Telemedicine',
            'appointment_duration' => 30,
            'cancellation_window_hours' => 24,
            'max_advance_booking_days' => 30,
            'notification_email_enabled' => true,
            'notification_sms_enabled' => false,
            'notification_appointment_reminder' => true,
            'notification_reminder_hours' => 2,
            'consultation_requires_approval' => false,
            'consultation_auto_assign' => true,
            'maintenance_mode' => false,
            'maintenance_message' => 'System is under maintenance. Please try again later.',
        ];
    }
}
