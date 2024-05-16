<?php
namespace Acrit\Additional\Order;

use Bitrix\Main;
use Bitrix\Sale\Result;
use Bitrix\Sale\ResultWarning;
use Bitrix\Sale\EntityMarker;

class Order {
    /**
     *  Метод модификации заказа с маркетплейсов
     */
    function modifyOrderFromMarketplace(Main\Event $event) {
        // Строка кода "$order->save();" начинает рекурсивно вызывать метод "modifyOrderFromMarketplace" благодаря обработчику
        // события "OnSaleOrderBeforeSaved" поэтому, чтобы событие отработало единожды для одного заказа
        // устанавливается флаг orderNext, который прерывает рекурсию
        if ($_REQUEST['orderNext'] && $_REQUEST['orderNext'] == "Y") {
            $_REQUEST['orderNext'] = "N";
            return true;
        }

        $order = $event->getParameter('ENTITY');

        // получаем данные пользователя, для определения что этот заказ с маркетплейса
        $user = CUser::GetByID($order->getUserId())->Fetch();

        // список логинов маркетплейсов
        $arrLoginMarketplace = ["ozon", "yandex"];
        // если заказ не от маркетплейса, то выходим
        if (!in_array($user['LOGIN'], $arrLoginMarketplace)) return true;

        $propOzon = [];
        $propertyCollection = $order->getPropertyCollection();
        $propertyArray = $propertyCollection->getArray();
        // находим необходимое свойство заказа "MARKETPLACE_OnSaleOrderBeforeSaved",
        // свойство необходимо для отслеживания обработки заказа согласно бизнес-логике маркетплейсов
        foreach ($propertyArray["properties"] as $item) {
            if ($item["CODE"] == "MARKETPLACE_OnSaleOrderBeforeSaved") {
                $propOzon = $item;
                break;
            }
        }

        // обработка заказа с ozon если он пришел в первый раз
        if ($propOzon["VALUE"][0] == "N") {
            // получаем корзину заказа
            $basket = $order->getBasket();

            // собираем id товаров для одного запроса в БД
            $arProductIds = [];
            foreach ($basket->getBasketItems() as $item) {
                $arProductIds[] = $item->getProductId();
            }

            // получаем данные по товарам
            $resProducts = \Bitrix\Catalog\ProductTable::getList(
                [
                    "select" => ["*"],
                    "filter" => ["ID" => $arProductIds]
                ]
            );
            $arProducts = [];
            while ($elem = $resProducts->fetch()) {
                $arProducts[$elem["ID"]] = $elem;
                // получаем данные по цене товара
                $arProducts[$elem["ID"]]["PRICES"] = \Bitrix\Catalog\PriceTable::getList([
                    "select" => ["*"],
                    "filter" => [
                        "=PRODUCT_ID" => $elem["ID"],
                        "=CATALOG_GROUP_ID" => 7,
                    ]
                ])->fetch();
            }

            // получаем данные по коэфициентам товаров
            $arMeasureRatio = \Bitrix\Catalog\ProductTable::getCurrentRatioWithMeasure($arProductIds);

            // модифицируем количество товаров согласно бизнес-логике маркетплейса
            foreach ($basket->getBasketItems() as $item) {
                // устанавливаем цену K4/КрупныйОпт
                $item->setField('PRICE', $arProducts[$item->getProductId()]["PRICES"]["PRICE"]);
                $item->setField('PRICE_TYPE_ID', $arProducts[$item->getProductId()]["PRICES"]["CATALOG_GROUP_ID"]);
                // если не хватает товаров в магазине, то вешаем "проблему" на товар в заказе и обрабатываем следующий товар
                if ($arProducts[$item->getProductId()]["QUANTITY"] < $item->getQuantity() * $arMeasureRatio[$item->getProductId()]["RATIO"]) {
                    $result = new Result();
                    $message = "Для товара (ID: " . $item->getProductId() . ") с OZON не достаточно товаров";
                    $result->addError(new ResultWarning($message, "OZON_NOT_ENOUGH_PRODUCTS"));
                    EntityMarker::addMarker($order, $order, $result);
                    continue;
                }
                $item->setField('QUANTITY', $item->getQuantity() * $arMeasureRatio[$item->getProductId()]["RATIO"]);
            }
            $_REQUEST['orderNext'] = "Y";
            // устанавливаем признак, что заказ с маркетплейса был обработан
            $somePropValue = $propertyCollection->getItemByOrderPropertyId($propOzon["ID"]);
            $somePropValue->setValue("Y");
            $order->save();
            return true;
        }

        // обработка заказа если он уже был обработан по логике маркетплейса, то
        // в этом случае необходимо вернуть некоторые старые значения
        if ($propOzon["VALUE"][0] == "Y" && $order->getId()) {
            $basket = $order->getBasket();
            // объект со старыми данными
            $old_order = Bitrix\Sale\Order::load($order->getId());
            $old_basket = $old_order->getBasket();
            foreach ($basket->getBasketItems() as $item) {
                foreach ($old_basket->getBasketItems() as $item_old) {
                    if ($item_old->getProductId() == $item->getProductId()) {
                        $item->setField('QUANTITY', $item_old->getQuantity());
                    }
                }
            }
            $_REQUEST['orderNext'] = "Y";
            $order->save();
            return true;
        }
    }
}
