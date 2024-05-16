<?php

class Stocks {
    /**
     * Метод возращает остаток согласно коэффициенту товара.
     * Если остаток товара меньше чем коэффициент, то возращает ноль.
     * @param $idProduct - id товара
     * @return int
     */
    public static function getStock($idProduct) {
        // получаем данные по товару
        $arProduct = \Bitrix\Catalog\ProductTable::getList(
            [
                "select" => ["*"],
                "filter" => ["ID" => $idProduct]
            ]
        )->fetch();
        // получаем коэфициент товара
        $arMeasureRatio = \Bitrix\Catalog\ProductTable::getCurrentRatioWithMeasure($idProduct);
        $quantity = floor($arProduct["QUANTITY"] / $arMeasureRatio[$idProduct]["RATIO"]);
        // на случай отрицательного значения
        if ($quantity < 0) {
            $quantity = 0;
        }
        return($quantity);
    }
}
