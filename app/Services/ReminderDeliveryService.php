<?php

namespace App\Services;

use App\Models\AdminReminder;
use App\Models\AutomatedReminderSetting;
use App\Models\QaSchedule;
use App\Models\User;
use App\Notifications\QaReminderNotification;
use Carbon\CarbonInterface;
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
        $this->dispatchDueAutomatedReminders();

        return $deliveredCount;
    }

    public function dispatchDueAutomatedReminders(): int
    {
        $localNow = now('Asia/Jakarta');
        $settings = AutomatedReminderSetting::current();
        $dispatchedCount = 0;
        $today = $localNow->toDateString();
        $firstSaturday = $localNow->isSaturday() && $localNow->day <= 7;

        $reminders = [
            [
                'enabled' => $settings->qa_enabled,
                'time' => $settings->qa_time,
                'event_key' => "qa:{$today}",
                'title' => 'Waktunya QA harian',
                'message' => 'Semangat! Saatnya memulai QA sesuai jadwal, menguji dengan teliti, dan mencatat hasilnya.',
                'url' => '/schedules',
            ],
            [
                'enabled' => $settings->daily_report_enabled,
                'time' => $settings->daily_report_time,
                'event_key' => "daily-report:{$today}",
                'title' => 'Waktunya membuat report QA',
                'message' => 'Jangan lupa merangkum hasil QA hari ini dan mengirim report harian.',
                'url' => '/reports/wa',
            ],
            [
                'enabled' => $settings->weekly_report_enabled && $localNow->isSaturday(),
                'time' => $settings->weekly_report_time,
                'event_key' => "weekly-report:{$today}",
                'title' => 'Waktunya report mingguan',
                'message' => 'Saatnya menyusun report pekerjaan QA mingguan. Terima kasih atas kerja keras minggu ini!',
                'url' => '/reports/weekly',
            ],
            [
                'enabled' => $settings->monthly_report_enabled && $firstSaturday,
                'time' => $settings->monthly_report_time,
                'event_key' => 'monthly-report:'.$localNow->format('Y-m'),
                'title' => 'Waktunya report bulanan',
                'message' => 'Sabtu pertama bulan ini adalah waktu yang tepat untuk menyusun report pekerjaan QA bulanan.',
                'url' => '/reports/weekly',
            ],
        ];

        foreach ($reminders as $reminder) {
            if (! $reminder['enabled'] || ! $this->isReminderDue($localNow, $reminder['time'])) {
                continue;
            }

            if ($this->dispatchAutomatedReminder($reminder, $settings->motivation_enabled, $settings->motivation_message)) {
                $dispatchedCount++;
            }
        }

        return $dispatchedCount;
    }

    /**
     * @param  array{enabled: bool, time: string, event_key: string, title: string, message: string, url: string}  $reminder
     */
    private function dispatchAutomatedReminder(array $reminder, bool $motivationEnabled, string $motivationMessage): bool
    {
        return DB::transaction(function () use ($reminder, $motivationEnabled, $motivationMessage): bool {
            $inserted = DB::table('automated_reminder_dispatches')->insertOrIgnore([
                'event_key' => $reminder['event_key'],
                'dispatched_at' => now(),
            ]);

            if ($inserted === 0) {
                return false;
            }

            $message = $reminder['message'];
            if ($motivationEnabled) {
                $message .= "\n\n".$motivationMessage;
            }

            User::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->chunkById(100, function ($users) use ($reminder, $message): void {
                    $users->each(fn (User $user) => $user->notify(new QaReminderNotification(
                        $reminder['title'],
                        $message,
                        $reminder['url'],
                    )));
                });

            return true;
        });
    }

    private function isReminderDue(CarbonInterface $localNow, string $time): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $scheduledMinutes = ($hour * 60) + $minute;
        $currentMinutes = ((int) $localNow->format('G') * 60) + (int) $localNow->format('i');

        // Catch-up: selama masih di hari yang sama (WIB), jadwal yang terlewat
        // tetap dikirim sekali. Dedup harian dijamin event_key dispatches.
        return $currentMinutes >= $scheduledMinutes;
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
            ->where(function ($query): void {
                $query->whereNotNull('tester_id')
                    ->orWhereHas('testers');
            })
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
            ->with(['tester', 'testers'])
            ->orderBy('id')
            ->chunkById(100, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    DB::transaction(function () use ($schedule): void {
                        $lockedSchedule = QaSchedule::query()->lockForUpdate()->find($schedule->id);

                        if ($lockedSchedule === null) {
                            return;
                        }

                        // Semua tester tertugaskan: pivot multi-user + tester_id warisan.
                        $recipients = $lockedSchedule->testers
                            ->push($lockedSchedule->tester)
                            ->filter()
                            ->unique('id')
                            ->filter(fn (User $user) => $user->is_active)
                            ->values();

                        if ($recipients->isEmpty()) {
                            return;
                        }

                        if (
                            $lockedSchedule->start_notification_sent_at === null
                            && $lockedSchedule->start_time->lessThanOrEqualTo(now())
                            && ! in_array($lockedSchedule->status, ['Completed', 'Cancelled'], true)
                        ) {
                            $recipients->each(fn (User $user) => $user->notify(new QaReminderNotification(
                                'Waktunya memulai QA',
                                "Jadwal {$lockedSchedule->schedule_code} - {$lockedSchedule->title} sudah dimulai.",
                                '/schedules',
                            )));
                            $lockedSchedule->start_notification_sent_at = now();
                        }

                        if (
                            $lockedSchedule->end_notification_sent_at === null
                            && $lockedSchedule->end_time->lessThan(now())
                            && ! in_array($lockedSchedule->status, ['Completed', 'Cancelled'], true)
                        ) {
                            $recipients->each(fn (User $user) => $user->notify(new QaReminderNotification(
                                'Waktu QA telah berakhir',
                                "Jadwal {$lockedSchedule->schedule_code} - {$lockedSchedule->title} telah melewati waktu yang ditentukan. Aktivitas QA terkait terkunci untuk user.",
                                '/schedules',
                            )));
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
