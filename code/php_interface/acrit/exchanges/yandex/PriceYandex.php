<?php

/**
 * КЛАСС ЯВЛЯЕТСЯ КОПИЕЙ КЛАССА PriceOzon
 * Class PriceYandex
 */
class PriceYandex {
    /**
     * Метод возращает цену товаря для Yandex
     * @param $idProduct
     * @param string $typePriceYandex
     * @param int $catalogGroupId
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPrice($idProduct, $typePriceYandex = "baseprice.value", $catalogGroupId = 6) {
        // максимальный процент последней мили
        $lastMile = 5.5;
        // максимальный процент эквайринга
        $acquiring = 1.5;
        // процент вознаграждения yandex
        $awardYandex = 18;
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
        // коэффициент наценки
        if ($typePriceYandex == "baseprice.value") { // цена со скидкой
            $priceWithCoefficient *= 1;
        } elseif ($typePriceYandex == "baseprice.discountBase") { // цена до скидки
            $priceWithCoefficient *= 1.4;
        }
        $priceYandex = ceil(
            $priceWithCoefficient
            + $sending // отправка
            + $priceLogistic * 2 // логистика
            + ($priceWithCoefficient * $awardYandex/100) // вознаграждение озона
            + ($priceWithCoefficient * $acquiring/100) // эквайринг
            + ($priceWithCoefficient * $lastMile/100)
        );

        if ($typePriceYandex == "baseprice.discountBase") {
            $priceYandexDefaultValue = ceil(
                $priceWithCoefficient / 1.4
                + $sending // отправка
                + $priceLogistic * 2 // логистика
                + ($priceWithCoefficient * $awardYandex/100) // вознаграждение озона
                + ($priceWithCoefficient * $acquiring/100) // эквайринг
                + ($priceWithCoefficient * $lastMile/100)
            );
            // высчитываем текущий процент скидки
            $percentDiscount = ($priceYandex - $priceYandexDefaultValue) * 100 / $priceYandex;
            // корректируем цену, чтобы у цены до скидки и после была разница не менее 10%
            // это требование ЯндексМаркета
            if ($percentDiscount < 10) {
                $priceYandex = ceil($priceYandexDefaultValue * 110 / 100);
            }
        }

        // затраты на рекламу
        $priceMarketing = ceil($priceYandex*$percentMarketing/100);

        // итоговая цена
        return $priceYandex // цена яндекса
            + $priceBuild // цена сборки
            + $priceMarketing; // процент на рекламу
    }
}
