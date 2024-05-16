<?php

/**
 * Class PriceOzon
 */
class PriceOzon {
    /**
     * Метод возращает цену товаря для Ozon
     * @param $idProduct
     * @param string $typePriceOzon
     * @param int $catalogGroupId
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPrice($idProduct, $typePriceOzon = "price", $catalogGroupId = 6) {
        // максимальный процент последней мили
        $lastMile = 5.5;
        // максимальный процент эквайринга
        $acquiring = 1.5;
        // процент вознаграждения озона
        $awardOzon = 18;
        // максимальная стоймость одной отправки товара
        $sending = 40;
        // цена логистика до 5 литров
        $logistics = 80;
        // наценка за каждый дополнительный литр
        $priceAdditionalLiter = 9;
        // стоймость сборки и упаковки
        $priceBuild = 70;
        // процент на рекламу
        $percentMarketing = 23;

        // получаем данные по товару
        $arProduct = \Bitrix\Catalog\ProductTable::getList(
            [
                "select" => ["*"],
                "filter" => ["ID" => $idProduct]
            ]
        )->fetch();
        // вычисляем объем товара
        $volume = $arProduct["WIDTH"] * $arProduct["LENGTH"] * $arProduct["HEIGHT"] / 1000000;
        // рассчитываем стоймость логистики
        $priceLogistic = $logistics;
        if ($volume > 5) {
            $priceLogistic += ($volume - 5) * $priceAdditionalLiter;
        }
        // получаем данные по цене товара
        $arPrice = \Bitrix\Catalog\PriceTable::getList([
            "select" => ["*"],
            "filter" => [
                "=PRODUCT_ID" => $idProduct,
                "=CATALOG_GROUP_ID" => $catalogGroupId,
            ]
        ])->fetch();
        // получаем данные по коэфициэнту товара
        $arCoefficient = \Bitrix\Catalog\MeasureRatioTable::getCurrentRatio($idProduct);
        // цена с учетом коэфициента
        $priceWithCoefficient = $arPrice["PRICE"] * $arCoefficient[$idProduct];
        //
        if ($typePriceOzon == "price") {
            $priceWithCoefficient *= 1;
        } elseif ($typePriceOzon == "old_price") {
            $priceWithCoefficient *= 1.4;
        } elseif ($typePriceOzon == "min_price") {
            $priceWithCoefficient *= 0.9;
        } elseif ($typePriceOzon == "premium_price") {
            $priceWithCoefficient *= 0.85;
        }
        $priceOzon = ceil(
            $priceWithCoefficient
            + $sending // отправка
            + $priceLogistic * 2 // логистика
            + ($priceWithCoefficient * $awardOzon/100) // вознаграждение озона
            + ($priceWithCoefficient * $acquiring/100) // эквайринг
            + ($priceWithCoefficient * $lastMile/100)
        );
        // итоговая цена
        return $priceOzon + $priceBuild + ceil($priceOzon*$percentMarketing/100);
    }
}
