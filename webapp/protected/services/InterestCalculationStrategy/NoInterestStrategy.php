<?php

/**
 * Estrategia neutra: no genera interes. Util cuando el interes ya se
 * calculo fuera de deposit() y se pasa como $amount, para que
 * deposit() no lo vuelva a sumar por su cuenta.
 */
class NoInterestStrategy implements InterestCalculationStrategyInterface
{
    public function calculate($principal, $rate, $months)
    {
        return 0;
    }
}
