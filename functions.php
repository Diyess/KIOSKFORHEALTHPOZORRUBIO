<?php
function isAbnormal($bp, $spo2, $temp) {

    // SPO2 check
    if ($spo2 < 95) {
        return true;
    }

    // Temperature check
    if ($temp < 36.1 || $temp > 37.2) {
        return true;
    }

    // BP check (simple logic)
    if (strpos($bp, '/') !== false) {
        list($sys, $dia) = explode('/', $bp);

        if ($sys > 120 || $dia > 80 || $sys < 90 || $dia < 60) {
            return true;
        }
    }

    return false;
}
?>