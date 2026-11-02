<?php

namespace App\Services;

use App\Models\AdminReminder;
use App\Models\QaSchedule;
use App\Models\User;
use App\Notifications\QaReminderNotification;
use Illuminate\Support\Facades\DB;

class ReminderDeliveryService
{
    public function dispatchDue(): int
    {
        $deliveredCount = 0;

        AdminReminder::query()
            ->whereNull('delivered_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($reminders) use (&$deliveredCount): void {
                foreach ($reminders as $reminder) {
                    if ($this->dispatchAdminReminder($reminder->id)) {
                        $deliveredCount++;
                    }
                }
            });

        $this->dispatchDueScheduleNotifications();

        return $deliveredCount;
    }

    public function dispatchAdminReminder(int $reminderId): bool
    {
        return DB::transaction(function () use ($reminderId): bool {
            $reminder = AdminReminder::query()->lockForUpdate()->find($reminderId);

            if ($reminder === null || $reminder->delivered_at !== null || $reminder->scheduled_at->isFuture()) {
                return false;
            }

            $recipients = User::query()->where('is_active', true);

            if ($reminder->recipient_type === 'role') {
                $recipients->where('role', $reminder->recipient_role);
            } elseif ($reminder->recipient_type === 'user') {
                $recipients->whereKey($reminder->recipient_user_id);
            }

            $recipientUsers = $recipients->get();

            if ($recipientUsers->isEmpty()) {
                return false;
            }

            $recipientUsers->each(function (User $user) use ($reminder): void {
                $user->notify(new QaReminderNotification(
                    $reminder->title,
                    $reminder->message,
                ));
            });

            $reminder->update(['delivered_at' => now()]);

            return true;
        });
    }

    private function dispatchDueScheduleNotifications(): void
    {
        QaSchedule::query()
            ->whereNotNull('tester_id')
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->where(function ($query): void {
                $query->where(function ($startQuery): void {
                    $startQuery->whereNull('start_notification_sent_at')
                        ->where('start_time', '<=', now());
                })->orWhere(function ($endQuery): void {
                    $endQuery->whereNull('end_notification_sent_at')
                        ->where('end_time', '<', now());
                });
            })
            ->with('tester')
            ->orderBy('id')
            ->chunkById(100, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    DB::transaction(function () use ($schedule): void {
                        $lockedSchedule = QaSchedule::query()->lockForUpdate()->find($schedule->id);
                        $tester = $lockedSchedule?->tester;

                        if ($lockedSchedule === null || $tester === null || ! $tester->is_active) {
                            return;
                        }

                        if (
                            $lockedSchedule->start_notification_sent_at === null
                            && $lockedSchedule->start_time->lessThanOrEqualTo(now())
                            && ! in_array($lockedSchedule->status, ['Completed', 'Cancelled'], true)
                        ) {
                            $tester->notify(new QaReminderNotification(
                                'Waktunya memulai QA',
                                "Jadwal {$lockedSchedule->schedule_code} - {$lockedSchedule->title} sudah dimulai.",
                                '/schedules',
                            ));
                            $lockedSchedule->start_notification_sent_at = now();
                        }

                        if (
                            $lockedSchedule->end_notification_sent_at === null
                            && $lockedSchedule->end_time->lessThan(now())
                            && ! in_array($lockedSchedule->status, ['Completed', 'Cancelled'], true)
                        ) {
                            $tester->notify(new QaReminderNotification(
                                'Waktu QA telah berakhir',
                                "Jadwal {$lockedSchedule->schedule_code} - {$lockedSchedule->title} telah melewati waktu yang ditentukan. Aktivitas QA terkait terkunci untuk user.",
                                '/schedules',
                            ));
                            $lockedSchedule->end_notification_sent_at = now();
                            if (! in_array($lockedSchedule->status, ['Completed', 'Cancelled', 'Overdue'], true)) {
                                $lockedSchedule->status = 'Overdue';
                            }
                        }

                        $lockedSchedule->save();
                    });
                }
            });
    }
}
