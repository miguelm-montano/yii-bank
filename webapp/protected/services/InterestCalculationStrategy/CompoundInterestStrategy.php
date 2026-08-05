<?php

/**
 * Interes compuesto: capitaliza mes a mes, el interes de un periodo
 * pasa a generar interes en el siguiente.
 *
 * Formula: principal * (1 + rate/100/12)^months - principal
 */
class CompoundInterestStrategy implements InterestCalculationStrategyInterface
{
    public function calculate($principal, $rate, $months)
    {
        return $principal * pow(1 + $rate / 100 / 12, $months) - $principal;
    }
}
