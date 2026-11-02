<?php

namespace App\Console\Commands;

use App\Services\ReminderDeliveryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:dispatch-due')]
#[Description('Dispatch scheduled reminders and QA schedule notifications')]
class DispatchDueNotifications extends Command
{
    public function handle(ReminderDeliveryService $delivery): int
    {
        $count = $delivery->dispatchDue();

        $this->info("Processed due reminders; delivered {$count} admin reminder(s).");

        return self::SUCCESS;
    }
}
