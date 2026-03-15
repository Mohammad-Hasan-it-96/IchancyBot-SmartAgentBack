<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TelegramController extends Controller
{
    protected $telegram;
     protected $adminIds;
    protected $requiredChannel = '-1002582950818';
    protected $channelUsername = '@harraypot';
    protected $usdToSyp = 1;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->adminIds = explode(',', env('ADMIN_TELEGRAM_ID'));
    }

    public function webhook(Request $request)
    {
        $update = $this->telegram->getWebhookUpdate();
        // Log::info('Webhook Update:', ['update' => $update]);

        if (isset($update['message'])) {
            $this->handleUserMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    private function handleUserMessage($message)
    {
        $chatId = $message['from']['id'];
        $text = $message['text'] ?? '';
        

        // تحقق مما إذا كنا بانتظار مبلغ الإيداع من المستخدم
        if (Cache::has("deposit_step_{$chatId}")) {
            $step = Cache::get("deposit_step_{$chatId}");
            
            if ($step === 'awaiting_amount') {
                // التحقق من أن المبلغ صحيح
                $amount = floatval($text);
                if ($amount <= 10000) {
                    Cache::forget("deposit_step_{$chatId}");
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❌ المبلغ يجب أن يكون أكبر من 10000. الرجاء المحاولة مرة أخرى."
                    ]);
                    return;
                }
                
                // حفظ المبلغ والانتقال لخطوة إدخال رقم المعاملة
                Cache::put("deposit_amount_{$chatId}", $amount, now()->addMinutes(10));
                Cache::put("deposit_step_{$chatId}", 'awaiting_txid', now()->addMinutes(10));
                
                $method = Cache::get("deposit_method_{$chatId}");
                $instruction = match($method) {
                    'سيرتيل كاش - تحويل يدوي' => "📲 أرسل {$amount} إلى الكود: 09304661\nوأدخل رقم العملية :",
                    'ام تي ان كاش' => "📲 أرسل {$amount} إلى الكود: 92868480\nوأدخل رقم العملية :",
                    'USDT Trc20' => "📤 أرسل {$amount} USDT (TRC20) إلى العنوان:\n\nTSwJjRPgjs9q4Gi7ruBMeQzjdvsLtr1rxe\n\n وأدخل TXID كود عملية التحويل:",
                    'USDT Erc20' => "📤 أرسل {$amount} USDT (ERC20) إلى العنوان:\n\n0x53ACdDcFc70cdCCd118e46f57fcdfb2DBBf52617\n\nوأدخل TXID كود عملية التحويل:",
                    'USDT Bep20' => "📤 أرسل {$amount} USDT (BEP20) إلى العنوان:\n\n0x53ACdDcFc70cdCCd118e46f57fcdfb2DBBf52617\n\nوأدخل TXID كود عملية التحويل:"
                };
                
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $instruction
                ]);
            }
           
            elseif ($step === 'awaiting_txid') {
                
                if (str_starts_with($text, '/')) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❗️يرجى إرسال *رقم العملية فقط*.",
                    ]);
                    
                    Cache::forget("deposit_step_{$chatId}");
                    Cache::forget("deposit_method_{$chatId}");
                    Cache::forget("deposit_amount_{$chatId}");
                    
                    return;
                }
                
                $method = Cache::get("deposit_method_{$chatId}");
                $amount = Cache::get("deposit_amount_{$chatId}");
                $txid = $text;
                if($method == 'ام تي ان كاش'){
                    Cache::forget("deposit_step_{$chatId}");
                    Cache::forget("deposit_method_{$chatId}");
                    Cache::forget("deposit_amount_{$chatId}");
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❌ عذرا ام تي ان كاش متوقف حاليا سيعود للعمل في أقرب وقت."
                    ]);
                    return;
                }
                
                // إرسال الطلب للأدمن
                
                foreach ($this->adminIds as $adminId) {
                    $msg = "🧾 طلب إيداع جديد:\n\n👤 المستخدم: $chatId\n💰 المبلغ: $amount\n💳 الطريقة: $method\n🔢 رقم العملية: $txid";
        
                    $this->telegram->sendMessage([
                        'chat_id' => $adminId,
                        'text' => $msg,
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    ['text' => "✅ تأكيد الإيداع", 'callback_data' => "/confirm_{$chatId}_{$amount}"],
                                    ['text' => '❌ إلغاء', 'callback_data' => "cancel_deposit_{$chatId}"]
                                ]
                            ]
                        ])
                    ]);
                }
    
    
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "✅ تم إرسال طلبك للإدارة وسيتم مراجعته قريباً."
                ]);
    
                // حذف البيانات المؤقتة
                Cache::forget("deposit_step_{$chatId}");
                Cache::forget("deposit_method_{$chatId}");
                Cache::forget("deposit_amount_{$chatId}");
    
                return;
            }
        }

        // تحقق مما إذا كنا بانتظار رقم التحويل
        

         if (str_starts_with($text, '/start')) {
            $this->handleStartCommand($chatId, $text);
        }
        
        if (str_starts_with($text, '/balance')) {
            // $this->handleStartCommand($chatId);
            $this->showUserBalance($chatId);
        }

        // معالجة تأكيد الإيداع من الأدمن
        if (str_starts_with($text, '/confirm_')) {
            if (!in_array((string)$chatId, $this->adminIds)) return;

            $parts = explode('_', $text);
            if (count($parts) === 3) {
                $targetId = $parts[1];
                $amount = floatval($parts[2]);

                $user = User::where('telegram_id', $targetId)->first();
                if ($user) {
                    $user->balance += $amount;
                    $user->total_deposit += $amount;
                    $user->save();
                    
                    // if($user->referred_id != null){
                    //     $referred = User::where('telegram_id',$user->referred_id)->first();
                    //     $amount_referred = $amount * 0.03;
                    //     $referred->balance += $amount_referred;
                    //     $referred->referrals_balance += $amount_referred;
                    //     $referred->save();
                    //     $this->telegram->sendMessage([
                    //         'chat_id' => $referred->telegram_id,
                    //         'text' => "✅ تم إضافة $amount_referred إلى رصيدك عن طريق رابط الاحالة .  شكراً!"
                    //     ]);
                    // }

                    $this->telegram->sendMessage([
                        'chat_id' => $targetId,
                        'text' => "✅ تم إضافة $amount إلى رصيدك. شكراً!"
                    ]);

                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "✅ تم تأكيد الإيداع بنجاح."
                    ]);
                }
            }
        }
        
        // معالجة خطوة إدخال مبلغ السحب
        if (Cache::has("withdraw_step_{$chatId}")) {
            $step = Cache::get("withdraw_step_{$chatId}");
            
            if ($step === 'awaiting_amount') {
                $amount = floatval($text);
                $method = Cache::get("withdraw_method_{$chatId}");
                $user = User::where('telegram_id', $chatId)->first();
                
                // التحقق من الرصيد
                if (!$user  || $amount > $user->balance) {
                    $balance = $user ? number_format($user->balance, 2) : '0.00';
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❌ المبلغ غير صالح أو رصيدك غير كافٍ. رصيدك الحالي: {$balance} "
                    ]);
                    Cache::forget("withdraw_step_{$chatId}");
                    return;
                }
                
                // الانتقال لخطوة إدخال المحفظة
                Cache::put("withdraw_amount_{$chatId}", $amount, now()->addMinutes(10));
                Cache::put("withdraw_step_{$chatId}", 'awaiting_wallet', now()->addMinutes(10));
                $code = $amount * 0.1;
                $total = $amount - $code;
                $method = Cache::get("withdraw_method_{$chatId}");
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "📌 أرسل عنوان المحفظة أو رقم الحساب لسحب {$amount} علما انه سيتم اقتطاع 10% عمولة للسحب اي سيصلك {$total} عبر {$method}:"
                ]);
            }
            elseif ($step === 'awaiting_wallet') {
                // معالجة إدخال المحفظة (الكود الحالي)
                $amount = Cache::get("withdraw_amount_{$chatId}");
                $method = Cache::get("withdraw_method_{$chatId}");
                $wallet = $text;
                // if (Str::contains($method, 'سيرتيل كاش - تحويل يدوي')) {
                //     $amount = $amount / 12000;
                //     $amount = number_format($amount, 2);
                // }
                $code = $amount * 0.1;
                $total = $amount - $code;
                // $adminId = env('ADMIN_TELEGRAM_ID');
                foreach ($this->adminIds as $adminId) {
                    $msg = "📤 طلب سحب جديد:\n\n👤 المستخدم: $chatId\n💸 المبلغ: $amount\n 💸 الاجمالي : $total \n\n🔌 الطريقة: $method\n🏦 المحفظة: $wallet";
                    
                    $this->telegram->sendMessage([
                        'chat_id' => $adminId,
                        'text' => $msg,
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                  ['text' => "✅ تأكيد السحب", 'callback_data' => "/confirm_withdraw_{$chatId}_{$amount}"]
                                ]
                            ]
                        ])
                    ]);
                }
                
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "✅ تم إرسال طلب السحب للإدارة"
                ]);
                
                // مسح البيانات المؤقتة
                Cache::forget("withdraw_step_{$chatId}");
                Cache::forget("withdraw_method_{$chatId}");
                Cache::forget("withdraw_amount_{$chatId}");
            }
        }
        
            // معالجة اختيار وسيلة السحب
        
        if (Cache::get("eshansy_step_{$chatId}") === "awaiting_withdraw_amount") {
            $amount = floatval($text);
            
            if ($amount <= 0) {
                Cache::forget("eshansy_step_{$chatId}");
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ المبلغ يجب أن يكون أكبر من الصفر!"
                ]);
                return;
            }
            $user = User::where('telegram_id',$chatId)->first();
            $username = $user->ichancy_username;
            // إرسال طلب السحب للإدارة
            foreach ($this->adminIds as $adminId) {
                $this->telegram->sendMessage([
                    'chat_id' => $adminId,
                    'text' => "📤 *طلب سحب رصيد إيشنسي*\n\n"
                               ."👤 المستخدم: $chatId\n"
                              ."👤 المستخدم على الموقع: $username\n"
                              ."💵 المبلغ: $amount\n\n"
                              ."⚠️ يرجى التحقق من رصيده في الموقع قبل التنفيذ.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => "✅ تأكيد السحب", 'callback_data' => "eshansy_withdraw_approve_{$chatId}_{$amount}"],
                                ['text' => "❌ رفض", 'callback_data' => "eshansy_withdraw_reject_{$chatId}"]
                            ]
                        ]
                    ])
                ]);
            }
            
            Cache::forget("eshansy_step_{$chatId}");
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📨 تم إرسال طلبك للإدارة. سيتم التحقق من رصيدك في الموقع وإعلامك بالنتيجة."
            ]);
        }
        if (Cache::get("waiting_for_message_{$chatId}")) {
            $this->forwardMessageToAdmin($chatId, $text);
            Cache::forget("waiting_for_message_{$chatId}");
        }
        
        // معالجة رد الإدارة
        if (Cache::get("admin_reply_to_{$chatId}")) {
            $targetUserId = Cache::get("admin_reply_to_{$chatId}");
            $this->telegram->sendMessage([
                'chat_id' => $targetUserId,
                'text' => "📬 رد من الإدارة:\n\n$text"
            ]);
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ تم إرسال الرد بنجاح."
            ]);
            
            Cache::forget("admin_reply_to_{$chatId}");
        }
        
        if (Cache::get("eshansy_step_{$chatId}") === "awaiting_username") {
            $requestedUsername = $text;
            
            // إرسال الطلب للإدارة بدون ربط اسم المستخدم المدخل
            foreach ($this->adminIds as $adminId) {
                $this->telegram->sendMessage([
                    'chat_id' => $adminId,
                    'text' => "📝 طلب إنشاء حساب إيشنسي جديد\n\n"
                             ."👤 اللاعب: $chatId\n"
                             ."✏️ الاسم المقترح: $requestedUsername\n\n"
                             ."الرجاء إنشاء الحساب وإرسال:\n"
                             ."1. اسم المستخدم النهائي\n"
                             ."2. كلمة المرور",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [['text' => "إنشاء الحساب", 'callback_data' => "eshansy_create_for_{$chatId}"]]
                        ]
                    ])
                ]);
            }
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "تم إرسال طلبك للإدارة. سيتم إعلامك بالبيانات عند الإنشاء"
            ]);
            
            Cache::forget("eshansy_step_{$chatId}");
        }
        if (Cache::get("gift_step_{$chatId}") === "awaiting_receiver") {
            $receiverInput = $text;
            $user = User::where('telegram_id', $chatId)
                        ->first();            
            // البحث عن المستلم
            $receiver = User::where('telegram_id', $receiverInput)
                        ->orWhere('ichancy_username', str_replace('@', '', $receiverInput))
                        ->first();
        
            if (!$receiver) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ لا يوجد لاعب بهذا المعرف!"
                ]);
                Cache::forget("gift_step_{$chatId}");
            Cache::forget("gift_receiver_{$chatId}");
                return;
            }
        
            if ($receiver->telegram_id == $chatId) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ لا يمكن إهداء الرصيد لنفسك!"
                ]);
                Cache::forget("gift_step_{$chatId}");
            Cache::forget("gift_receiver_{$chatId}");
                return;
            }
        
            // حفظ البيانات والانتقال لمرحلة المبلغ
            Cache::put("gift_receiver_{$chatId}", $receiver->telegram_id, now()->addHours(1));
            Cache::put("gift_step_{$chatId}", "awaiting_amount_gift", now()->addHours(1));
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "💰 أدخل المبلغ المُراد إهداؤه:\n"
                        ."مع العلم انه سيتم خصم 5% على عملية الاهداء \n"
                         ."رصيدك الحالي: " . $user->balance . " $",
                'reply_markup' => json_encode([
                    'keyboard' => [["🚫 إلغاء العملية"]],
                    'resize_keyboard' => true
                ])
            ]);
            return;
        }
        if (Cache::get("gift_step_{$chatId}") === "awaiting_amount_gift") {
            if (!is_numeric($text) || $text <= 0) {
                Cache::forget("gift_step_{$chatId}");
                Cache::forget("gift_receiver_{$chatId}");
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ يرجى إدخال رقم صحيح أكبر من الصفر!"
                ]);
                Cache::forget("gift_step_{$chatId}");
                Cache::forget("gift_receiver_{$chatId}");
                Cache::forget("gift_amount_{$chatId}");
                return;
            }
        
            $amount = (float)$text;
            $sender = User::where('telegram_id', $chatId)->first();
            $receiverId = Cache::get("gift_receiver_{$chatId}");
            $receiver = User::where('telegram_id', $receiverId)->first();
            
            if ($amount > $sender->balance) {

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ رصيدك غير كافي!\n"
                             ."رصيدك الحالي: {$sender->balance} $\n"
                             ."المبلغ المطلوب: {$amount} $"
                ]);
                return;
            }
        
            // تنفيذ الإهداء
            DB::transaction(function () use ($sender, $receiver, $amount, $chatId) {
                $sender->balance -= $amount;
                $code = $amount * 0.05;
                $total = $amount - $code;
                $receiver->balance += $total;
                $sender->save();
                $receiver->save();
                $admin = User::where('telegram_id', '1088015905')->first();
                $admin->balance += $code;  
                $admin->save();
                
                Cache::forget("gift_step_{$chatId}");
                Cache::forget("gift_receiver_{$chatId}");
                // إرسال إشعار للمرسل
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "✅ تم إهداء {$total} إلى @{$receiver->ichancy_username} بنجاح!\n"
                             ."رصيدك الجديد: {$sender->balance} "
                             ."عمولة البوت : {$code} ",
                    'reply_markup' => json_encode(['remove_keyboard' => true])
                ]);
        
                // إرسال إشعار للمستلم
                $this->telegram->sendMessage([
                    'chat_id' => $receiver->telegram_id,
                    'text' => "🎉 تلقيت هدية بقيمة {$total} $ من @{$sender->ichancy_username}!\n"
                             ."رصيدك الجديد: {$receiver->balance} "
                             ."عمولة البوت : {$code} "
                ]);
            });
        
            // مسح بيانات العملية
            Cache::forget("gift_step_{$chatId}");
            Cache::forget("gift_receiver_{$chatId}");
            Cache::forget("awaiting_amount_gift");
        }
         if ($text === "🚫 إلغاء العملية") {
            Cache::forget("eshansy_step_{$chatId}");
             Cache::forget("gift_step_{$chatId}");
            Cache::forget("gift_receiver_{$chatId}");
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ تم إلغاء العملية بنجاح.",
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ]);
            return; // إنهاء الدالة هنا لتجنب تنفيذ أكواد أخرى
        }
        // في handleUserMessage
        if (Cache::has("eshansy_admin_creating_for_{$chatId}")) {
           
                $username = $text;
                $password = "Yh667711";
                $targetUserId = Cache::get("eshansy_admin_creating_for_{$chatId}");
                $user = User::where('telegram_id',$targetUserId)->first();
                $data = [
                    'have_ichancy_account'=>1,
                    'ichancy_username' =>$username
                ];
                $user->update($data);
                // إرسال البيانات للاعب
                // $this->telegram->sendMessage([
                //     'chat_id' => $targetUserId,
                //     'text' => "✅ تم إنشاء حساب إيشنسي لك\n\n"
                //              ."👤 اسم المستخدم: $username\n"
                //              ."🔑 كلمة المرور: $password\n\n"
                //              ."يمكنك تعديل كلمة المرور بعد الدخول"
                // ]);
                $this->telegram->sendMessage([
                    'chat_id' => $targetUserId,
                    'text' => "🎉 *تم إنشاء حسابك بنجاح\\!* \n\n🔐 *بيانات الدخول:*\n\n👤 اسم المستخدم: \n`$username` \n\n🔑 كلمة المرور: \n`Yh667711` \n\n🎁 لديك الآن *يمكنك الاستفادة من عجلة الحظ * 🎡\\!\n\n⚠️ احفظ هذه البيانات في مكان آمن\\!",
                    'parse_mode' => 'MarkdownV2',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                           
                            [
                                ['text' => 'الدخول إلى الحساب', 'url' => 'https://ichancy.com'] // الرابط المطلوب
                            ]
                        ]
                    ])
                ]);
                // تأكيد للإدارة
                
                foreach ($this->adminIds as $adminId) {
                    $this->telegram->sendMessage([
                        'chat_id' => $adminId,
                         'text' => "تم إرسال البيانات للاعب $targetUserId"
                    ]);                
                    
                }
                
                Cache::forget("eshansy_admin_creating_for_{$chatId}");
           
        }
    
        if (Cache::get("eshansy_step_{$chatId}") === "awaiting_deposit_amount") {
            $amount = floatval($text);
            $user = User::where('telegram_id', $chatId)->first();
        
            if ($amount <= 0 || $amount > $user->balance) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ المبلغ غير صالح! الرصيد المتاح: {$user->balance}"
                ]);
                return;
            }
            $username = $user->ichancy_username;
            
            $pending = DB::table('pending_rewards')->where('telegram_id', $chatId)->get();
            $totalReward = 0;

            foreach ($pending as $reward) {
                $totalReward += $reward->reward;
                 DB::table('pending_rewards')->where('id', $reward->id)->delete();
            }
            
            $caption = "🎡 جائزة عجلة الحظ: $totalReward\n";
            // إرسال طلب الشحن للإدارة
            foreach ($this->adminIds as $adminId) {
                $this->telegram->sendMessage([
                    'chat_id' => $adminId,
                    'text' => "📥 *طلب شحن رصيد إيشنسي*\n\n"
                               ."👤 المستخدم: $chatId\n"
                                ."👤 المستخدم على الموقع: $username\n"
                                . "$caption\n"
                              ."💵 المبلغ: $amount\n\n"
                              ."🔄 الرصيد قبل الشحن: {$user->balance} ",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => "✅ تأكيد الشحن", 'callback_data' => "eshansy_deposit_approve_{$chatId}_{$amount}"],
                                ['text' => "❌ رفض", 'callback_data' => "eshansy_deposit_reject_{$chatId}_{$amount}"]
                            ]
                        ]
                    ])
                ]);
            }
        
            Cache::forget("eshansy_step_{$chatId}");
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📤 تم إرسال طلب شحن الرصيد للإدارة. سيتم الخصم من رصيدك بعد الموافقة."
            ]);
            // $this->telegram->sendMessage([
            //     'chat_id' => $chatId,
            //     'text' => $messageText,
            //     'parse_mode' => 'HTML',  // or 'Markdown' if you're using markdown
            //     'disable_web_page_preview' => true
            // ]);
        }
        if (Cache::get("user_{$chatId}_action") === 'awaiting_prize_code') {
            $prizeCode = trim($text);
            
            if ($prizeCode === '🚫 إلغاء') {
                Cache::forget("user_{$chatId}_action");
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "تم إلغاء العملية",
                    'reply_markup' => json_encode(['remove_keyboard' => true])
                ]);
                return;
            }
            
            // إرسال طلب الموافقة للإدارة
            foreach ($this->adminIds as $adminId) {
                $this->telegram->sendMessage([
                    'chat_id' => $adminId,
                    'text' => "🎟 *طلب كود جائزة جديد*\n\n"
                             ."👤 المستخدم:  ($chatId)\n"
                             ."🔢 الكود: `{$prizeCode}`\n\n"
                             ."الرجاء الموافقة أو الرفض:",
                    'parse_mode' => 'Markdown',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => '✅ الموافقة', 'callback_data' => "approve_prize_{$chatId}_{$prizeCode}"],
                                ['text' => '❌ رفض', 'callback_data' => "reject_prize_{$chatId}"]
                            ]
                        ]
                    ])
                ]);
            }
            
            Cache::forget("user_{$chatId}_action");
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📬 تم إرسال طلبك للإدارة. سيتم إعلامك بالنتيجة قريبًا!",
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ]);
        }
        // if (str_contains($text, '/start ref_')) {
        //     $this->handleStartCommand($chatId, $text);
        //     return;
        // }

    }

    private function handleCallbackQuery($callbackQuery)
    {
        $chatId = $callbackQuery['from']['id'];
        $data = $callbackQuery['data'];
        $messageId = $callbackQuery['message']['message_id'];
    

        if ($data === 'check_subscription') {
            $this->handleSubscriptionCheck($chatId, $messageId);
        }
        elseif ($data === 'eshansy_create') {
            $user = User::where('telegram_id',$chatId)->where('have_ichancy_account',1)->first();
            if($user){
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ *عذراا لديك حساب ايشانسي سابق * اسم الحساب $user->ichancy_username  وكلمة المرور Yh667711"
                ]);
                return;
            }
            Cache::put("eshansy_step_{$chatId}", "awaiting_username", now()->addMinutes(15));
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📝 *إنشاء حساب إيشنسي جديد*\n\n"
                         ."أدخل اسم المستخدم المطلوب (باللغة الإنجليزية):\n"
                         ."مثال: `eshansy_user123`",
                'parse_mode' => 'Markdown'
            ]);
        }
        elseif ($data === 'eshansy_menu') {
            $this->showEshansyMenu($chatId);
        }
        elseif (str_starts_with($data, 'eshansy_create_for_')) {
            $targetUserId = str_replace('eshansy_create_for_', '', $data);
            
            // حفظ حالة الإدارة لاستقبال البيانات
            Cache::put("eshansy_admin_creating_for_{$chatId}", $targetUserId, now()->addHours(1));
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "أدخل بيانات الحساب الجديد:\n"
                         ."1. اسم المستخدم\n"
                         ."الرجاء إرسالهم بهذا الشكل:\n"
                         ."username"
            ]);
        }
        elseif ($data === 'eshansy_deposit') {
                $user = User::where('telegram_id', $chatId)->first();
                if($user->have_ichancy_account != 1){
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❌ *ليس لديك حساب على الموقع!*\n\n",
                        'parse_mode' => 'Markdown'
                    ]);
                    return;
                }
                if (!$user || $user->balance <= 0) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❌ *لا يوجد رصيد كافي!*\n\n"
                                  ."رصيدك الحالي: " . ($user ? $user->balance : 0) . " $\n"
                                  ."يمكنك شحن رصيدك عبر زر ➕ إيداع رصيد",
                        'parse_mode' => 'Markdown'
                    ]);
                    return;
                }
            
                Cache::put("eshansy_step_{$chatId}", "awaiting_deposit_amount", now()->addMinutes(15));
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "💳 *شحن رصيد إيشنسي*\n\n"
                             ."أدخل المبلغ المطلوب شحنه:\n"
                             ."رصيدك الحالي: *{$user->balance} $*",
                    'parse_mode' => 'Markdown'
                ]);
            }
        elseif (str_starts_with($data, 'eshansy_deposit_approve_')) {
            if (!in_array($chatId, $this->adminIds)) return;
            
            $parts = explode('_', $data);
            $targetUserId = $parts[3];
            $amount = $parts[4];
        
            $user = User::where('telegram_id', $targetUserId)->first();
            if ($user && $user->balance >= $amount) {
                $user->balance -= $amount;
                $user->total_deposit_for_account += $amount;
                $user->xp += 5;
                $user->save();
                $data = [
                        'user_id'=>$user->id,
                        'type'=>'deposit',
                        'amount'=>$amount,
                        'status'=>'completed'
                ];
                Transaction::create($data);
                $lastWeek = Carbon::now()->subDays(7);
                $transactions = DB::table('transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 0)
                    ->where('type', 'deposit') // إذا عندك نوع العملية
                    ->where('created_at', '>=', $lastWeek)
                    ->get();
                
                $totalAmount = $transactions->sum('amount');
                
                // احسب عدد الفرص المستحقة
                $chancesToAdd = floor($totalAmount / 200000);
                
                if ($chancesToAdd > 0) {
                    // زيد الفرص للمستخدم
                    DB::table('wheel_chances')->updateOrInsert(
                        ['telegram_id' => $chatId],
                        [
                            'chances' => DB::raw("chances + $chancesToAdd"),
                            'updated_at' => now()
                        ]
                    );
                    $responseText = "🎉 مبرووووووووك لقد ربحت معنا *فرصة مجانية* لتجربة عجلة الحظ مجان";
                } 
                // إعلام المستخدم
                $this->telegram->sendMessage([
                    'chat_id' => $targetUserId,
                    'text' => "✅ *تم شحن رصيد إيشنسي بنجاح!*\n\n"
                              ."المبلغ: *$amount *\n"
                              ."🎉  بالاضافة الى جوائز عجلة الحظ ان كان لديك \n"
                              ."الرصيد المتبقي: *{$user->balance} *",
                    'parse_mode' => 'Markdown'
                ]);
        
                // تأكيد للإدارة
                foreach ($this->adminIds as $adminId) {
                    $this->telegram->sendMessage([
                        'chat_id' => $adminId,
                       'text' => "✅ تمت الموافقة على شحن $amount للمستخدم $targetUserId"
                    ]);
                }
                
            
        }
        }
        elseif (str_starts_with($data, 'eshansy_deposit_reject_')) {
            $this->rejectDepositRequest($data);
        }
        elseif (str_starts_with($data, 'approve_prize_')) {
            if (!in_array($chatId, $this->adminIds)) return;
            
            $parts = explode('_', $data);
            $targetUserId = $parts[2];
            $prizeCode = $parts[3];
            
            // هنا يمكنك التحقق من صحة الكود في قاعدة البيانات
            // $isValidCode = $this->validatePrizeCode($prizeCode);
            
            // if ($isValidCode) {
                // إضافة الرصيد
                $user = User::where('telegram_id', $targetUserId)->first();
                // $rewardAmount = $this->getPrizeAmount($prizeCode); // دالة تحدد قيمة الجائزة
                $rewardAmount = 10000;
                $user->balance += $rewardAmount;
                $user->save();
                
                // إرسال إشعار للمستخدم
                $this->telegram->sendMessage([
                    'chat_id' => $targetUserId,
                    'text' => "🎊 *تمت الموافقة على كود الجائزة!*\n\n"
                             ."تم إضافة {$rewardAmount} إلى رصيدك!\n"
                             ."الكود: `{$prizeCode}`",
                    'parse_mode' => 'Markdown'
                ]);
                
                // تأكيد للإدارة
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "✅ تمت الموافقة على الكود وإضافة الرصيد للمستخدم"
                ]);
            // } else {
            //     $this->telegram->sendMessage([
            //         'chat_id' => $chatId,
            //         'text' => "❌ كود الجائزة غير صالح أو منتهي الصلاحية"
            //     ]);
            // }
        }
        elseif (str_starts_with($data, 'reject_prize_')) {
            $targetUserId = str_replace('reject_prize_', '', $data);
            
            $this->telegram->sendMessage([
                'chat_id' => $targetUserId,
                'text' => "❌ تم رفض كود الجائزة المقدم\n\n"
                         ."السبب: الكود غير صالح أو منتهي الصلاحية"
            ]);
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ تم إبلاغ المستخدم بالرفض"
            ]);
        }
        elseif ($data === 'gift_balance') {
            $user = User::where('telegram_id', $chatId)->first();
            
            if (!$user || $user->balance <= 0) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ رصيدك غير كافي للإهداء!"
                ]);
                return;
            }
            
            Cache::put("gift_step_{$chatId}", "awaiting_receiver", now()->addHours(1));
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "👤 أدخل معرف اللاعب المستلم:\n"
                         ."- المعرف الرقمي (مثال: 123456789)\n"
                         ."- أو اسم المستخدم (مثال: @username)",
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ]);
        }
        elseif (str_starts_with($data, 'eshansy_approve_')) {
            if (!in_array($chatId, $this->adminIds)) return;
            $targetUserId = str_replace('eshansy_approve_', '', $data);
            $username = Cache::get("eshansy_username_{$targetUserId}");
            $password = Cache::get("eshansy_password_{$targetUserId}");
            $user = User::where('telegram_id',$targetUserId)->first();
            $data = [
                'have_ichancy_account'=>1,
                'ichancy_username' => $username
            ];
            $user->update($data);
        
            // إرسال البيانات للمستخدم مع أزرار نسخ تلقائي
            $this->telegram->sendMessage([
                'chat_id' => $targetUserId,
                'text' => "🎉 *تمت الموافقة على طلبك!*\n\n"
                         ."🔐 تفاصيل حساب إيشنسي:\n"
                         ."👤 اسم المستخدم: `$username`\n"
                         ."🔑 كلمة المرور: `$password`\n\n"
                         ."⚠️ انقر على أي حقل لنسخه تلقائياً",
                'parse_mode' => 'Markdown',
            ]);
        
            // تأكيد للإدارة
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ تم إنشاء حساب إيشنسي للمستخدم $targetUserId"
            ]);
        
            // مسح البيانات المؤقتة
            Cache::forget("eshansy_username_{$targetUserId}");
            Cache::forget("eshansy_password_{$targetUserId}");
        }
        elseif (str_starts_with($data, 'eshansy_reject_')) {
            if (!in_array($chatId, $this->adminIds)) return;
        
            $targetUserId = str_replace('eshansy_reject_', '', $data);
            
            $this->telegram->sendMessage([
                'chat_id' => $targetUserId,
                'text' => "❌ *تم رفض طلب إنشاء الحساب من الإدارة*"
            ]);
        
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ تم إلغاء الطلب بنجاح"
            ]);
        }
        elseif (str_starts_with($data, 'copy_data_')) {
            $copiedText = base64_decode(str_replace('copy_data_', '', $data));
            
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery['id'],
                'text' => "✅ تم النسخ: $copiedText",
                'show_alert' => false
            ]);
        }
        elseif ($data === 'eshansy_withdraw') {
            $user = User::where('telegram_id', $chatId)->first();
            if($user->have_ichancy_account != 1){
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ *ليس لديك حساب على الموقع!*\n\n",
                    'parse_mode' => 'Markdown'
                ]);
                return;
            }
            if (!$user) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ لا يوجد حساب مرتبط بك. أرسل /start لإنشاء حساب."
                ]);
                return;
            }
            
            Cache::put("eshansy_step_{$chatId}", "awaiting_withdraw_amount", now()->addMinutes(15));
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "💸 *سحب رصيد إيشنسي*\n\n"
                         ."أدخل المبلغ المطلوب سحبه:\n"
                         ."سيتم التحقق من رصيدك في الموقع قبل التنفيذ.",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ]);
        }
        elseif ($data === 'prize_code') {
            Cache::put("user_{$chatId}_action", 'awaiting_prize_code', now()->addHours(1));
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🎉 *كود الجائزة*\n\n"
                         ."يتم الحصول على كود الهدية من الأدمن\n\n"
                         ."أدخل كود الهدية لتحصل على رصيد مجاني 👇",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'keyboard' => [['🚫 إلغاء']],
                    'resize_keyboard' => true
                ])
            ]);
        }
        elseif (str_starts_with($data, 'eshansy_withdraw_approve_')) {
            if (!in_array($chatId, $this->adminIds)) return;
            
            $parts = explode('_', $data);
            $targetUserId = $parts[3];
            $amount = $parts[4];
            
            // خصم المبلغ من رصيد المستخدم
            $user = User::where('telegram_id', $targetUserId)->first();
            
                $user->balance += $amount;
                $user->total_withdrawal_for_account += $amount;
                $user->save();
                $data = [
                        'user_id'=>$user->id,
                        'type'=>'withdraw',
                        'amount'=>$amount,
                        'status'=>'completed'
                ];
                Transaction::create($data);
                // إعلام المستخدم
                $this->telegram->sendMessage([
                    'chat_id' => $targetUserId,
                    'text' => "✅ *تم سحب $amount  من رصيدك بنجاح!*\n\n"
                             ."الرصيد المتبقي: {$user->balance} ",
                    'parse_mode' => 'Markdown'
                ]);
                
                // تأكيد للإدارة
                // $this->telegram->sendMessage([
                //     'chat_id' => $chatId,
                //     'text' => "✅ تم إضافة $amount  لرصيدك في البوت ذو الايدي $targetUserId"
                // ]);
                foreach ($this->adminIds as $adminId) {
                    $this->telegram->sendMessage([
                        'chat_id' => $adminId,
                       'text' => "✅ تم إضافة $amount  لرصيد الحساب في البوت ذو الايدي $targetUserId"
                    ]);
                }
            
        }
        elseif (str_starts_with($data, 'eshansy_withdraw_reject_')) {
            if (!in_array($chatId, $this->adminIds)) return;
            
            $targetUserId = str_replace('eshansy_withdraw_reject_', '', $data);
            
            $this->telegram->sendMessage([
                'chat_id' => $targetUserId,
                'text' => "❌ *تم رفض طلب السحب*\n\n"
                         ."السبب: عدم كفاية الرصيد في الموقع أو بيانات غير صحيحة."
            ]);
            foreach ($this->adminIds as $adminId) {
                $this->telegram->sendMessage([
                    'chat_id' => $adminId,
                      'text' => "❌ *تم رفض طلب السحب*\n\n"
                         ."السبب: عدم كفاية الرصيد في الموقع أو بيانات غير صحيحة."
                ]);
            }
            
        }
        elseif ($data === 'balance') {
            $this->showUserBalance($chatId);
        } elseif ($data === 'deposit') {
            $this->showDepositMethods($chatId);
           
        } elseif (in_array($data, ['سيرتيل كاش - تحويل يدوي', 'ام تي ان كاش','USDT Trc20','USDT Erc20','USDT Bep20'])){
            if($data == 'ام تي ان كاش'){
                // Cache::forget("deposit_amount_{$chatId}");
                // Cache::forget("awaiting_txid");
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ عذرا ام تي ان كاش متوقف حاليا سيعود للعمل في أقرب وقت."
                ]);
                return;
            }
            Cache::put("deposit_method_{$chatId}", $data, now()->addMinutes(10));
            Cache::put("deposit_step_{$chatId}", 'awaiting_amount', now()->addMinutes(10));
             $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "💰 رجاءً أرسل المبلغ الذي ترغب في إيداعه:"
            ]);

        } elseif (str_starts_with($data, '/confirm_withdraw_')) {
            if (!in_array((string)$chatId, $this->adminIds)) return;
        
            $parts = explode('_', $data);
            if (count($parts) === 4) {
                $targetId = $parts[2];
                $amount = floatval($parts[3]);
        
                $user = User::where('telegram_id', $targetId)->first();
        
                if ($user && $user->balance >= $amount) {
                    $user->balance -= $amount;
                    $user->total_withdrawal += $amount;
                    $user->save();
                    $code = $amount * 0.1;
                    $total = $amount - $code;
                    $admin = User::where('telegram_id', '1088015905')->first();
                    $admin->balance += $code;
                    $admin->save();
                    // إرسال للمستخدم
                    $this->telegram->sendMessage([
                        'chat_id' => $targetId,
                        'text' => "✅ تم تنفيذ طلب السحب الخاص بك بنجاح بقيمة $amount 💸 مع خصم 10% عمولة تحويل \n يصبح الاجمالي $total 💸"
                    ]);
        
                    // تأكيد للأدمن
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "✅ تم تأكيد عملية السحب للمستخدم $targetId بنجاح."
                    ]);
                } else {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❌ فشل في تنفيذ السحب. تحقق من رصيد المستخدم أو من صحة البيانات."
                    ]);
                }
            }
        }
        elseif (str_starts_with($data, '/confirm_')) {
            // تحقق إذا كان المستخدم هو الأدمن
            if (!in_array((string)$chatId, $this->adminIds)) return;
    
            $parts = explode('_', $data);
            if (count($parts) === 3) {
                $targetId = $parts[1];  // معرف المستخدم
                $amount = floatval($parts[2]);  // المبلغ
    
                // الحصول على المستخدم
                $user = User::where('telegram_id', $targetId)->first();
                if ($user) {
                    // تحديث الرصيد
                    $user->balance += $amount;
                    $user->total_deposit += $amount;
                    $user->save();
                    if($user->referred_id != null){
                        $referred = User::where('telegram_id',$user->referred_id)->first();
                        if($referred->is_dealer){
                            $profit_rate = 0.15;
                        }else{
                            $profit_rate = 0.03;
                        }
                        $amount_referred = $amount * $profit_rate;
                        // $referred->balance += $amount_referred;
                        $referred->referrals_balance += $amount_referred;
                        $referred->save();
                        $this->telegram->sendMessage([
                            'chat_id' => $referred->telegram_id,
                            'text' => "✅ تم إضافة $amount_referred إلى رصيد الاحالات الخاص بك عن طريق رابط الاحالة .  شكراً!"
                        ]);
                    }
                    // إرسال رسالة للمستخدم
                    $this->telegram->sendMessage([
                        'chat_id' => $targetId,
                        'text' => "✅ تم إضافة $amount إلى رصيدك. شكراً!"
                    ]);
    
                    // إرسال تأكيد للأدمن
                    foreach ($this->adminIds as $adminId) {
                        $this->telegram->sendMessage([
                            'chat_id' => $adminId,
                            'text' => "✅ تم تأكيد الإيداع بنجاح."
                        ]);
                    }
                }
            }
            
            // $user = User::where('telegram_id', $targetUserId)->first();
            // if ($user->referred_by) {
            //     $referralBonus = $amount * 0.03; // 3%
            //     $referrer = User::where('telegram_id', $user->referred_by)->first();
                
            //     $referrer->referral_balance += $referralBonus;
            //     $referrer->save();
                
            //     // إرسال إشعار للمُحيل
            //     $this->telegram->sendMessage([
            //         'chat_id' => $referrer->telegram_id,
            //         'text' => "🎉 حصلت على مكافأة إحالة بقيمة {$referralBonus} $!\n"
            //                  ."من عملية شحن قام بها @{$user->username}"
            //     ]);
            // }
            return;
        } 
        // في handleCallbackQuery
        elseif ($data === 'withdraw') {
            $user = User::where('telegram_id', $chatId)->first();
            
            if (!$user) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ لم يتم العثور على حسابك. أرسل /start لإنشاء حساب جديد."
                ]);
                return;
            }
            
            if ($user->balance <= 0) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ رصيدك الحالي صفر. لا يمكنك السحب."
                ]);
                return;
            }
            $this->showWithdrawMethods($chatId);
        } elseif (str_starts_with($data, 'withdraw_')) {
                    $method = str_replace('withdraw_', '', $data);
                    Cache::put("withdraw_method_{$chatId}", $method, now()->addMinutes(10));
                    // Cache::put("withdraw_step_{$chatId}", 'awaiting_wallet', now()->addMinutes(10));
                
                    // $this->telegram->sendMessage([
                    //     'chat_id' => $chatId,
                    //     'text' => "🏦 أرسل رقم محفظتك أو حسابك على $method:"
                    // ]);
                    
                    
                    Cache::put("withdraw_step_{$chatId}", 'awaiting_amount', now()->addMinutes(10));
                    
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "💸 الرجاء إدخال المبلغ الذي تريد سحبه:"
                    ]);
        }
        elseif (str_starts_with($data, 'cancel_deposit_') ) {
            $targetUserId = str_replace('cancel_deposit_', '', $data);
            $this->cancelDeposit($targetUserId);
        }
        elseif ($data === 'contact_us') {
            $this->showContactOptions($chatId);
        }
        elseif (str_starts_with($data, 'reject_premium_')) {
            $targetUserId = str_replace('reject_premium_', '', $data);
            $this->rejectPremiumPurchase($targetUserId, $chatId);
        }
        elseif (str_starts_with($data, 'complete_stars_')) {
            $parts = explode('_', $data);
            $targetUserId = $parts[2];
            $package = $parts[3];
            $this->completeStarsPurchase($targetUserId, $package, $chatId);
        }
        elseif ($data === 'send_message_to_admin') {
            Cache::put("waiting_for_message_{$chatId}", true, now()->addHours(1));
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📝 الرجاء كتابة رسالتك للإدارة وسيتم الرد في أقرب وقت:",
                'reply_markup' => json_encode(['remove_keyboard' => true])
            ]);
        }
        elseif($data === 'spin_wheel'){
            $chance = DB::table('wheel_chances')->where('telegram_id', $chatId)->first();
        
            if (!$chance || $chance->chances <= 0) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ ليس لديك فرص حالياً. قم بالشحن لتحصل على فرصة!"
                ]);
                return response('OK', 200);
            }
        
            // خصم الفرصة
            DB::table('wheel_chances')->where('telegram_id', $chatId)->decrement('chances');
        
            // إجراء السحب
            $reward = $this->spinWheel();
            if($reward > 0){
                // حفظ الجائزة لتُضاف لاحقاً بعد الشحن
                DB::table('pending_rewards')->insert([
                    'telegram_id' => $chatId,
                    'reward' => $reward,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $user = User::where('telegram_id', $chatId)->first();
                $amount = $reward + $user->balance;
                $data = [
                    'balance' => $amount
                ];
                $user->update($data);
                // إرسال النتيجة
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "🎉 مبروك! ربحت معنا *$reward* ل.س\n.",
                    'parse_mode' => 'Markdown'
                ]);
            }else{
                 $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "😢 حظًا أوفر! جرب مرة أخرى في المرة القادمة 🎡"
                ]);
            }
        }
        elseif (str_starts_with($data, 'reply_to_')) {
            $targetUserId = str_replace('reply_to_', '', $data);
            Cache::put("admin_reply_to_{$chatId}", $targetUserId, now()->addHours(1));
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📝 اكتب ردك للمستخدم:"
            ]);
        }
        elseif($data === 'main_menu'){
             $this->showMainMenu($chatId);
        }
        elseif($data === 'check_vip_level'){
                // $user = User::where('telegram_id', $chatId)->first();
                $user = DB::table('users')->where('telegram_id', $chatId)->first();
                if(!$user){
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "❌ عذراً ليس لديك حساب ."
                    ]);
                    
                    return response('OK', 200);
                }
                $level = $this->getVipLevel($user->xp);
        
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' =>
"🎖️ *نظام المستويات (VIP) في بوت الملوك والإيشانسي*\n
كل ما تعاملت أكتر معنا (شحن، سحب، إنشاء وكالات)، رح تكسب نقاط خبرة *(XP)*، وبزيادة النقاط بترتفع رتبتك تلقائياً!\n
كل مستوى بيعطيك ميزات خاصة، وبيقرّبك من صف النخبة 👑🔥\n
استمر بالتفاعل وراقب تطورك!\n\n\n
🏅 *مستواك الحالي:* $level",
    'parse_mode' => 'Markdown'
]);

        }
        
        elseif($data === 'bonus_info'){
            
                
                    $bonusMessage = "🎉 *نظام المكافآت الحصري!*\n\n"
                        . "▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬\n"
                        . "💎 *احصل على 10% مكافأة عند شحن 500,000*\n\n"
                        . "✨ *كيف تحصل على المكافأة؟*\n"
                        . "1. قم بشحن مبلغ 500,000 أو أكثر\n"
                        . "2. بدون أي عمليات سحب خلال 24 ساعة\n"
                        . "3. يجب أن تكون عمليات الشحن خلال أسبوع على الأكثر \n"
                        . "4. احصل على 10% مكافأة تلقائياً!\n\n"
                        . "💰 *مثال:*\n"
                        . "شحنت 500,000 تصبح 550,000!\n\n"
                        . "📊 *تتبع تقدمك:*\n"
                        . "المطلوب للبونص: 500,000\n"
                        . "🔔 *ملاحظة:* المكافأة تضاف خلال 5 دقائق من استيفاء الشروط";
                
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $bonusMessage,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [['text' => '🔙 رجوع', 'callback_data' => 'main_menu']]
                            ]
                        ])
                    ]);
        }
        elseif ($data === 'referral_stats') {
            $user = User::where('telegram_id', $chatId)->first();
            $referrals = User::where('referred_id', $chatId)->count();
            if($user->is_dealer){
                  $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📊 إحصائيات الإحالة:\n\n"
                         ."👥 عدد المُحالين: {$referrals}\n"
                         ."💰 إجمالي الأرباح: {$user->referrals_balance} $\n"
                         ."💸 نسبة الأرباح: 15% من كل شحن"
            ]);
            }else{
                $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📊 إحصائيات الإحالة:\n\n"
                         ."👥 عدد المُحالين: {$referrals}\n"
                         ."💰 إجمالي الأرباح: {$user->referrals_balance} $\n"
                         ."💸 نسبة الأرباح: 3% من كل شحن"
            ]);  
            }
          
        }
        
        elseif ($data === 'my_referral_link') {
            $referralLink = $this->generateReferralLink($chatId);
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "📌 *رابط الإحالة الخاص بك*:\n\n`{$referralLink}`\n\n"
                         ."يمكنك مشاركته مع الأصدقاء لتحصل على 3% من كل عملية شحن يقومون بها!",
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        // [['text' => '📋 نسخ الرابط', 'callback_data' => 'copy_referral_link']],
                        [['text' => '🔙 رجوع', 'callback_data' => 'main_menu']]
                    ]
                ])
            ]);
        }
        // elseif ($data === 'copy_referral_link') {
        //     $referralLink = $this->generateReferralLink($chatId);
            
        //     $this->telegram->answerCallbackQuery([
        //         'callback_query_id' => $callbackQuery['id'],
        //         'text' => 'تم نسخ الرابط إلى الحافظة!',
        //         'show_alert' => false
        //     ]);
            
        //     // Note: النسخ الفعلي يتطلب واجهة ويب أو تطبيق خارجي
        //     $this->telegram->sendMessage([
        //         'chat_id' => $chatId,
        //         'text' => "يمكنك نسخ الرابط يدوياً:\n\n`{$referralLink}`",
        //         'parse_mode' => 'Markdown'
        //     ]);
        // }
        elseif ($data === 'withdraw_referral') {
            $user = User::where('telegram_id', $chatId)->first();
            
            if ($user->referral_balance < 10) { // حد أدنى للسحب
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ الحد الأدنى لسحب أرباح الإحالة هو 10 $"
                ]);
                return;
            }
            
            $user->balance += $user->referral_balance;
            $user->referral_balance = 0;
            $user->save();
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ تم تحويل {$user->referral_balance} $ إلى رصيدك الرئيسي"
            ]);
        }
        elseif ($data === 'show_game') {
            $user = User::where('telegram_id', $chatId)->first();

            if (!$user) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ لم يتم العثور على حساب مستخدم مرتبط بك."
                ]);
                return;
            }
            $id = $user->id ?? 1;
            $balance = $user->balance ?? 50000;
    
            // Create an inline keyboard with a Web App button
            $keyboard = [
                'inline_keyboard' => [
                    [[
                        'text' => '🎮 الشجرة ',
                        'web_app' => ['url' => route('game.page',['id' => $id, 'balance' => $balance])]
                    ]],
                    [[
                         'text' => '🎲 ألعاب التحدي',
                        'web_app' => ['url' => route('game-2.page',['id' => $id, 'balance' => $balance])]
                    ]]
                ]
            ];
        
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ اضغط على الزر لبدء اللعب 👇",
                'reply_markup' => json_encode($keyboard),
            ]);
        }
        else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "⚠️ خيار الإيداع غير صحيح. حاول مرة أخرى."
            ]);
        }
    }
    public function index(Request $request)
    {
        // $userId = $request->query('id');
        // $user = User::findOrFail($request->id);
        $id = $request->id;
        $balance = $request->balance;
        return view('game.index', compact('id','balance'));
    }
    
    public function game_2(Request $request)
    {
        // $userId = $request->query('id');
        // $user = User::findOrFail(1);
        $id = $request->id;
        $balance = $request->balance;
    
        return view('game.game-2', compact('id','balance'));
    }
    private function showDepositMethods($chatId)
    {
        $methods = [

        ];

        $inlineKeyboard = array_chunk($methods, 2);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "🚀 اختر طريقة الدفع المناسبة لك:",
            'reply_markup' => json_encode(['inline_keyboard' => [
                               [['text' => 'سيرتيل كاش - تحويل يدوي', 'callback_data' => 'سيرتيل كاش - تحويل يدوي']],
                                [['text' => 'ام تي ان كاش', 'callback_data' => 'ام تي ان كاش']],
                                [['text' => 'USDT Trc20', 'callback_data' => 'USDT Trc20']],
                                [['text' => 'USDT Erc20', 'callback_data' => 'USDT Erc20']],
                                [['text' => 'USDT Bep20', 'callback_data' => 'USDT Bep20']]
                ]])
        ]);
    }
    
    private function showWithdrawMethods($chatId)
    {
        $methods = [
            ['text' => 'سيرتيل كاش - تحويل يدوي', 'callback_data' => 'withdraw_سيرتيل كاش - تحويل يدوي'],
            ['text' => 'رصيد سيرتيل ', 'callback_data' => 'withdraw_رصيد سيرتيل '],            
            
        ];
    
        $inlineKeyboard = array_chunk($methods, 2);
    
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "💳 اختر وسيلة السحب:",
            'reply_markup' => json_encode([
                    'inline_keyboard' => [
                    [['text' => 'سيرتيل كاش - تحويل يدوي', 'callback_data' => 'withdraw_سيرتيل كاش - تحويل يدوي']],
                    [['text' => 'ام تي ان كاش', 'callback_data' => 'withdraw_ام تي ان كاش']],
                    [['text' => 'رصيد سيرتيل ', 'callback_data' => 'withdraw_رصيد سيرتيل']],    
                    [['text' => 'رصيد ام تي ان ', 'callback_data' => 'withdraw_mtn رصيد']],    
                    
                    
                ]
            ])
        ]);
    }


    private function handleSubscriptionCheck($chatId, $messageId)
    {
        if ($this->checkUserSubscription($chatId)) {
            User::firstOrCreate(
                ['telegram_id' => $chatId],
            );
            if (!DB::table('wheel_chances')->where('telegram_id', $chatId)->exists()) {
                DB::table('wheel_chances')->insert([
                    'telegram_id' => $chatId,
                    'chances' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $this->telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => "✅ تم التحقق من اشتراكك بنجاح! اضغط /start للبدء",
            ]);
        } else {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $messageId,
                'text' => "❌ لم يتم العثور على اشتراكك، يرجى الاشتراك أولاً",
                'show_alert' => true,
            ]);
        }
    }
    
    private function handleStartCommand($chatId, $text = '')
    {
        \Log::info("Start command triggered by $chatId");
            
            if (str_contains($text, 'ref_')) {
                $processed = $this->processReferral($chatId, $text);
            
                if ($processed) {
                    return;
                }
            }
            
            // 1. التحقق من الاشتراك بالقناة
            if (!$this->checkUserSubscription($chatId)) {
        
                // 1.a إذا دخل برابط إحالة، خزّنها مؤقتًا بالكاش
                if (str_contains($text, 'ref_')) {
                    Cache::put("pending_referral_$chatId", $text, now()->addMinutes(30));
                }
        
                // 1.b اطلب منه الاشتراك
                $this->sendSubscriptionRequest($chatId);
                return;
            }
        
            
    
        // 3. عرض القائمة الرئيسية (مرة واحدة)
        $this->showMainMenu($chatId);
    }

    private function sendSubscriptionRequest($chatId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "📢 للوصول إلى جميع الميزات، يرجى الاشتراك في قناتنا أولاً:",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => 'اشترك في القناة', 'url' => "https://t.me/harraypot"]],
                    [['text' => 'تأكيد الاشتراك', 'callback_data' => 'check_subscription']]
                ]
            ])
        ]);
    }

    private function checkUserSubscription($userId)
    {
        try {
            $response = $this->telegram->getChatMember([
                'chat_id' => $this->requiredChannel,
                'user_id' => $userId,
            ]);

            return in_array($response['status'], ['member', 'administrator', 'creator']);
        } catch (\Exception $e) {
            // Log::error('Subscription check failed:', [
            //     'error' => $e->getMessage(),
            //     'user_id' => $userId
            // ]);
            return false;
        }
    }

    private function showMainMenu($chatId)
    {
        $text = "👋 أهلين $chatId!\n\n🚀 نورتنا ببوت *إيشانسي* الرسمي 💰\n💡 هون بتقدر تشحن رصيدك، تسحب، تهدي رصيد، تكشف مستواك، وتربح جوائز من عجلة الحظ 🎡!\n🔥 خدمات سريعة، احترافية، ودعم متواصل لخدمتك.\n\n👇 اختر الخدمة يلي بدك ياها من القائمة 👇";

        $chances = DB::table('wheel_chances')->where('telegram_id', $chatId)->value('chances') ?? 0;
        
        $wheelText = '🎡 جرب حظك - عجلة الفرصة';
        if ($chances > 0) {
            $wheelText .= " ($chances)";
        }else{
            $wheelText .= " (0)";
        }
        
        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⚡ حساب ايشانسي', 'callback_data' => 'eshansy_menu']
                ],
                [
                    ['text' => '➖ سحب رصيد', 'callback_data' => 'withdraw'],
                    ['text' => '➕ إيداع رصيد', 'callback_data' => 'deposit']
                ],
                [
                    ['text' => '🎁 إهداء رصيد', 'callback_data' => 'gift_balance'],
                    ['text' => '🎁 رصيدي', 'callback_data' => 'balance']
                ],
                
                [
                    ['text' => '🏅 مستواك', 'callback_data' => 'check_vip_level'],
                    ['text' => '🎁 كود الجائزة', 'callback_data' => 'prize_code']
                ],
                [['text' => $wheelText, 'callback_data' => 'spin_wheel']],
                [
                    ['text' => '🎁 نظام بونص جديد ', 'callback_data' => 'bonus_info'], // تمت إضافته هنا
                    ['text' => '🔗 رابط الإحالة', 'callback_data' => 'my_referral_link'],
                    ['text' => '📊 إحصائيات الإحالة', 'callback_data' => 'referral_stats']
                ],
                [
                    ['text' => ' 🔥 🔥كسر الدنيا بصالة الالعاب', 'callback_data' => 'show_game']
                ],
                [
                    ['text' => '📞 تواصل معنا', 'callback_data' => 'send_message_to_admin']
                ],
            ]
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($inlineKeyboard)
        ]);
    }

    private function showUserBalance($chatId)
    {
        $user = User::where('telegram_id', $chatId)->first();

        if ($user) {
            $balance = number_format($user->balance, 2);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "🎁 رصيدك الحالي هو: $balance 💰"
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "⚠️ لم يتم العثور على حسابك، أرسل /start لإنشاء حساب جديد."
            ]);
        }
    }

 
    private function cancelVpnPurchase($chatId)
    {
        Cache::forget("vpn_country_{$chatId}");
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ تم إلغاء طلب VPN بنجاح"
        ]);
    }
    
    


    

    
  
    
    private function cancelDeposit($chatId)
    {
        Cache::forget("deposit_method_{$chatId}");
        Cache::forget("deposit_amount_{$chatId}");
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ تم إلغاء عملية الشراء من الإدارة",
            'reply_markup' => json_encode(['remove_keyboard' => true])
        ]);
        
        foreach ($this->adminIds as $adminId) {
            $this->telegram->sendMessage([
                'chat_id' => $adminId,
                'text' => "تم رفض الطلب!"
            ]);
        }
        return;
    }
    
  
    private function showContactOptions($chatId)
    {
        $options = [
            ['text' => '📩 إرسال رسالة للإدارة', 'callback_data' => 'send_message_to_admin'],
            ['text' => '📞 الدعم الفني', 'callback_data' => 'technical_support'],
            ['text' => '💼 الشكاوى والاقتراحات', 'callback_data' => 'complaints_suggestions'],
            ['text' => '🔙 رجوع', 'callback_data' => 'main_menu']
        ];
    
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "📞 *خيارات التواصل*\n\nاختر طريقة التواصل المناسبة:",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => array_chunk($options, 2)
            ])
        ]);
    }
    
    private function forwardMessageToAdmin($chatId, $message)
    {
        $adminMsg = "📨 رسالة جديدة من  ($chatId):\n\n$message";
        
        foreach ($this->adminIds as $adminId) {
            $this->telegram->sendMessage([
                'chat_id' => $adminId,
                'text' => $adminMsg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '📩 الرد على الرسالة', 'callback_data' => "reply_to_{$chatId}"]]
                    ]
                ])
            ]);
        }
    
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ تم إرسال رسالتك للإدارة بنجاح. سيتم الرد عليك قريباً."
        ]);
    }
    
    private function showEshansyMenu($chatId)
    {
        $text = "✨ *مرحباً بكم في عالم إيشنسي السحري!* ✨\n\n"
              ."⚡️ هنا يمكنك إدارة حسابك بكل سهولة:\n"
              ."• إنشاء حساب جديد خلال ثوانٍ!\n"
              ."• شحن رصيدك بضغطة زر!\n"
              ."• سحب أرباحك عندما تشاء!\n\n"
              ."👇 اختر ما يناسبك من الخيارات التالية:";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📌 إنشاء حساب جديد', 'callback_data' => 'eshansy_create']],
                [['text' => '💰 شحن الرصيد', 'callback_data' => 'eshansy_deposit']],
                [['text' => '💸 سحب الأرباح', 'callback_data' => 'eshansy_withdraw']],
                [['text' => '🔙 العودة للرئيسية', 'callback_data' => 'main_menu']]
            ]
        ];
    
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($keyboard)
        ]);
    }
    private function getVipLevel($xp)
    {
        if ($xp >= 5000) return 'VIP 5';
        if ($xp >= 2500) return 'VIP 4';
        if ($xp >= 1000) return 'VIP 3';
        if ($xp >= 500) return 'VIP 2';
        if ($xp >= 100) return 'VIP 1';
        return 'مبتدئ';
    }
    
     private function spinWheel()
    {
        $rewards = [
            ['amount' => 0, 'weight' => 70],
            ['amount' => 5000, 'weight' => 30],
            ['amount' => 10000, 'weight' => 0],
            ['amount' => 20000, 'weight' => 0],
            ['amount' => 50000, 'weight' => 0],
            ['amount' => 100000, 'weight' => 0], // مؤقتاً احتمال 0
        ];
    
        $totalWeight = array_sum(array_column($rewards, 'weight'));
        $random = rand(1, $totalWeight);
        $cumulative = 0;
    
        foreach ($rewards as $reward) {
            $cumulative += $reward['weight'];
            if ($random <= $cumulative) {
                return $reward['amount'];
            }
        }
    
        return 5000; // fallback
    }
    
    private function rejectDepositRequest($callbackData)
    {
        $parts = explode('_', $callbackData);
        $userId = $parts[3];
        $amount = $parts[4] ?? 'غير معروف';
    
        // إرسال إشعار للمستخدم
        $this->telegram->sendMessage([
            'chat_id' => $userId,
            'text' => "🚫 *تم رفض طلبك*\n\nالمبلغ: $amount\nالسبب: غير مذكور",
            'parse_mode' => 'Markdown'
        ]);
        
        foreach ($this->adminIds as $adminId) {
            $this->telegram->sendMessage([
                'chat_id' => $adminId,
                'text' => "تم رفض الطلب!"
            ]);
        }
        
        // إجابة الكالباك
        // $this->telegram->answerCallbackQuery([
        //     'callback_query_id' => $this->callbackQuery->getId(),
        //     'text' => "تم رفض الطلب!"
        // ]);
        return;
    }
    public function get_user_balance($id){
        $user = User::find($id);
        $allowVars = $this->getAllowToWinVars($user->id);
        return array_merge(['user' => $user], $allowVars);
    }
    
    public function put_user_balance(Request $request){
        $request->validate([
            'id' => 'required',
            'current_balance' => 'required',
            'win_amount' => 'required',
            'bet_amount' => 'required',
            'balance_before' => 'required',
            'game_name' => 'required',
        ]);
        $user = User::find($request->id);
        $user->update(['balance' => $request->current_balance]);

        // Get game_id from games table
        $game = \DB::table('games')->where('name', $request->game_name)->first();
        if ($game) {
            $stats = \DB::table('bets')->where('user_id', $user->id)->where('game_id', $game->id)->first();
            $new_bet = $request->bet_amount;
            $new_win = $request->win_amount;
            $failed_attempts = 0;
            if ($stats) {
                $total_bet = $stats->total_bet_amount + $new_bet;
                $total_win = $stats->total_win_amount + $new_win;
                if ($new_win > 0) {
                    $failed_attempts = 0;
                } else {
                    $failed_attempts = $stats->failed_attempts + 1;
                }
                \DB::table('bets')->where('id', $stats->id)->update([
                    'total_bet_amount' => $total_bet,
                    'total_win_amount' => $total_win,
                    'failed_attempts' => $failed_attempts,
                    'updated_at' => now(),
                ]);
            } else {
                $failed_attempts = $new_win > 0 ? 0 : 1;
                \DB::table('bets')->insert([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'total_bet_amount' => $new_bet,
                    'total_win_amount' => $new_win,
                    'failed_attempts' => $failed_attempts,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $allowVars = $this->getAllowToWinVars($user->id);
        return array_merge(['user' => $user], $allowVars);
    }

/******************رابط الاحالة ********************************************************/
    private function generateReferralLink($chatId)
    {
        $user = User::where('telegram_id', $chatId)->first();
        
        // if (!$user->referral_code) {
        //     $user->referral_code = strtoupper(Str::random(8));
        //     $user->save();
        // }
        
        // إزالة @ من الرابط
        return "https://t.me/".str_replace('@', '', '@HarrayPotter_bot')."?start=ref_".$user->telegram_id;
    }

    private function processReferral($chatId, $text)
    {
        $referrerId = str_replace('ref_', '', $text);
        // $referrerId = preg_replace('/[^0-9]/', '', $text);
        $parts = explode(' ', $referrerId);
        
        // ما تخلي الشخص يحيل نفسه
        if ($parts[1] == $chatId) {
            return false;
        }
    
        // إذا المستخدم موجود مسبقًا، ما نعمل إحالة
        $user = User::where('telegram_id', $chatId)->first();
        if ($user) {
            return false;
        }
    
        // أنشئ المستخدم الجديد وسجّل المحيل
        User::create([
            'telegram_id' => $chatId,
            'referred_id' => $parts[1],
            'referrals_count' => 0,
        ]);
       
       if(!DB::table('wheel_chances')->where('telegram_id', $chatId)->exists()) {
            DB::table('wheel_chances')->insert([
                'telegram_id' => $chatId,
                'chances' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } 
        
    
        // زيادة عدد إحالات المحيل
        $referrer = User::where('telegram_id', $parts[1])->first();
        if ($referrer) {
            $referrer->increment('referrals_count');
            $this->sendMessage($parts[1], "🎉 شخص جديد دخل عن طريقك! إجمالي الإحالات: {$referrer->referrals_count}");
        }
    
        return true;
    }

/******************رابط الاحالة ********************************************************/

private function getAllowToWinVars($userId) {
    // Get all games
    $games = \DB::table('games')->pluck('id', 'name');
    $bets = \DB::table('bets')->where('user_id', $userId)->get();
    $result = [
        'is_allow_to_win_for_tree' => false,
        'is_allow_to_win_for_dice' => false,
        'is_allow_to_win_for_wheel' => false,
    ];
    // For tree and dice: user total_bet_amount > total_win_amount
    foreach (['tree', 'dice'] as $gameName) {
        if (isset($games[$gameName])) {
            $bet = $bets->where('game_id', $games[$gameName])->first();
            if ($bet && $bet->total_bet_amount > $bet->total_win_amount) {
                $result['is_allow_to_win_for_' . $gameName] = true;
            }
        }
    }
    // For wheel: global total_bet_amount - total_win_amount > 200000
    $totals = \DB::table('bets')
        ->selectRaw('SUM(total_bet_amount) as total_bet, SUM(total_win_amount) as total_win')
        ->first();
    if ($totals && ($totals->total_bet - $totals->total_win) > 500000) {
        $result['is_allow_to_win_for_wheel'] = true;
    }
    return $result;
}
}
