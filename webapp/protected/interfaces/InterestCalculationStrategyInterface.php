<?php

/**
 * Strategy Pattern: diferentes algoritmos de calculo de intereses.
 *
 * TransactionService no decide QUE formula de interes aplicar, solo
 * COMO aplicarla: recibe una implementacion concreta (simple,
 * compuesto, o cualquier otra que se anada despues) y la ejecuta a
 * traves de este contrato. Cambiar de algoritmo en runtime es
 * cuestion de inyectar otra implementacion, sin tocar TransactionService.
 */
interface InterestCalculationStrategyInterface
{
    /**
     * @param float $principal capital sobre el que se calcula el interes
     * @param float $rate tasa anual en porcentaje (ej. 5 = 5%)
     * @param int $months plazo en meses
     * @return float interes generado (no incluye el principal)
     */
    public function calculate($principal, $rate, $months);
}
