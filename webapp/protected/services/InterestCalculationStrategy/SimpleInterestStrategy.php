<?php

/**
 * Interes simple: crece de forma lineal, el interes de un periodo no
 * genera a su vez interes en el siguiente.
 *
 * Formula: (principal * rate * months) / (12 * 100)
 */
class SimpleInterestStrategy implements InterestCalculationStrategyInterface
{
    public function calculate($principal, $rate, $months)
    {
        return ($principal * $rate * $months) / (12 * 100);
    }
}
