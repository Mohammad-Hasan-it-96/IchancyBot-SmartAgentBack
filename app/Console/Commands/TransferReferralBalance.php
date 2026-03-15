<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransferReferralBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:transfer-to-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer referral_balance to balance and reset referral_balance for all users except admin and special users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting referral balance transfer...');

        try {
            // Get all users except admin and special users
            $users = User::where('telegram_id', '!=', '1088015905')
                        ->where('telegram_id', '!=', '1046645775')
                        ->get();
            
            $this->info("Found {$users->count()} users to process.");
            
            $processedCount = 0;
            $transferredCount = 0;
            $totalTransferred = 0;

            foreach ($users as $user) {
                $this->line("Processing user ID: {$user->id} (Telegram ID: {$user->telegram_id})");
                
                $oldBalance = $user->balance;
                $oldReferralBalance = $user->referrals_balance;
                
                if ($oldReferralBalance > 0) {
                    // Transfer referral_balance to balance
                    $user->balance += $oldReferralBalance;
                    $user->referrals_balance = 0;
                    $user->save();
                    
                    $this->line("  - Transferred {$oldReferralBalance} from referrals_balance to balance");
                    $this->line("  - New balance: {$user->balance}");
                    $this->line("  - Referrals balance reset to: 0");
                    
                    $transferredCount++;
                    $totalTransferred += $oldReferralBalance;
                    
                    // Log the transfer
                    Log::info('Referral balance transferred', [
                        'user_id' => $user->id,
                        'telegram_id' => $user->telegram_id,
                        'old_balance' => $oldBalance,
                        'old_referral_balance' => $oldReferralBalance,
                        'new_balance' => $user->balance,
                        'new_referral_balance' => 0
                    ]);
                } else {
                    $this->line("  - No referral balance to transfer (current: {$oldReferralBalance})");
                }
                
                $processedCount++;
            }
            
            $this->info("Transfer completed!");
            $this->info("Total users processed: {$processedCount}");
            $this->info("Users with transferred amounts: {$transferredCount}");
            $this->info("Total amount transferred: {$totalTransferred}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error occurred: " . $e->getMessage());
            Log::error('Error in referral balance transfer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
} 