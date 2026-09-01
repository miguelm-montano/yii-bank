<?php

class CheckingWithdrawalStrategy implements WithdrawalStrategyInterface
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

        return (float) $account->balance >= (float) $amount; // Check if the account has sufficient funds
    }
}