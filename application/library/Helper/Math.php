<?php

class Helper_Math
{
    /**
     * 求除法
     * @param  $numerator    分子
     * @param  $denominator  分母
     * @return float
     */
    public static function divide($numerator, $denominator, $decimal = 2)
    {
        $ratio = $denominator ? ($numerator / $denominator) : 0;
        return round($ratio, $decimal);
    }
}