<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\User;
use Telegram\Bot\Objects\Update;
use App\Models\Transaction;

class TelegramController extends Controller
{
    protected $telegram;
    protected $channelUsername = '@ChanceUpRobert'; // ضع هنا معرف قناتك

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    // ============= الدالة الرئيسية لمعالجة الويب هوك =============
    public function handleWebhook(Request $request)
    {
        $update = Telegram::getWebhookUpdate(); // تعديل هنا
    
        if ($update->isType('message')) {
            $message = $update->getMessage();
            $userId = $message->getFrom()->getId();
            $chatId = $message->getChat()->getId();
        } elseif ($update->isType('callback_query')) {
            $callbackQuery = $update->getCallbackQuery();
            $userId = $callbackQuery->getFrom()->getId();
            $chatId = $callbackQuery->getMessage()->getChat()->getId();
        }
    
        if ($this->isUserSubscribed($update) === false) {
            return $this->askForSubscription($update);
        }
    
        if ($update->isType('callback_query')) {
            $this->handleCallbackQuery($update);
        } else {
            $this->handleMessage($update);
        }
    
        return response()->json(['status' => 'success']);
    }


    // ============= الدوال المساعدة =============

    // التحقق من اشتراك المستخدم في القناة
    private function isUserSubscribed($update)
    {
        $update = Telegram::getWebhookUpdate();
        $userId = $update->getMessage()->getFrom()->getId();
        $response = $this->telegram->getChatMember([
            'chat_id' => $this->channelUsername,
            'user_id' => $userId,
        ]);

        return in_array($response->getStatus(), ['member', 'administrator', 'creator']);
    }

    // طلب الاشتراك في القناة
    private function askForSubscription($update)
    {
        $update = Telegram::getWebhookUpdate();
        $userId = $update->getMessage()->getFrom()->getId();
        $this->telegram->sendMessage([
            'chat_id' => $userId,
            'text' => '⚠️ يجب عليك الاشتراك في قناتنا أولاً: ' . $this->channelUsername,
        ]);
    }

    // معالجة ضغطات الأزرار (Inline Keyboard)
    private function handleCallbackQuery($update)
    {
        $callbackData = $update->getCallbackQuery()->getData();
        $userId = $update->getCallbackQuery()->getFrom()->getId();

        switch ($callbackData) {
            case 'eshansy_account':
                $this->showEshansyMenu($userId);
                break;
            case 'create_account':
                $this->createEshansyAccount($userId);
                break;
            case 'deposit':
                $this->askForDepositAmount($userId);
                break;
            case 'withdraw':
                $this->askForWithdrawAmount($userId);
                break;
            case 'back':
                $this->showMainMenu($userId);
                break;
            // ... أضف باقي الخيارات هنا
        }
    }

    // معالجة الرسائل النصية
    private function handleMessage($update)
    {
        $message = $update->getMessage();
        $userId = $message->getFrom()->getId();
        $text = $message->getText();

        if ($text == '/start') {
            $this->showMainMenu($userId);
        } elseif (is_numeric($text)) {
            // إذا كان المستخدم أدخل رقمًا (مثل مبلغ الشحن)
            $this->handleNumericInput($userId, $text);
        }
    }

    // ============= واجهات المستخدم (Keyboards) =============

    // القائمة الرئيسية
    private function showMainMenu($userId)
    {
        $keyboard = [
            [
                ['text' => 'حساب إيشنسي', 'callback_data' => 'eshansy_account'],
                ['text' => 'شحن رصيد', 'callback_data' => 'deposit'],
            ],
            [
                ['text' => 'سحب رصيد', 'callback_data' => 'withdraw'],
                ['text' => 'كود جائزة', 'callback_data' => 'promo_code'],
            ],
            [
                ['text' => 'إهداء صديق', 'callback_data' => 'gift'],
                ['text' => 'الإحالات', 'callback_data' => 'referrals'],
            ],
            [
                ['text' => 'رسالة للدعم', 'callback_data' => 'support'],
                ['text' => 'شروط الاستخدام', 'callback_data' => 'terms'],
            ],
        ];

        $this->sendMenu($userId, 'اختر من القائمة:', $keyboard);
    }

    // قائمة حساب إيشنسي
    private function showEshansyMenu($userId)
    {
        $keyboard = [
            [
                ['text' => 'إنشاء حساب', 'callback_data' => 'create_account'],
                ['text' => 'شحن رصيد', 'callback_data' => 'deposit'],
            ],
            [
                ['text' => 'سحب رصيد', 'callback_data' => 'withdraw'],
                ['text' => 'رجوع', 'callback_data' => 'back'],
            ],
        ];

        $this->sendMenu($userId, 'إدارة حساب إيشنسي:', $keyboard);
    }

    // إرسال قائمة بالأزرار
    private function sendMenu($userId, $text, $keyboard)
    {
        $this->telegram->sendMessage([
            'chat_id' => $userId,
            'text' => $text,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
            ]),
        ]);
    }

    // ============= دوال العمليات =============

    // إنشاء حساب إيشنسي
    private function createEshansyAccount($userId)
    {
        $user = User::firstOrCreate(['telegram_id' => $userId]);
        $user->eshansy_account_id = 'ESH' . rand(1000, 9999);
        $user->save();

        $this->telegram->sendMessage([
            'chat_id' => $userId,
            'text' => '✅ تم إنشاء حسابك بنجاح! رقم حسابك: ' . $user->eshansy_account_id,
        ]);
    }

    // طلب مبلغ الشحن
    private function askForDepositAmount($userId)
    {
        $this->telegram->sendMessage([
            'chat_id' => $userId,
            'text' => '💳 أدخل مبلغ الشحن (بالدولار):',
        ]);
    }

    // معالجة المبلغ المدخل
    private function handleNumericInput($userId, $amount)
    {
        // هنا يمكنك إضافة عملية الشحن أو السحب حسب السياق
        $this->telegram->sendMessage([
            'chat_id' => $userId,
            'text' => '🔃 جاري معالجة طلبك للمبلغ: ' . $amount . '$',
        ]);
    }
}