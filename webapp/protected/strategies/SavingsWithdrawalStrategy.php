<?php

class SavingsWithdrawalStrategy implements WithdrawalStrategyInterface
{
    /**
     * Determine if a withdrawal is allowed for the given account and amount
     * @param Account $account
     * @param float $amount
     * @return bool
     */
    public function canWithdraw(Account $account, $amount)
    {
        if ($amount <= 0) {
            return false; // Cannot withdraw a non-positive amount
        }

        if ((float) $account->balance < (float) $amount) {
            return false; // Insufficient funds
        }

        if ((int) $account->withdrawal_count >= 3) {
            return false; // Exceeded the maximum number of withdrawals for a savings account
        }

        return true; // All conditions met for withdrawal
    }
}