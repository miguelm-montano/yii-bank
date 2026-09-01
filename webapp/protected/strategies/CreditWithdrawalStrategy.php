<?php

class CreditwithdrawalStrategy implements WithdrawalStrategyInterface
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

         // Credit accounts: validate against credit line (no column yet, so only check amount > 0)
        // Future: add credit_limit column and validate against it
        return true;
    }
}