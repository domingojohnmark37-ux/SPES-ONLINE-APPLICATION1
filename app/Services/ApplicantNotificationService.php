<?php

namespace App\Services;

use App\Models\ApplicantNotificationDelivery;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ApplicantNotificationService
{
    public function notifyApplicants(ApplicantPortalUpdate $notification, string $eventKey): void
    {
        $this->notifyUsers(User::query(), $notification, $eventKey);
    }

    public function notifyUsers(Builder $applicants, ApplicantPortalUpdate $notification, string $eventKey): void
    {
        $applicants
            ->where('role', 'user')
            ->orderBy('id')
            ->chunkById(100, function ($applicants) use ($notification, $eventKey): void {
                foreach ($applicants as $applicant) {
                    $this->notifyApplicant($applicant, $notification, $eventKey);
                }
            });
    }

    public function notifyApplicant(User $applicant, ApplicantPortalUpdate $notification, string $eventKey): void
    {
        if ($applicant->role !== 'user') {
            return;
        }

        $eventHash = hash('sha256', $eventKey);
        $channels = $notification->via($applicant);

        if (! filter_var($applicant->email, FILTER_VALIDATE_EMAIL)) {
            $now = now();
            $created = DB::table('applicant_notification_deliveries')->insertOrIgnore([
                'user_id' => $applicant->id,
                'event_hash' => $eventHash,
                'channel' => 'mail',
                'status' => 'failed',
                'attempts' => 0,
                'last_error' => 'InvalidRecipient',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($created === 1) {
                Log::warning('SPES applicant notification has an invalid registered email address.', [
                    'applicant_id' => $applicant->id,
                    'event_hash' => $eventHash,
                ]);
            }

            $channels = array_values(array_filter($channels, fn (string $channel): bool => $channel !== 'mail'));
        }

        foreach ($channels as $channel) {
            if (! in_array($channel, ['database', 'mail'], true)) {
                continue;
            }

            $this->deliverChannel($applicant, $notification, $eventHash, $channel);
        }
    }

    private function deliverChannel(
        User $applicant,
        ApplicantPortalUpdate $notification,
        string $eventHash,
        string $channel,
    ): void {
        $now = now();

        DB::table('applicant_notification_deliveries')->insertOrIgnore([
            'user_id' => $applicant->id,
            'event_hash' => $eventHash,
            'channel' => $channel,
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $claimed = ApplicantNotificationDelivery::query()
            ->where('user_id', $applicant->id)
            ->where('event_hash', $eventHash)
            ->where('channel', $channel)
            ->where(function ($query) use ($now): void {
                $query->whereIn('status', ['pending', 'failed'])
                    ->orWhere(function ($query) use ($now): void {
                        $query->where('status', 'sending')
                            ->where('claimed_at', '<=', $now->copy()->subMinutes(15));
                    });
            })
            ->update([
                'status' => 'sending',
                'attempts' => DB::raw('attempts + 1'),
                'last_error' => null,
                'claimed_at' => $now,
                'updated_at' => $now,
            ]);

        if ($claimed !== 1) {
            return;
        }

        try {
            app(ChannelManager::class)->sendNow($applicant, $notification, [$channel]);

            ApplicantNotificationDelivery::query()
                ->where('user_id', $applicant->id)
                ->where('event_hash', $eventHash)
                ->where('channel', $channel)
                ->update([
                    'status' => 'sent',
                    'last_error' => null,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (Throwable $exception) {
            ApplicantNotificationDelivery::query()
                ->where('user_id', $applicant->id)
                ->where('event_hash', $eventHash)
                ->where('channel', $channel)
                ->update([
                    'status' => 'failed',
                    'last_error' => Str::limit(class_basename($exception), 255, ''),
                    'updated_at' => now(),
                ]);

            Log::error('SPES applicant notification delivery failed.', [
                'applicant_id' => $applicant->id,
                'event_hash' => $eventHash,
                'channel' => $channel,
                'exception' => class_basename($exception),
            ]);
        }
    }
}
