<?php

interface WithdrawalStrategyInterface
{
    /**
     * Determine if a withdrawal is allowed for the given account and amount
     * @param Account $account
     * @param float $amount
     * @return bool
     */
    public function canWithdraw(Account $account, $amount);
}
