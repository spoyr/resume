<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use IT\Controllers\User;
use IT\Controllers\OrderStatuses;
use IT\Controllers\WorkArea;
use IT\Controllers\DeliveryPlaces as CDeliveryPlaces;
use IT\Baskets;
use IT\PlanMenuProducts;
use IT\DeliveryPlaces;

// подключаем ленговые файлы
Loc::loadMessages(__FILE__);

// подключаем класс компонента
require_once('class.php');

/**
 * Класс обработчик ajax запросов
 * Class AjaxController
 */
class AjaxController extends Controller
{
    /**
     * обязательный метод интерфейса Controllerable, для работы ajax обработчика
     * возвращает массив где ключ - название метода-обработчика без суффикса Action
     * в пре- фильтре передаем объекты фильтров,
     * пользователь должен быть авторизован - Authentication(),
     * метод передачи данныз POST - HttpMethod::METHOD_POST
     * защита от csrf атаки - Csrf(), в запросе должно быть передано значение sessid
     * ключ - пре- и пост- фильтры для обработки запроса
     * @return array|array[][]
     */
    public function configureActions()
    {
        return [
            'increaseQuantityGoods' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'reduceQuantityGoods' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'removeProduct' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'removeBasket' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'updateComment' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'updateDeliveryPlace' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],

            ],
            'updateDeliveryPeriod' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'updateSpecialFood' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'additionPayment' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'cashlessPayment' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'cashPayment' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
            'makeOrder' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(
                        array(ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
        ];
    }

    /**
     * Метод для изменения количества товара на 1 в большую сторону
     * @param array $post - данные post запроса "idBasket":id корзины, "id":id товара в корзине
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function increaseQuantityGoodsAction($post)
    {
        // результирующий массив
        $data = [];

        // проверяем получение всех данных
        if (!$post['idBasket'] || !$post['id'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }

        // получаем и проверяем наличие данных пользователя
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }

        // получаем и проверяем наличие статусов заказов
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        // получаем и корзину и проверяем ее принадлежность пользователю
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }

        // получаем товары корзины
        $productsInBasket = Baskets::getProductsByBaskets($post['idBasket']);
        // проверяем наличие товара в корзине
        $productIsInBasket = false;
        // массив для сохранения найденного товара
        $product = [];
        foreach ($productsInBasket as $keyProduct => $itemProduct)
        {
            if ($itemProduct['ID'] == $post['id'])
            {
                $productIsInBasket = true;
                $product = $itemProduct;
                break;
            }
        }

        // проверяем наличие товара, по которуму идет запрос на увеличение количества
        if (!$productIsInBasket)
        {
            $data['error'] = Loc::getMessage('NO_PRODUCT');
            return $data;
        }

        // увеличиваем количество товара на единицу
        if (!CIBlockElement::SetPropertyValueCode($post['id'], "KOLICHESTVO", $product['COUNT'] + 1))
        {
            $data['error'] = Loc::getMessage('ERROR_UPDATE');
            return $data;
        }

        // получаем актуальные данные по корзине после манипуляций над ней
        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }

        return $data;
    }

    /**
     * Метод для изменения количества товара на 1 в меньшую сторону
     * @param array $post - данные post запроса "idBasket":id корзины, "id":id товара в корзине
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function reduceQuantityGoodsAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'] || !$post['id'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }
        // получаем товары корзины
        $productsInBasket = Baskets::getProductsByBaskets($post['idBasket']);
        // проверяем наличие товара в корзине
        $productIsInBasket = false;
        // массив для сохранения найденного товара
        $product = [];
        foreach ($productsInBasket as $keyProduct => $itemProduct)
        {
            if ($itemProduct['ID'] == $post['id'])
            {
                $productIsInBasket = true;
                $product = $itemProduct;
                break;
            }
        }
        if (!$productIsInBasket)
        {
            $data['error'] = Loc::getMessage('NO_PRODUCT');
            return $data;
        }
        if ($product['COUNT'] <= 1)
        {
            $data['error'] = Loc::getMessage('MIN_COUNT');
            return $data;
        }
        if (!CIBlockElement::SetPropertyValueCode($post['id'], "KOLICHESTVO", $product['COUNT'] - 1))
        {
            $data['error'] = Loc::getMessage('ERROR_UPDATE');
            return $data;
        }
        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }
        return $data;
    }

    /**
     * Метод для удаления товара в корзине
     * @param array $post - данные post запроса "idBasket":id корзины, "id":id товара в корзине
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function removeProductAction($post)
    {
        $data = [];

        // проверяем получение всех данных
        if (!$post['idBasket'] || !$post['id'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }

        // получаем данные пользователя
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }

        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        // получаем обрабатываемую корзину
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }

        // получаем товары корзины
        $productsInBasket = Baskets::getProductsByBaskets($post['idBasket']);
        if (!$productsInBasket)
        {
            $data['error'] = Loc::getMessage('NO_PRODUCTS');
            return $data;
        }
        $productIsInBasket = false;
        // переменная для сохранения найденного товара
        $product = [];
        foreach ($productsInBasket as $keyProduct => $itemProduct)
        {
            if ($itemProduct['ID'] == $post['id'])
            {
                $productIsInBasket = true;
                $product = $itemProduct;
                break;
            }
        }
        if (!$productIsInBasket)
        {
            $data['error'] = Loc::getMessage('PRODUCT_NO_BELONG_BASKET');
            return $data;
        }

        // если комплект, то удаляем товары комплекта
        if ($product['IS_COMPLECT'])
        {
            $productLunch = Baskets::getLaunchProductsByBasket($post['id']);
            foreach ($productLunch[$post['id']] as $itemProductLunch)
            {
                if (!CIBlockElement::Delete($itemProductLunch['ID']))
                {
                    $data['error'] = Loc::getMessage('ERROR_REMOVE_PRODUCT_LUNCH');
                }
            }
        }

        // удаляем товар
        if (!CIBlockElement::Delete($post['id']))
        {
            $data['error'] = Loc::getMessage('ERROR_REMOVE_PRODUCT');
            return $data;
        }

        //если товаров в корзине не осталось, то удаляем корзину
        if (count($productsInBasket) <= 1)
        {
            $data['removeBasketId'][] = $post['idBasket'];
            if (!CIBlockElement::Delete($post['idBasket']))
            {
                $data['error'] = Loc::getMessage('ERROR_REMOVE_BASKET');
            }
            return $data;
        }

        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }
        $data['baskets'][$post['idBasket']]['removeProductId'][] = $post['id'];
        return $data;
    }

    /**
     * Метод для удаления корзины
     * @param array $post - данные post запроса "idBasket":id корзины
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function removeBasketAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }

        // получаем данные пользователя
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }

        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        // получаем обрабатываемую корзину
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }

        // получаем товары корзины
        $productsInBasket = Baskets::getProductsByBaskets($post['idBasket']);
        if (!$productsInBasket)
        {
            $data['error'] = Loc::getMessage('NO_PRODUCTS');
            return $data;
        }

        // удаляем использованные льготы
        $arFilter = [
            'IBLOCK_CODE' => 'zakaz_lgoty',
            'ACTIVE' => 'Y',
            'PROPERTY_ID_ZAKAZ' => $post['idBasket'],
        ];
        $arSelect = ['ID', 'NAME'];
        $res = CIBlockElement::GetList([], $arFilter, false, [], $arSelect);
        while ($elem = $res->Fetch())
        {
            if (!CIBlockElement::Delete($elem['ID']))
            {
                $data['error'] = Loc::getMessage('ERROR_REMOVE_BENEFIT');
            }
        }

        // удаляем товары корзины
        foreach ($productsInBasket as $keyProduct => $itemProduct)
        {
            // если комплект, то удаляем товары комплекта
            if ($itemProduct['IS_COMPLECT'])
            {
                $productLunch = Baskets::getLaunchProductsByBasket($itemProduct['ID']);
                foreach ($productLunch[$itemProduct['ID']] as $itemProductLunch)
                {
                    if (!CIBlockElement::Delete($itemProductLunch['ID']))
                    {
                        $data['error'] = Loc::getMessage('ERROR_REMOVE_PRODUCT_LUNCH');
                    }
                }
            }
            // удаляем товар
            if (!CIBlockElement::Delete($itemProduct['ID']))
            {
                $data['error'] = Loc::getMessage('ERROR_REMOVE_PRODUCT');
                return $data;
            }
        }

        // удаляем корзину
        $data['removeBasketId'][] = $post['idBasket'];
        if (!CIBlockElement::Delete($post['idBasket']))
        {
            $data['error'] = Loc::getMessage('ERROR_REMOVE_BASKET');
        }

        return $data;
    }

    /**
     * Метод для изменения данных комментария у корзины
     * @param array $post - данные post запроса "idBasket":id корзины, "value":комментарий оставленный опльзователем
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function updateCommentAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }

        // получаем данные пользователя
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }

        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        // получаем обрабатываемую корзину
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }

        if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "COMMENT", $post['value']))
        {
            $data['error'] = Loc::getMessage('ERROR_UPDATE');
            return $data;
        }

        return $data;
    }

    /**
     * Метод для изменения места доставки корзины
     * @param array $post - данные post запроса "idBasket":id корзины, "value":id места доставки
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function updateDeliveryPlaceAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }

        // получаем данные пользователя
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }

        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        // получаем обрабатываемую корзину
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }

        // получаем место доставки
        if ($post['value'] <> 0)
        {
            $deliveryPlace = DeliveryPlaces::getDeliveryPlacesById($post['value']);
        }

        // если успешно получены место доставки (id или 0) и корзина
        if ($deliveryPlace || $post['value'] == 0)
        {
            // проставляем новое значение месту доставки
            $statusUpdateDeliveryPlace = CIBlockElement::SetPropertyValueCode(
                $post['idBasket'],
                "ID_MESTO_DOSTAVKI",
                $deliveryPlace[$post['value']]['ID']
            );
            // проставляем новое значение типу оплаты
            $statusUpdatePaymentType = CIBlockElement::SetPropertyValueCode(
                $post['idBasket'],
                "ID_TIP_OPLATY",
                $deliveryPlace[$post['value']]['ID_BASIC_PAYMENT_TYPE']
            );
            // обнуляем период доставки, так как графики у каждого места доставки свои
            $statusUpdateDeliveryPeriod = CIBlockElement::SetPropertyValueCode(
                $post['idBasket'],
                "PERIOD_DOSTAVKY",
                0
            );
            // проверяем успешность установки новых значений, в ином случае выдаем предупреждение
            if (!$statusUpdateDeliveryPlace || !$statusUpdatePaymentType
                || !$statusUpdateDeliveryPeriod)
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        else if (!$deliveryPlace) // если не найдено место доставки
        {
            $data['error'] = Loc::getMessage('NO_DELIVERY_PLACE');
            return $data;
        }
        else if (!$basket) // если не найдена корзина
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }
        // получаем обновленные данные по корзинам
        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        // проверяем на наличие корзины
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }
        return $data;
    }

    /**
     * Метод для изменения периода доставки
     * @param array $post - данные post запроса "idBasket":id корзины, "value":id места доставки
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function updateDeliveryPeriodAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }

        // получаем данные пользователя
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }

        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        // получаем обрабатываемую корзину
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }

        // если получено значение по периоду доставки 0, тоест произошла установка в состояние "Не выбрано"
        if ($post['value'] == 0 && $basket)
        {
            $statusUpdateDeliveryPeriod = CIBlockElement::SetPropertyValueCode($post['idBasket'], "PERIOD_DOSTAVKY", 0);
            // проверяем успешность обновления данных
            if (!$statusUpdateDeliveryPeriod)
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        else if ($post['value'] && $basket) // если получены место доставки и корзина
        {
            // получаем места доставки с их графиками доставок
            $deliveryPlaces = CDeliveryPlaces::getDeliveryPlacesForBasket($basket['WORKAREA_ID']);
            // берем значение выбранного значения периода доставки
            $schedule = $deliveryPlaces[$basket[$post['idBasket']]['DAY_OF_THE_WEEK']]
            [$basket[$post['idBasket']]['ID_DELIVERY_PLACE']]['SCHEDULE'][$post['value']]['STR_FULL_TIME'];
            // если успешно получен график доставки
            if ($schedule)
            {
                // обновляем график доставки
                $statusUpdateDeliveryPeriod = CIBlockElement::SetPropertyValueCode($post['idBasket'], "PERIOD_DOSTAVKY",
                    $schedule);
                // проверяем успешность обновления графика доставки
                if (!$statusUpdateDeliveryPeriod)
                {
                    $data['error'] = Loc::getMessage('ERROR_UPDATE');
                    return $data;
                }
            }
            else
            {
                $data['error'] = Loc::getMessage('ERROR_DELIVERY_PERIOD');
                return $data;
            }
        }
        else // если не были получены место доставки или корзина
        {
            $data['error'] = Loc::getMessage('ERROR_UPDATE');
            return $data;
        }
        // получаем обновленные данные по корзинам
        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        // проверяем на наличие корзины
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }
        return $data;
    }

    /**
     * Метод для изменения периода доставки
     * @param array $post - данные post запроса "idBasket":id корзины, "value":id ПланМенюТовар
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function updateSpecialFoodAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'] || !($post['value'] >= 0))
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }
        $basket = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $post['idBasket']
        );
        if (!$basket)
        {
            $data['error'] = Loc::getMessage('NO_BASKET');
            return $data;
        }

        // если происходит отмена выбора спецпитания
        if ($post['value'] == 0)
        {
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "ID_PLAN_MENU_SPECPITANIE", '')
                || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "STATUS_SPECPITANIE", ''))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        else
        {
            $planMenuProduct = PlanMenuProducts::getPlanMenuProductsById($post['value']);
            if (!$planMenuProduct)
            {
                $data['error'] = Loc::getMessage('ERROR_NO_FIEND_DATA');
                return $data;
            }
            $propertyEnum = CIBlockPropertyEnum::GetList([], ["XML_ID" => 'selected', 'CODE' => 'STATUS_SPECPITANIE'])->Fetch();
            if (!$propertyEnum)
            {
                $data['error'] = Loc::getMessage('ERROR_NO_FIEND_DATA');
                return $data;
            }
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "ID_PLAN_MENU_SPECPITANIE", $post['value'])
                || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "STATUS_SPECPITANIE", $propertyEnum['ID']))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }

        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }
        return $data;
    }

    /**
     * Метод для обработки дополнительного способа оплаты "Использовать ПВЗП"
     * @param array $post - данные post запроса "idBasket":id корзины
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function additionPaymentAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }

        if (!$data['baskets'][$post['idBasket']]['PAYMENT_METHODS']['ADDITION_PAY']['ACTIVE'])
        {
            $data['error'] = Loc::getMessage('ERROR_PVZP_1');
            return $data;
        }

        if ($data['baskets'][$post['idBasket']]['PAYMENT_METHODS']['ADDITION_PAY']['CHECKED'])
        {
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "ADD_ADDITION_PAY", 0))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        else
        {
            $propertyEnum = CIBlockPropertyEnum::GetList([], ["XML_ID" => 'Y', 'CODE' => 'ADD_ADDITION_PAY',
                'IBLOCK_ID' => getIBlockIdByCode('s1', 'spravochnik', 'zakaz')])->Fetch();
            if (!$propertyEnum)
            {
                $data['error'] = Loc::getMessage('ERROR_NO_FIEND_DATA');
                return $data;
            }
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "ADD_ADDITION_PAY", $propertyEnum['ID']))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        /* получаем обновленные данные с расчетами */
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        return $data;
    }

    /**
     * Метод для обработки дополнительного способа оплаты "Оплата банковской картой при получении"
     * @param array $post - данные post запроса "idBasket":id корзины
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function cashlessPaymentAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }

        if (!$data['baskets'][$post['idBasket']]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE'])
        {
            $data['error'] = Loc::getMessage('ERROR_CASHLESS_1');
            return $data;
        }

        if ($data['baskets'][$post['idBasket']]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['CHECKED'])
        {
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "PAY_BANK_KART", 0))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        else
        {
            $propertyEnum = CIBlockPropertyEnum::GetList([], ["XML_ID" => 'Y', 'CODE' => 'PAY_BANK_KART',
                'IBLOCK_ID' => getIBlockIdByCode('s1', 'spravochnik', 'zakaz')])->Fetch();
            if (!$propertyEnum)
            {
                $data['error'] = Loc::getMessage('ERROR_NO_FIEND_DATA');
                return $data;
            }
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "PAY_BANK_KART", $propertyEnum['ID'])
                || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "CASH_PAYMENT", 0))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        /* получаем обновленные данные с расчетами */
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        return $data;
    }

    /**
     * Метод для обработки дополнительного способа оплаты "Оплата за наличный расчет при получении"
     * @param array $post - данные post запроса "idBasket":id корзины
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function cashPaymentAction($post)
    {
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }
        $statuses = OrderStatuses::getInstance();
        if (!$statuses)
        {
            $data['error'] = Loc::getMessage('NO_STATUS');
            return $data;
        }

        $basketComponent = new BasketComponent();
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        if (!$data['baskets'])
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }

        if (!$data['baskets'][$post['idBasket']]['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE'])
        {
            $data['error'] = Loc::getMessage('ERROR_CASH_1');
            return $data;
        }

        if ($data['baskets'][$post['idBasket']]['PAYMENT_METHODS']['CASH_PAYMENT']['CHECKED'])
        {
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "CASH_PAYMENT", 0))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        else
        {
            $propertyEnum = CIBlockPropertyEnum::GetList([], ["XML_ID" => 'Y', 'CODE' => 'CASH_PAYMENT',
                'IBLOCK_ID' => getIBlockIdByCode('s1', 'spravochnik', 'zakaz')])->Fetch();
            if (!$propertyEnum)
            {
                $data['error'] = Loc::getMessage('ERROR_NO_FIEND_DATA');
                return $data;
            }
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "CASH_PAYMENT", $propertyEnum['ID'])
                || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "PAY_BANK_KART", 0))
            {
                $data['error'] = Loc::getMessage('ERROR_UPDATE');
                return $data;
            }
        }
        /* получаем обновленные данные с расчетами */
        $data['baskets'] = $basketComponent->executeComponentAjax($post['idBasket']);
        return $data;
    }

    /**
     * Метод создания заказа из корзины
     * @param array $post - данные post запроса "idBasket":id корзины
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    public function makeOrderAction($post)
    {
        // возвращаемый массив в ajax запросе
        $data = [];
        // проверяем получение всех данных
        if (!$post['idBasket'])
        {
            $data['error'] = Loc::getMessage('NO_ALL_DATA');
            return $data;
        }
        $user = User::getInstance();
        if (!$user)
        {
            $data['error'] = Loc::getMessage('NO_USER');
            return $data;
        }

        $basketComponent = new BasketComponent();
        $baskets = $basketComponent->executeComponentAjax($post['idBasket']);

        if (!$baskets)
        {
            $data['error'] = Loc::getMessage('NO_ACCESS_BASKET');
            return $data;
        }

        if (!$baskets[$post['idBasket']]['ACTIVE'])
        {
            $data['error'] = Loc::getMessage('ERROR_NO_MAKE_ORDER') . "\n";
            foreach ($baskets[$post['idBasket']]['ERROR'] as $item)
            {
                $data['error'] .= $item . "\n";
            }
            return $data;
        }


        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        // проверяем получение статусов заказа
        if (!$statuses)
        {
            return $this->criticalError($post);
        }

        // формируем номер заказа с префиксом
        $strNum = $baskets[$post['idBasket']]['WORK_AREA_PREFIX'] . '-';

        for ($i = 0; $i < 9 - strlen($baskets[$post['idBasket']]['WORK_AREA_COUNT_ORDERS']); $i++)
        {
            $strNum .= 0;
        }


        $strNum .= $baskets[$post['idBasket']]['WORK_AREA_COUNT_ORDERS'] + 1;
        // добавляем номер
        if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "NUMBER", $strNum))
        {
            return $this->criticalError($post);
        }

        // добавляем оплату при получении
        if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "PAYMENT_GET",
            $baskets[$post['idBasket']]['PAYMENT_ON_RECEIPT']))
        {
            return $this->criticalError($post);
        }

        // изменяем общее количество сделанных заказов
        if (!CIBlockElement::SetPropertyValueCode($baskets[$post['idBasket']]['WORKAREA_ID'], "COUNT_ORDERS",
            $baskets[$post['idBasket']]['WORK_AREA_COUNT_ORDERS'] + 1))
        {
            return $this->criticalError($post);
        }

        // обрабатываем спецпитание
        // если спецпитание выбрано и оно доступно
        if ($baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']
            && $baskets[$post['idBasket']]['SPECIAL_FOOD_APPOINTED'])
        {
            // если спецпитание берется только в счет льгот
            if ($baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']['TYPE_ACCESS'] == 2)
            {
                $res = CIBlockPropertyEnum::GetList([],
                    [
                        "IBLOCK_ID" => getIBlockIdByCode('s1', 'spravochnik', 'zakaz'),
                        "CODE" => "STATUS_SPECPITANIE"
                    ]
                );
                while ($enumFields = $res->Fetch())
                {
                    if ($enumFields['XML_ID'] == 'benefits')
                    {
                        $enumFieldId = $enumFields['ID'];
                    }
                }
                // устанавливем значения и проверяем выполнено ли действие
                if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "STATUS_SPECPITANIE", $enumFieldId)
                    || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "PRICE_SPECPITANIE", 0)
                    || !$enumFieldId)
                {
                    return $this->criticalError($post);
                }
            }
            elseif ($baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']['TYPE_ACCESS'] == 1)
            {
                $res = CIBlockPropertyEnum::GetList([],
                    [
                        "IBLOCK_ID" => getIBlockIdByCode('s1', 'spravochnik', 'zakaz'),
                        "CODE" => "STATUS_SPECPITANIE"
                    ]
                );
                while ($enumFields = $res->Fetch())
                {
                    if ($enumFields['XML_ID'] == 'paid')
                    {
                        $enumFieldId = $enumFields['ID'];
                    }
                }

                // устанавливем значения и проверяем выполнено ли действие
                if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "STATUS_SPECPITANIE", $enumFieldId)
                    || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "PRICE_SPECPITANIE",
                        $baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']['PLAN_MENU_PRODUCTS']['PRICE'])
                    || !$enumFields)
                {
                    return $this->criticalError($post);
                }
            }

            //массив для добавления спецпитания в корзину битрикса
            $arFields = [
                'PRODUCT_ID' => $baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']['PLAN_MENU_PRODUCTS']['ID'],
                'QUANTITY' => 1,
                'PRICE' => $baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']['PLAN_MENU_PRODUCTS']['PRICE'],
                'CUSTOM_PRICE' => 'Y',
                'PROPS' => [
                    ['NAME' => 'КлючСтроки', 'CODE' => 'КлючСтроки',
                        'VALUE' => $baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']['PLAN_MENU_PRODUCTS']['ID']],
                    ['NAME' => 'Комплект', 'CODE' => 'Комплект', 'VALUE' => '0'],
                    ['NAME' => 'Спецпитание', 'CODE' => 'Спецпитание', 'VALUE' => '1']
                ],
            ];
            $res = Bitrix\Catalog\Product\Basket::addProduct($arFields);
            if (!$res->isSuccess())
            {
                return $this->criticalError($post);
            }
        }
        else // если спецпитание не выбрано или не доступно
        {
            $res = CIBlockPropertyEnum::GetList([],
                [
                    "IBLOCK_ID" => getIBlockIdByCode('s1', 'spravochnik', 'zakaz'),
                    "CODE" => "STATUS_SPECPITANIE"
                ]
            );
            while ($enumFields = $res->Fetch())
            {
                if ($enumFields['XML_ID'] == 'canseled')
                {
                    $enumFieldId = $enumFields['ID'];
                }
            }

            // устанавливем значения и проверяем выполнено ли действие
            if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "ID_PLAN_MENU_SPECPITANIE", '')
                || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "PRICE_SPECPITANIE", '')
                || !CIBlockElement::SetPropertyValueCode($post['idBasket'], "STATUS_SPECPITANIE", $enumFieldId)
                || !$enumFieldId)
            {
                return $this->criticalError($post);
            }
        }

        foreach ($baskets[$post['idBasket']]['CATEGORIES'] as $keyCategory => $itemCategory)
        {
            foreach ($itemCategory['PRODUCTS'] as $keyProduct => $itemProduct)
            {
                if ($itemProduct['IS_COMPLECT'])
                {
                    $arFields = [
                        'PRODUCT_ID' => $itemProduct['ID_PLAN_MENU_TOVAR'],
                        'QUANTITY' => $itemProduct['COUNT'],
                        'PRICE' => 0,
                        'CUSTOM_PRICE' => 'Y',
                        'PROPS' => [
                            ['NAME' => 'Комплект', 'CODE' => 'Комплект', 'VALUE' => '1'],
                            ['NAME' => 'ЯвляетсяМодификатором', 'CODE' => 'ЯвляетсяМодификатором', 'VALUE' => '0'],
                            ['NAME' => 'КлючСтроки', 'CODE' => 'КлючСтроки', 'VALUE' => $itemProduct['ID']],
                        ],
                    ];
                    $res = Bitrix\Catalog\Product\Basket::addProduct($arFields);
                    if (!$res->isSuccess())
                    {
                        return $this->criticalError($post);
                    }

                    foreach ($itemProduct['LUNCH']['CATEGORIES'] as $keyCategoryLunch => $itemCategoryLunch)
                    {
                        foreach ($itemCategoryLunch['PRODUCTS'] as $keyProductLunch => $itemProductLunch)
                        {
                            $arFields = [
                                'PRODUCT_ID' => $itemProductLunch['ID_PRODUCT_LUNCH'],
                                'QUANTITY' => $itemProductLunch['COUNT'] * $itemProduct['COUNT'],
                                'PROPS' => [
                                    ['NAME' => 'Комплект', 'CODE' => 'Комплект', 'VALUE' => '0'],
                                    ['NAME' => 'ЯвляетсяМодификатором',
                                        'CODE' => 'ЯвляетсяМодификатором', 'VALUE' => '1'],
                                    ['NAME' => 'КлючСтроки',
                                        'CODE' => 'КлючСтроки', 'VALUE' => $itemProductLunch['ID_PRODUCT_LUNCH']],
                                    ['NAME' => 'КлючСтрокиВладельцаМодификатора',
                                        'CODE' => 'КлючСтрокиВладельцаМодификатора', 'VALUE' => $itemProduct['ID']],
                                ],
                            ];
                            $res = Bitrix\Catalog\Product\Basket::addProduct($arFields);
                            if (!$res->isSuccess())
                            {
                                return $this->criticalError($post);
                            }
                        }
                    }
                }
                else // если простой товар
                {
                    //массив для добавления простого товара
                    $arFields = [
                        'PRODUCT_ID' => $itemProduct['ID_PLAN_MENU_TOVAR'],
                        'QUANTITY' => $itemProduct['COUNT'], // количество, обязательно
                        //'PRICE' => 123, 'CUSTOM_PRICE' => 'Y', 'USER_ID ' => 'Y',
                        'PROPS' => [
                            ['NAME' => 'КлючСтроки', 'CODE' => 'КлючСтроки', 'VALUE' => $itemProduct['ID']],
                            ['NAME' => 'Комплект', 'CODE' => 'Комплект', 'VALUE' => '0']
                        ],
                    ];
                    $res = Bitrix\Catalog\Product\Basket::addProduct($arFields);
                    if (!$res->isSuccess())
                    {
                        return $this->criticalError($post);
                    }
                }
            }
        }

        // добавляем использованные льготы
        foreach ($baskets[$post['idBasket']]['FOOD_BENEFITS'] as $itemBenefit)
        {
            if ($itemBenefit['ACTIVE'] && $itemBenefit['UCHET_OST_PO_SUMME'])
            {

                $useSumMonth = 0;
                if ($itemBenefit['KONTROL_MESYACHNUYU_NORMU'])
                {
                    $useSumMonth = $itemBenefit['DATA_SHOW']['USE'];
                }

                $PROP = [
                    'ZNACHENIE_LGOTY_KOLICH' => 0,// используемая сумма за день
                    'ZNACHENIE_LGOTY_KOLICH_V_MESYAC' => 0,// используемая сумма за месяц
                    'ZNACHENIE_LGOTY' => $itemBenefit['DATA_SHOW']['USE'],// используемые льготы за день
                    'ZNACHENIE_LGOTY_V_MESYAC' => $useSumMonth,// используемые льготы за месяц
                    'USE_LGOT_SUMMA' => $itemBenefit['BALANCE_DAY_SUM'],// остаток льготы за день до осуществления заказа
                    'USE_LGOT_SUMMA_V_MESYAC' => $itemBenefit['BALANCE_MONTH_SUM'],// остаток льготы за месяц до осуществления заказа
                    'USE_LGOT_KOLICH' => 0,
                    'USE_LGOT_KOLICH_V_MESYAC' => 0,
                    'ID_VID_LGOTY' => $itemBenefit['ID'],
                    'ID_ZAKAZ' => $post['idBasket'],
                    'ID_USER' => $user->bitrixId,
                ];
                $arLoadProductArray = [
                    "MODIFIED_BY" => $user->bitrixId, // элемент изменен текущим пользователем
                    "IBLOCK_ID" => getIBlockIdByCode('s1', 'spravochnik', 'zakaz_lgoty'),
                    "PROPERTY_VALUES" => $PROP,
                    "NAME" => $user->accNum,
                    "ACTIVE" => "Y",
                ];

                $el = new CIBlockElement;
                if (!$ELEMENT_ID = $el->Add($arLoadProductArray))
                {
                    return $this->criticalError($post);
                }
            }
            else if ($itemBenefit['ACTIVE'] && $itemBenefit['UCHET_OST_PO_KOLICH']
                && $baskets[$post['idBasket']]['SELECTED_SPECIAL_FOOD']['TYPE_ACCESS'] == 2)
            {

                $useQuantityMonth = 0;
                if ($itemBenefit['KONTROL_MESYACHNUYU_NORMU'])
                {
                    $useQuantityMonth = $itemBenefit['DATA_SHOW']['USE'];
                }

                $PROP = [
                    'ZNACHENIE_LGOTY_KOLICH' => 1,
                    'ZNACHENIE_LGOTY_KOLICH_V_MESYAC' => $useQuantityMonth,
                    'ZNACHENIE_LGOTY' => 0,
                    'ZNACHENIE_LGOTY_V_MESYAC' => 0,
                    'USE_LGOT_SUMMA' => 0,
                    'USE_LGOT_SUMMA_V_MESYAC' => 0,
                    'USE_LGOT_KOLICH' => $itemBenefit['BALANCE_DAY_KOLICH'],// остаток льготы за день до осуществления заказа
                    'USE_LGOT_KOLICH_V_MESYAC' => $itemBenefit['BALANCE_MONTH_KOLICH'],// остаток льготы за месяц до осуществления заказа
                    'ID_VID_LGOTY' => $itemBenefit['ID'],
                    'ID_ZAKAZ' => $post['idBasket'],
                    'ID_USER' => $user->bitrixId,
                ];
                $arLoadProductArray = [
                    "MODIFIED_BY" => $user->bitrixId, // элемент изменен текущим пользователем
                    "IBLOCK_ID" => getIBlockIdByCode('s1', 'spravochnik', 'zakaz_lgoty'),
                    "PROPERTY_VALUES" => $PROP,
                    "NAME" => $user->accNum,
                    "ACTIVE" => "Y",
                ];

                $el = new CIBlockElement;
                if (!$ELEMENT_ID = $el->Add($arLoadProductArray))
                {
                    return $this->criticalError($post);
                }
            }
        }

        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID);
        $order = Bitrix\Sale\Order::create(SITE_ID, $user->bitrixId);
        $order->setPersonTypeId(1);//физическое лицо
        $order->setBasket($basket);
        $order->setField("STATUS_ID", $statuses->getOrderStatusIdBitrix('Otpravlen'));
        $order->setField('USER_DESCRIPTION', $baskets[$post['idBasket']]['COMMENT']['TEXT']);

        // задаем доставку и отгрузку
        $shipmentCollection = $order->getShipmentCollection();
        $delivery_id = 1;// ID службы доставки
        $shipment = $shipmentCollection->createItem(Bitrix\Sale\Delivery\Services\Manager::getObjectById($delivery_id));
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        foreach ($basket as $basketItem)
        {
            $item = $shipmentItemCollection->createItem($basketItem);
            $item->setQuantity($basketItem->getQuantity());
        }

        // задаем вид оплаты
        $paymentCollection = $order->getPaymentCollection();
        $pay_id = 1;// вид оплаты, внутренний счет
        $payment = $paymentCollection->createItem(Bitrix\Sale\PaySystem\Manager::getObjectById($pay_id));
        $payment->setField("SUM", $order->getPrice());//задаем сумму
        $payment->setField("CURRENCY", $order->getCurrency());//задаем валюту

        //объявляем объект свойств
        $propertyCollection = $order->getPropertyCollection();

        $orderProperties= [];
        $res = CSaleOrderProps::GetList([], [], false, false, []);
        while ($elem = $res->Fetch())
        {
            $orderProperties[$elem['CODE']] = $elem;
        }

        $valProperties = [
            'xml_id_period_pitania' => $baskets[$post['idBasket']]['PERIOD_XML_ID'],
            'xml_id_place_delivery' => $baskets[$post['idBasket']]['SELECTED_DELIVERY_PLACE']['XML_ID'],
            'xml_id_tip_oplaty' => $baskets[$post['idBasket']]['SELECTED_DELIVERY_PLACE']['XML_ID_PAYMENT_TYPE'],
            'period_pitaniya' => $baskets[$post['idBasket']]['PERIOD_NAME'],
            'oplata_v_schet_zp' => $baskets[$post['idBasket']]['ADDITION_PAYMENT_SUM'],
            'period_dostavki' => $baskets[$post['idBasket']]['DELIVERY_PERIOD'],
            'planiruemaya_data_zakaza' => date('Y-m-d', strtotime($baskets[$post['idBasket']]['ORDER_DATE'])),
            'mesto_dostavki' => $baskets[$post['idBasket']]['SELECTED_DELIVERY_PLACE']['NAME'],
            'tip_oplaty' => $baskets[$post['idBasket']]['SELECTED_DELIVERY_PLACE']['NAME_PAYMENT_TYPE'],
            'tabelnyj_nomer' => $user->accNum,
            'PLOSHCHADKA' => $baskets[$post['idBasket']]['WORK_AREA_NAME'],
            'ID_PLOSHCHADKA' => $baskets[$post['idBasket']]['WORKAREA_ID'],
            'nomer_zakaza' => $strNum,
            'oplata_bankovskoj_kartoj' =>
                $baskets[$post['idBasket']]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE']
                && $baskets[$post['idBasket']]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['CHECKED']
                    ? $baskets[$post['idBasket']]['PAYMENT_ON_RECEIPT'] : 0,
            'cash_payment' =>
                $baskets[$post['idBasket']]['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE']
                && $baskets[$post['idBasket']]['PAYMENT_METHODS']['CASH_PAYMENT']['CHECKED']
                    ? $baskets[$post['idBasket']]['PAYMENT_ON_RECEIPT'] : 0,
            'oplacheno_polnostyu' =>
                $baskets[$post['idBasket']]['PAYMENT_ON_RECEIPT'] > 0
                    ? 0 : 1,
        ];

        $this->setValPropertiesOrder($orderProperties, $valProperties, $propertyCollection);

        $result = $order->save();

        if (!$result->isSuccess())
        {
            return $this->criticalError($post);
        }

        // изменяем статус с "ВКорзине" на "Отправлен"
        if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "ID_STATUS_ZAKAZA",
            $statuses->getOrderStatusId('Otpravlen')))
        {
            return $this->criticalError($post);
        }

        if (!CIBlockElement::SetPropertyValueCode($post['idBasket'], "ID_ZAKAZ_IN_MAGAZIN", $order->getField('ACCOUNT_NUMBER')))
        {
            return $this->criticalError($post);
        }

        $data['MAKE_ORDER'][] = $post['idBasket'];
        $data['ORDER_NUM'] = $strNum;

        // получаем остальные актуальные корзины пользователя
        $data['idBaskets'] = array_keys(
            Baskets::getBaskets(
                $statuses->getOrderStatusId('VKorzine'),
                $user->bitrixId,
                ConvertDateTime(WorkArea::getDateTimeWorkArea($baskets[$post['idBasket']]['WORK_AREA_TIME_DISPLACEMENT']), "Y-m-d", "ru")
            )
        );

        foreach ($data['idBaskets'] as $idBasket)
        {
            $data['baskets'][$idBasket] = $basketComponent->executeComponentAjax($idBasket)[$idBasket];
            if ($data['baskets'][$idBasket] === null)
            {
                unset($data['baskets'][$idBasket]);
            }
        }
        return $data;
    }

    /**
     * Метод создания критической ошибки и удаления корзины с которой это произошло
     * @param array $post - данные post запроса "idBasket":id корзины
     * @param array $num - номер ошибки
     * @return array $data массив содержит ключи data, errors и success - массив результатов работы метода
     */
    private function criticalError($post)
    {
        // удаляем корзину пользователя в модуле интернет магазина Битрикс
        CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID());
        // заносим удаляемую корзину
        $data['removeBasketId'][] = $post['idBasket'];
        // формируем ошибку
        $data['error'] = Loc::getMessage('CRITICAL_ERROR');
        if ($res = $this->removeBasketAction($post)['error'])
        {
            $data['error'] .= "\n" . Loc::getMessage('ERROR_REMOVE_BASKET') . ' ' . $res;
        }
        else
        {
            array_merge($data, $res);
        }

        return $data;
    }

    /**
     * Метод задания значений свойств заказа
     * @param array $orderProperties - массив свойств заказа
     * @param array $valProperties - массив задаваемых значений заказу
     * @param array $propertyCollection - объект коллекции свойст заказа
     */
    private function setValPropertiesOrder($orderProperties, $valProperties, $propertyCollection)
    {
        foreach ($valProperties as $key=>$item)
        {
            $location = $propertyCollection->getItemByOrderPropertyId($orderProperties[$key]['ID']);
            $location->setValue($item);
        }
    }
}
