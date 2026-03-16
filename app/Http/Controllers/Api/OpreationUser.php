<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppHarfosh;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseNotificationService;
class OpreationUser extends Controller
{

    // ===============================
    // Register / Check Device
    // ===============================
    public function create_device(Request $request)
    {
        $request->validate([
            'app_name'   => 'required|string',
            'device_id'  => 'required|string',
            'full_name'  => 'required|string',
            'phone'      => 'required|string',
            'fcm_token'  => 'nullable|string'
        ]);

        $app = AppHarfosh::where('device_id', $request->device_id)
        ->where('app_name', $request->app_name)
        ->first();

        if ($app) {
            $app->update(['fcm_token' => $request->fcm_token  ?? $app->fcm_token]);
            return response()->json([
                'is_verified' => $app->is_verified,
                'expires_at'  => $app->expires_at,
                'plan'        => $app->plan_id,
                'fcm_token'   => $app->fcm_token
            ], 200);

        } else {

            $data = [
                'app_name'   => $request->app_name,
                'device_id'  => $request->device_id,
                'full_name'  => $request->full_name,
                'phone'      => $request->phone,
                'is_verified'=> 0,
                'fcm_token'  => $request->fcm_token  ?? null
            ];

            AppHarfosh::create($data);

            return response()->json([
                'is_verified' => 0,
                'expires_at'  => null,
                'plan'        => null,
                'fcm_token'   => $data['fcm_token'],
            ], 200);
        }
    }
     public function checkDevice(Request $request)
    {
        // التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'app_name'  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $app = AppHarfosh::where('device_id', $request->device_id)
                        ->where('app_name', $request->app_name)
                        ->first();

        if (!$app) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }


        $isVerified = $app->is_verified;
        $expiresAt = $app->expires_at;
        $currentTime = Carbon::now();


        if ($expiresAt && Carbon::parse($expiresAt)->lessThan($currentTime)) {
            $isVerified = false;
        }


        return response()->json([
            'success'     => true,
            'is_verified' => (int) $isVerified,
            'plan'        => $app->plan_id,
            'expires_at'  => $expiresAt ?? null,
            'server_time' => $currentTime->toISOString(),
        ], 200);
    }
  public function updateMyData(Request $request)
    {
        // التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'app_name'  => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'phone'     => 'required|string|max:255',
            'fcm_token' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $app = AppHarfosh::where('device_id', $request->device_id)
                        ->where('app_name', $request->app_name)
                        ->first();

        if (!$app) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }


      $app->update([
          'full_name' => $request->full_name,
          'phone'     => $request->phone,
          'fcm_token' => $request->fcm_token ?? null
          ]);
        return response()->json([
            'success'     => true,
        ], 200);
    }

    // ===============================
    // Admin: Activate Device
    // ===============================
    public function activate_device(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'plan_id'   => 'required|string'
        ]);

        $app = AppHarfosh::where('device_id', $request->device_id)->first();

        if (!$app) {
            return response()->json([
                'message' => 'Device not found'
            ], 404);
        }

        // Calculate expiration
        $months = 0;

        if ($request->plan_id == 'half_year') {
            $months = 6;
        }

        if ($request->plan_id == 'yearly') {
            $months = 12;
        }

        $expiresAt = Carbon::now()->addMonths($months);

        $app->update([
            'is_verified' => 1,
            'plan_id'     => $request->plan_id,
            'expires_at'  => $expiresAt
        ]);

        // إشعار التفعيل
        if ($app->fcm_token) {
            $planLabels = [
                'half_year' => 'نصف السنوية (6 أشهر)',
                'yearly'    => 'السنوية (12 شهراً)',
            ];
            $planLabel   = $planLabels[$request->plan_id] ?? $request->plan_id;
            $expiresDate = $expiresAt->format('Y/m/d');

            $firebase = new FirebaseNotificationService();
            $firebase->send(
                $app->fcm_token,
                "🎉 تم تفعيل اشتراكك بنجاح!",
                "أهلاً {$app->full_name}! تم تفعيل خطّتك {$planLabel} بنجاح ✅\nتنتهي بتاريخ: {$expiresDate}\nنتمنى لك تجربة رائعة معنا 💙",
                "new_plan_activated"
            );
        }

        return response()->json([
            'success'    => true,
            'is_verified'=> 1,
            'plan'       => $request->plan_id,
            'expires_at' => $expiresAt
        ], 200);
    }

    // ===============================
    // Get All Devices (Admin)
    // ===============================
    public function get_device()
    {
        $apps = AppHarfosh::latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $apps
        ], 200);
    }

    // ===============================
    // Get Subscription Plans
    // ===============================
    public function get_plans()
    {
        return response()->json([
            'success' => true,

            'currency' => [
                'code'   => 'USD',
                'symbol' => '$'
            ],

            'plans' => [

                [
                    'id' => 'half_year',
                    'title' => 'الخطة نصف السنوية',
                    'duration_months' => 6,
                    'price'=> 12,
                    'enabled' => true,
                    'recommended' => false,
                    'description' => 'أفضل خيار للتجربة طويلة المدى',
                ],

                [
                    'id' => 'yearly',
                    'title' => 'الخطة السنوية',
                    'duration_months' => 12,
                    'price'=> 20,
                    'enabled' => true,
                    'recommended' => true,
                    'description' => 'الأكثر توفيراً',
                ]
            ]

        ], 200);
    }

    public function sendPlanNotifications()
    {
        $users = AppHarfosh::whereNotNull('fcm_token')->get();

        $firebase = new FirebaseNotificationService();

        foreach ($users as $user) {

            if(!$user->expires_at) continue;

            $daysLeft = Carbon::now()->diffInDays(Carbon::parse($user->expires_at), false);

            // انتهاء الاشتراك
            if ($daysLeft < 0) {
                $firebase->send(
                    $user->fcm_token,
                    "🔴 انتهت صلاحية اشتراكك",
                    "عزيزي {$user->full_name}، لقد انتهى اشتراكك في المندوب الذكي. جدّد اشتراكك الآن للاستمرار في استخدام جميع المميزات دون انقطاع. 🙏",
                    "plan_deactivated"
                );
            }

            // قبل 7 أيام
            if ($daysLeft == 7) {
                $firebase->send(
                    $user->fcm_token,
                    "📅 اشتراكك ينتهي بعد 7 أيام",
                    "مرحباً {$user->full_name}! نودّ تذكيرك بأن اشتراكك سينتهي خلال أسبوع. جدّد مسبقاً لتستمر في العمل بدون أي توقف. نقدّر ثقتك بنا! 💙",
                    "still_7_days"
                );
            }

            // قبل 3 أيام
            if ($daysLeft == 3) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "⏳ تبقّى 3 أيام على انتهاء اشتراكك",
                    "تنبيه ودّي يا {$user->full_name}! اشتراكك سينتهي بعد 3 أيام فقط. لا تدع العمل يتوقف — جدّد الآن بكل سهولة وتابع إنجازاتك. 🚀",
                    "still_3_days"
                );
            }

            // قبل يوم
            if ($daysLeft == 1) {
                $firebase->sendNotification(
                    $user->fcm_token,
                    "⚠️ آخر يوم في اشتراكك!",
                    "اشتراكك سينتهي غداً يا {$user->full_name}. جدّد الآن حتى لا تفوّتك أي لحظة من عملك. نحن هنا دائماً لخدمتك! 😊",
                    "still_1_day"
                );

            }

        }

        return response()->json([
            "success" => true
        ]);
    }
    public function testSendNotifications()
    {
        $users = AppHarfosh::whereNotNull('fcm_token')->get();

        $firebase = new FirebaseNotificationService();

        foreach ($users as $user) {
            $response = $firebase->send(
                $user->fcm_token,
                "🔴 انتهت صلاحية اشتراكك",
                "عزيزي {$user->full_name}، لقد انتهى اشتراكك في المندوب الذكي. جدّد اشتراكك الآن للاستمرار في استخدام جميع المميزات دون انقطاع. 🙏",
                "plan_deactivated"
            );
        }

        return response()->json([
            "success" => true,
            'response'=> $response
        ]);
    }
}
