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
            if ($daysLeft <= 0 && $daysLeft >= -3) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "🔴 انتهت صلاحية اشتراكك",
                    "عزيزي العميل، لقد انتهى اشتراكك في المندوب الذكي. جدّد اشتراكك الآن للاستمرار في استخدام جميع المميزات دون انقطاع. 🙏",
                    "plan_deactivated"
                );
                $sent++;
                Log::info("SendPlanNotifications: plan_deactivated sent to user {$user->id}");
            }

            // قبل 7 أيام
            if ($daysLeft == 7) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "📅 اشتراكك ينتهي بعد 7 أيام",
                    "مرحباً! نودّ تذكيرك بأن اشتراكك سينتهي خلال أسبوع. جدّد مسبقاً لتستمر في العمل بدون أي توقف. نقدّر ثقتك بنا! 💙",
                    "still_7_days"
                );
                $sent++;
                Log::info("SendPlanNotifications: still_7_days sent to user {$user->id}");
            }

            // قبل 3 أيام
            if ($daysLeft == 3) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "⏳ تبقّى 3 أيام على انتهاء اشتراكك",
                    "تنبيه ودّي! اشتراكك سينتهي بعد 3 أيام فقط. لا تدع العمل يتوقف — جدّد الآن بكل سهولة وتابع إنجازاتك. 🚀",
                    "still_3_days"
                );
                $sent++;
                Log::info("SendPlanNotifications: still_3_days sent to user {$user->id}");
            }

            // قبل يوم واحد
            if ($daysLeft == 1) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "⚠️ آخر يوم في اشتراكك!",
                    "اشتراكك سينتهي غداً. جدّد الآن حتى لا تفوّتك أي لحظة من عملك. نحن هنا دائماً لخدمتك! 😊",
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

