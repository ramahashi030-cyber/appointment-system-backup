<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;

/**
 * Shared shape for every real-time appointment notification.
 *
 * A notification is stored through Laravel's `database` channel and pushed
 * through Laravel's `broadcast` channel on the user's private
 * `user.{role}.{id}` channel. The payload is deliberately plain scalars so the
 * Blade/JavaScript notification modal can render it without knowing anything
 * about Eloquent.
 *
 * Nothing in here hardcodes a service type: the service name is always read
 * from the appointment's own `service_id` / `mode` pair, which is what the
 * existing triage workflow writes.
 */
abstract class AppointmentNotification extends Notification
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * @param  array<string, mixed>  $context  values that differ per recipient
     */
    public function __construct(Appointment $appointment, array $context = [])
    {
        $this->data = array_merge([
            'appointment_id' => (int) $appointment->id,
            'service' => static::serviceFor($appointment),
            'date' => $appointment->date?->format('F j, Y'),
            'time' => $appointment->time_slot ?: null,
            'reason' => null,
            'title' => '',
            'message' => '',
            'icon' => 'bi-bell-fill',
            'action_label' => null,
            'action_url' => null,
        ], $this->build($appointment, $context));
    }

    /**
     * Short machine-readable type stored in the notification payload.
     */
    abstract public function kind(): string;

    /**
     * Recipient-aware title / message / action for this notification.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    abstract protected function build(Appointment $appointment, array $context): array;

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->payload();
    }

    public function broadcastWith(): array
    {
        return $this->payload();
    }

    public function broadcastType(): string
    {
        return $this->kind();
    }

    /**
     * The approved / scheduled service, resolved from the existing appointment
     * data. Returns null while the triager has not picked a service yet — it is
     * never guessed from the request mode.
     */
    public static function serviceFor(Appointment $appointment): ?string
    {
        if ($appointment->service_id === null) {
            return null;
        }

        $mode = $appointment->mode ?: $appointment->request_mode;

        $name = $mode === 'TELE'
            ? $appointment->serviceTele?->service_name
            : $appointment->service?->service_name;

        return filled($name) ? (string) $name : null;
    }

    /** @return array<string, mixed> */
    protected function payload(): array
    {
        return $this->data + [
            'id' => $this->id,
            'type' => $this->kind(),
        ];
    }
}
