<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppHarfosh;
use App\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendPlanNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send subscription expiry notifications to users daily';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = AppHarfosh::whereNotNull('fcm_token')->get();

        $firebase = new FirebaseNotificationService();

        $sent = 0;

        foreach ($users as $user) {

            if (!$user->expires_at) continue;

            $daysLeft = Carbon::now()->diffInDays(Carbon::parse($user->expires_at), false);

            // انتهاء الاشتراك
            if ($daysLeft < 0) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "انتهى الاشتراك",
                    "تم إيقاف الباقة الخاصة بك، يرجى تجديد الاشتراك.",
                    "plan_deactivated"
                );
                $sent++;
                Log::info("SendPlanNotifications: plan_deactivated sent to user {$user->id}");
            }

            // قبل 7 أيام
            if ($daysLeft == 7) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "تبقى 7 أيام",
                    "اشتراكك سينتهي بعد 7 أيام.",
                    "still_7_days"
                );
                $sent++;
                Log::info("SendPlanNotifications: still_7_days sent to user {$user->id}");
            }

            // قبل 3 أيام
            if ($daysLeft == 3) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "تبقى 3 أيام",
                    "اشتراكك سينتهي بعد 3 أيام.",
                    "still_3_days"
                );
                $sent++;
                Log::info("SendPlanNotifications: still_3_days sent to user {$user->id}");
            }

            // قبل يوم واحد
            if ($daysLeft == 1) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "تبقى 24 ساعة",
                    "اشتراكك سينتهي غداً.",
                    "still_1_day"
                );
                $sent++;
                Log::info("SendPlanNotifications: still_1_day sent to user {$user->id}");
            }
        }

        $this->info("✅ Done! Notifications sent: {$sent}");
        Log::info("SendPlanNotifications: finished. Total sent: {$sent}");

        return Command::SUCCESS;
    }
}

