<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateDealerReferralProfits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dealers:calculate-referral-profits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate referral profits for dealers based on their referred users deposits and withdrawals';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting dealer referral profits calculation...');

        try {
            // Get all dealers (users with is_dealer = 1)
            $dealers = User::where('is_dealer', 1)
            ->where('telegram_id', '!=', 1088015905)
            ->where('telegram_id', '!=', 1046645775)->get();
            
            $this->info("Found {$dealers->count()} dealers to process.");
            
            $processedCount = 0;
            $updatedCount = 0;

            foreach ($dealers as $dealer) {
                $this->line("Processing dealer ID: {$dealer->id} (Telegram ID: {$dealer->telegram_id})");
                
                // Get all users referred by this dealer
                $referredUsers = User::where('referred_id', $dealer->telegram_id)->get();
                
                if ($referredUsers->isEmpty()) {
                    $this->line("  - No referred users found for this dealer.");
                    continue;
                }
                
                $this->line("  - Found {$referredUsers->count()} referred users.");
                
                // Calculate total deposits and withdrawals for all referred users
                $totalDeposits = $referredUsers->sum('total_deposit');
                $totalWithdrawals = $referredUsers->sum('total_withdrawal');
                
                $this->line("  - Total deposits from referred users: {$totalDeposits}");
                $this->line("  - Total withdrawals from referred users: {$totalWithdrawals}");
                
                // Calculate net amount (deposits - withdrawals)
                $netAmount = $totalDeposits - $totalWithdrawals;
                
                // Calculate referral profit (15% of net amount)
                $referralProfit = $netAmount > 0 ? ($netAmount * 0.15) : 0;
                
                $this->line("  - Net amount: {$netAmount}");
                $this->line("  - Calculated referral profit: {$referralProfit}");
                
                // Update dealer's referrals_balance
                $oldBalance = $dealer->referrals_balance;
                $dealer->referrals_balance = $referralProfit;
                $dealer->save();
                
                $this->line("  - Updated referrals_balance from {$oldBalance} to {$referralProfit}");
                
                $processedCount++;
                if ($oldBalance != $referralProfit) {
                    $updatedCount++;
                }
                
                // Log the calculation for audit purposes
                Log::info('Dealer referral profit calculated', [
                    'dealer_id' => $dealer->id,
                    'dealer_telegram_id' => $dealer->telegram_id,
                    'referred_users_count' => $referredUsers->count(),
                    'total_deposits' => $totalDeposits,
                    'total_withdrawals' => $totalWithdrawals,
                    'net_amount' => $netAmount,
                    'referral_profit' => $referralProfit,
                    'old_balance' => $oldBalance,
                    'new_balance' => $referralProfit
                ]);
            }
            
            // Special calculation for admin user (telegram_id = 1088015905)
            $adminUser = User::where('telegram_id', '1088015905')->first();
            if ($adminUser) {
                $this->line("\nProcessing admin user (Telegram ID: 1088015905)");
                
                // Get total deposits and withdrawals from ALL users
                $allUsersTotalDeposits = User::sum('total_deposit');
                $allUsersTotalWithdrawals = User::sum('total_withdrawal');
                $adminNetAmount = $allUsersTotalDeposits - $allUsersTotalWithdrawals;
                
                $this->line("  - All users total deposits: {$allUsersTotalDeposits}");
                $this->line("  - All users total withdrawals: {$allUsersTotalWithdrawals}");
                $this->line("  - Admin net amount: {$adminNetAmount}");
                
                $oldAdminBalance = $adminUser->referrals_balance;
                $adminUser->referrals_balance = $adminNetAmount;
                $adminUser->save();
                
                $this->line("  - Updated admin referrals_balance from {$oldAdminBalance} to {$adminNetAmount}");
                
                Log::info('Admin total profit calculated', [
                    'admin_telegram_id' => '1088015905',
                    'all_users_total_deposits' => $allUsersTotalDeposits,
                    'all_users_total_withdrawals' => $allUsersTotalWithdrawals,
                    'admin_net_amount' => $adminNetAmount,
                    'old_balance' => $oldAdminBalance,
                    'new_balance' => $adminNetAmount
                ]);
            }
            
            // Special calculation for user (telegram_id = 1046645775)
            $specialUser = User::where('telegram_id', '1046645775')->first();
            if ($specialUser) {
                $this->line("\nProcessing special user (Telegram ID: 1046645775)");
                
                // Get total deposits and withdrawals for ichancy accounts from ALL users
                $allUsersTotalDepositsForAccount = User::sum('total_deposit_for_account');
                $allUsersTotalWithdrawalsForAccount = User::sum('total_withdrawal_for_account');
                $specialUserNetAmount = $allUsersTotalDepositsForAccount - $allUsersTotalWithdrawalsForAccount;
                
                $this->line("  - All users total deposits for account: {$allUsersTotalDepositsForAccount}");
                $this->line("  - All users total withdrawals for account: {$allUsersTotalWithdrawalsForAccount}");
                $this->line("  - Special user net amount: {$specialUserNetAmount}");
                
                $oldSpecialUserBalance = $specialUser->referrals_balance;
                $specialUser->referrals_balance = $specialUserNetAmount;
                $specialUser->save();
                
                $this->line("  - Updated special user referrals_balance from {$oldSpecialUserBalance} to {$specialUserNetAmount}");
                
                Log::info('Special user ichancy profit calculated', [
                    'special_user_telegram_id' => '1046645775',
                    'all_users_total_deposits_for_account' => $allUsersTotalDepositsForAccount,
                    'all_users_total_withdrawals_for_account' => $allUsersTotalWithdrawalsForAccount,
                    'special_user_net_amount' => $specialUserNetAmount,
                    'old_balance' => $oldSpecialUserBalance,
                    'new_balance' => $specialUserNetAmount
                ]);
            }
            
            $this->info("Processing completed!");
            $this->info("Total dealers processed: {$processedCount}");
            $this->info("Dealers with updated balances: {$updatedCount}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error occurred: " . $e->getMessage());
            Log::error('Error in dealer referral profit calculation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
} 