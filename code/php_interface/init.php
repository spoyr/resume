<?php
use Bitrix\Main;
use Bitrix\Main\EventManager;
use Bitrix\Sale\Result;
use Bitrix\Sale\ResultWarning;
use Bitrix\Sale\EntityMarker;

include "acrit/exchanges/ozon/PriceOzon.php";
include "acrit/exchanges/yandex/PriceYandex.php";
include "acrit/exchanges/Stocks.php";
include "acrit/exchanges/Order.php";


/**
 * Обработчик модификации данных заказа с маркетплейсов
 */
Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    "modifyOrderFromMarketplace"
);

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

    if ($_REQUEST['action'] == "delete") {
        return true;
    }

    $order = $event->getParameter('ENTITY');

    // получаем данные пользователя, для определения что этот заказ с маркетплейса
    $user = CUser::GetByID($order->getUserId())->Fetch();
    // список логинов с маркетплейсов
    $arrLoginMarketplace = ["ozon", "yandex"];
    // если заказ не от маркетплейса, то выходим
    if (!in_array($user['LOGIN'], $arrLoginMarketplace)) return true;

    // получаем коллекцию по свойствам у заказа
    $propertyCollection = $order->getPropertyCollection();
    // состав заказа по товарам
    $compositionOrder = $propertyCollection->getItemByOrderPropertyCode("COMPOSITION_ORDER");
    $onSaleOrderBeforeSaved = $propertyCollection->getItemByOrderPropertyCode("MARKETPLACE_OnSaleOrderBeforeSaved");

    // Пример строки:
    // Карабин винтовой (соединитель цепей) оцинк. 12 мм Код товара для поиска: К04352 Количество: 1 Цена: 723.0000 RUB
    $strProductListFromOzon = $propertyCollection->getItemByOrderPropertyCode("MARKETPLACE_PRODUCT_LIST")->getValue();
    // Массив данных по товарам в заказе с озона
    $arrProductListFromOzon = [];
    $arr = explode("RUB", $strProductListFromOzon);
    array_pop($arr);
    foreach ($arr as $item) {
        $mask = "/[ ][a-zа-я0-9]*Количество/ui";
        $matches = [];
        preg_match($mask, str_replace(["\n", "\r"], "", $item), $matches);
        $article = trim(str_replace("Количество", "", $matches[0]));

        $mask = "/[ ][a-zа-я0-9]*Цена/ui";
        $matches = [];
        preg_match($mask, str_replace(["\n", "\r"], "", $item), $matches);
        $quantity = trim(str_replace("Цена", "", $matches[0]));

        $arrProductListFromOzon[$article] = [
            "ARTICLE" => $article,
            "QUANTITY" => $quantity
        ];
    }

    // обработка заказа с ozon если он пришел в первый раз
    if ($onSaleOrderBeforeSaved->getValue() == "N") {
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

            $arFilter = ['ID' => $elem["ID"], "ACTIVE"=>"Y", 'IBLOCK_ID' =>  19];
            $arSelect = ['PROPERTY_ADD_AVAILABLE_QUANTITY', 'PROPERTY_CML2_ARTICLE'];
            $arProducts[$elem["ID"]]["PROPERTY"] = CIBlockElement::GetList(
                [], $arFilter, false, false, $arSelect)->fetch();
            $arProducts[$elem["ID"]]["DATA_PRODUCT_FROM_OZONE"] =
                $arrProductListFromOzon[$arProducts[$elem["ID"]]["PROPERTY"]["PROPERTY_CML2_ARTICLE_VALUE"]];
        }

        // получаем данные по коэфициентам товаров
        $arMeasureRatio = \Bitrix\Catalog\ProductTable::getCurrentRatioWithMeasure($arProductIds);

        // состав заказа по товарам в виде строки
        $str = "";
        // модифицируем количество товаров согласно бизнес-логике маркетплейса
        foreach ($basket->getBasketItems() as $item) {
            // устанавливаем цену K4/КрупныйОпт
            $item->setField('PRICE', $arProducts[$item->getProductId()]["PRICES"]["PRICE"]);
            $item->setField('PRICE_TYPE_ID', $arProducts[$item->getProductId()]["PRICES"]["CATALOG_GROUP_ID"]);

            /*$str .= "[Название товара: ".$item->getField("NAME")
                ."; Актикул: ".$arProducts[$item->getProductId()]["DATA_PRODUCT_FROM_OZONE"]["ARTICLE"]
                ."; Количество: ".$arProducts[$item->getProductId()]["DATA_PRODUCT_FROM_OZONE"]["QUANTITY"]."x".$arMeasureRatio[$item->getProductId()]["RATIO"]."];".PHP_EOL;*/

            $str .= "[".$item->getField("NAME")
                ."; ".$arProducts[$item->getProductId()]["DATA_PRODUCT_FROM_OZONE"]["QUANTITY"]."x".$arMeasureRatio[$item->getProductId()]["RATIO"]."];".PHP_EOL;


            if ($arProducts[$item->getProductId()]["PROPERTY"]["PROPERTY_ADD_AVAILABLE_QUANTITY_VALUE"] == "Y"
                && $arProducts[$item->getProductId()]["QUANTITY"] == 0) {

                $result = new Result();
                $message = "Добавлен остаток к товару (ID: " . $item->getProductId() . ")";
                $result->addWarning(new ResultWarning($message, "OZON_NOT_ENOUGH_PRODUCTS11"));
                EntityMarker::addMarker($order, $order, $result);

                Bitrix\Catalog\Model\Product::update($item->getProductId(), ['QUANTITY' => $arProducts[$item->getProductId()]["DATA_PRODUCT_FROM_OZONE"]["QUANTITY"]]);
                $item->setField('QUANTITY', $arProducts[$item->getProductId()]["DATA_PRODUCT_FROM_OZONE"]["QUANTITY"]);
                continue;
            }

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
        $compositionOrder->setValue($str);
        $onSaleOrderBeforeSaved->setValue("Y");
        $order->save();
        return true;
    }

    // обработка заказа если он уже был обработан по логике маркетплейса, то
    // в этом случае необходимо вернуть некоторые старые значения
    if ($onSaleOrderBeforeSaved->getValue() == "Y" && $order->getId()) {
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

/**
 * Метод для выполнения логирования
 * @param $input_text - сообщение для лога
 * @param false $separator - разделитель в виде пустой строки
 * @param string $name_file - имя файла для сохранения
 */
function logFile($input_text, $separator = false, $name_file = 'log.txt')
{
    $param = [
        "one_log" => 1, // запись в один лог файл
        "name_one_log" => $name_file, // наименование одиночного лога файла
        "name" => $name_file . '_' . date('Y_m_d H_i_s') . ".txt", // наименование лог файлов
        "clear_file_log" => 0, //разрешение на удаление всех предыдущих файлов логов
        "file_path" => $_SERVER['DOCUMENT_ROOT'] . "/local/log/", // путь к папке с логами
    ];

    if ($param['one_log'])
    {
        $name_log = $param['name_one_log'];
        $file = $param["file_path"] . $name_log;
    }
    else
    {
        $name_log = $param['name'];
        $file = $param["file_path"] . $name_log;
    }

    if ($param["clear_file_log"])
    {
        if (file_exists($param["file_path"] . $name_file))
        {
            unlink($param["file_path"] . $name_file);
        }
    }

    if (is_array($input_text))
    {
        $text = var_export($input_text, true) . "\r\n";
    }
    else
    {
        $text = $input_text . "\r\n";
    }


    if ($separator)
    {
        $text .= "\r\n";
    }

    $fOpen = fopen($file, 'a');
    fwrite($fOpen, $text);
    fclose($fOpen);
}
