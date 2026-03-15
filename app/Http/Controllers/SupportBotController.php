<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class SupportBotController extends Controller
{
    protected $telegram;
    protected $adminIds = ['7188961884','1088015905'];

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_SUPPORT_BOT_TOKEN'));
    }

    public function handleSupport(Request $request)
    {
        try {
            $update = $this->telegram->getWebhookUpdate();
            // Log::info('Webhook Update:', ['update' => $update]);

            // معالجة callback_query
            if ($update->isType('callback_query')) {
                $callback = $update->getCallbackQuery();
                $data = $callback->getData();
                $adminChatId = $callback->getMessage()->getChat()->getId();
                $messageId = $callback->getMessage()->getMessageId();

                if (str_starts_with($data, 'reply_to_')) {
                    $targetUserId = str_replace('reply_to_', '', $data);
                    
                    // إرسال رسالة توجيهية للأدمن
                    $this->telegram->sendMessage([
                        'chat_id' => $adminChatId,
                        'text' => "📝 أكتب رسالتك للرد على المستخدم (ID: $targetUserId):",
                        'reply_to_message_id' => $messageId
                    ]);

                    // إجابة على callback query
                    $this->telegram->answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => 'جاهز لاستقبال ردك'
                    ]);

                    return response('OK', 200);
                }
            }

            // معالجة الرسائل العادية
            if ($update->isType('message')) {
                $message = $update->getMessage();
                $chatId = $message->getChat()->getId();
                $text = $message->getText() ?? '';
                $firstName = $message->getFrom()->getFirstName() ?? 'مستخدم';

                // إذا كان المرسل أدمن
                if (in_array($chatId, $this->adminIds)) {
                    // معالجة الرد على رسالة
                    if ($message->getReplyToMessage() !== null) {
                        $repliedMsg = $message->getReplyToMessage();
                        $repliedText = $repliedMsg->getText();
                        
                        if (preg_match('/\(ID: (\d+)\)/', $repliedText, $matches)) {
                            $targetUserId = $matches[1];
                            $this->sendReplyToUser($targetUserId, $text, $chatId);
                        }
                    }
                } 
                // إذا كان المرسل مستخدم عادي
                else {
                    foreach ($this->adminIds as $adminId) {
                        $this->telegram->sendMessage([
                            'chat_id' => $adminId,
                            'text' => "📩 رسالة جديدة من $firstName (ID: $chatId):\n\n$text",
                            'reply_markup' => json_encode([
                                'inline_keyboard' => [
                                    [
                                        [
                                            'text' => "🔵 الرد على $firstName",
                                            'callback_data' => "reply_to_$chatId"
                                        ]
                                    ]
                                ]
                            ])
                        ]);
                    }
                }
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            // Log::error('Bot Error:', ['error' => $e->getMessage()]);
            return response('Error', 500);
        }
    }

    protected function sendReplyToUser($userId, $message, $adminChatId)
    {
        try {
            $this->telegram->sendMessage([
                'chat_id' => $userId,
                'text' => "📬 رسالة من الدعم الفني:\n\n$message"
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $adminChatId,
                'text' => "✅ تم إرسال ردك إلى المستخدم (ID: $userId)"
            ]);
        } catch (\Exception $e) {
            $this->telegram->sendMessage([
                'chat_id' => $adminChatId,
                'text' => "❌ فشل في إرسال الرد: " . $e->getMessage()
            ]);
        }
    }
}