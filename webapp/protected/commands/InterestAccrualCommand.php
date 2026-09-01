<?php

class InterestAccrualCommand extends CConsoleCommand
{
    public function actionIndex()
    {
        echo "Starting interest accrual process...\n";
        
        $accountRepository = new AccountRepository();
        $accounts = $accountRepository->findAll();

        $transactionService = new TransactionService(
            new TransactionRepository(),
            $accountRepository,
            new SimpleInterestStrategy()
        );

        $interestCount = 0;

        foreach ($accounts as $account) {
            if ($account->status !== Account::STATUS_ACTIVE) {
                continue; // Skip non-active accounts
            }

        //Calcula interes diario
        $strategy = new SimpleInterestStrategy();
        $interestAmount = $strategy->calculate((float)$account->balance, 2.0, 1);

        //Deposita sin estategia visible (no suma en interest_earned del usuario)

        $result = $transactionService->deposit($account->id, $interestAmount, new NoInterestStrategy());

        if ($result !== false) {
            echo "Interest accrued for account {$account->id}: {$interestAmount}\n";
            $interestCount++;
            }
        }
        
        echo "Interest accrual process completed.\n";
        
        }
}
