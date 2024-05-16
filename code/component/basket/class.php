<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use KS\Orders;
use KS\Nomenclatures;
use KS\FoodBenefits;
use KS\PlanMenuProducts;
use KS\Baskets;
use KS\Menus;
use KS\Helps;
use KS\SpecialFood;
use KS\Products;
use KS\Controllers\WorkArea;
use KS\Controllers\DeliveryPlaces;
use KS\Controllers\OrderStatuses;
use KS\Controllers\UserFoodBenefits;
use KS\Controllers\User;

// подключаем ленговые файлы
Loc::loadMessages(__FILE__);

/**
 * Класс компонента для работы с корзиной
 * Class Basket
 */
class BasketComponent extends CBitrixComponent
{
    // корзины пользователя
    public $baskets = [];

    /**
     * Проверка наличия шаблона
     * @return bool
     */
    private function checkTemplate()
    {
        if (!$this->InitComponentTemplate()) {
            ShowError(Loc::getMessage('NO_TEMPLATE'));
            return false;
        }
        return true;
    }

    /**
     * Проверка наличия модулей требуемых для работы компонента
     * @return bool
     */
    private function checkModules()
    {
        if (!Loader::includeModule('iblock')) {
            ShowError(Loc::getMessage('NO_INCLUDE_IBLOCK'));
            return false;
        }
        return true;
    }

    /**
     * Метод проверяет доступность корзины по времени окончания приема заказов из периода,
     * если не корзина не доступна, то происходит ее удаление
     * @return void
     */
    private function checkBasketsAccessTime()
    {
        foreach ($this->baskets as $key => $basket)
        {
            // дата и время окончания приема заказа
            $date_end = new \DateTime($basket['ORDER_DATE'] . ' ' . $basket['END_TIME_FOR_ORDERS'], new \DateTimeZone('UTC'));
            // текущая дата и время со смещением по периоду, время брать из площадки, на которую оформлена корзина
            $basketWorkAreaTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $basketWorkAreaTime->modify('+' . $basket['PERIOD_OFFSET_IN_DAYS'] * 24 + $basket['WORK_AREA_TIME_ZONE'] . 'hours');
            if ($basketWorkAreaTime->getTimestamp() > $date_end->getTimestamp())
            {
                unset($this->baskets[$key]);
            }
        }
    }

    /**
     * Получаем подсказки корзины
     */
    private function setHelps()
    {
        $help = Helps::getHelps();
        foreach ($this->baskets as $key => $item) {
            $this->baskets[$key]['TIPS'] = $help;
        }
    }

    /**
     * Задаем товары у корзин
     */
    private function setProducts()
    {
        // получаем товары корзин
        $products = Baskets::getProductsByBaskets(array_keys($this->baskets));
        // массив id номенклатур
        $arrIdNomenclatures = [];
        // массив id товаров в корзине
        $arrIdProductBaskets = [];
        // массив id номенклатур, которые являются комплектами
        $arrIdNomenclaturesComplect = [];
        // собираем id товаров и id номенклатур в корзине
        for ($i = 0; $i < count($products); $i++)
        {
            // массив id номенклатур
            $arrIdNomenclatures[] = $products[$i]['ID_NOMENKLATURA'];
            // массив id товаров в корзине
            $arrIdProductBaskets[] = $products[$i]['ID'];
            // если товар комплект
            if ($products[$i]['IS_COMPLECT'])
            {
                $arrIdNomenclaturesComplect[] = $products[$i]['ID_NOMENKLATURA'];
            }
        }
        // получаем номенклатуру у товаров
        $nomenclatures = Nomenclatures::getNomenclaturesById($arrIdNomenclatures);
        // распределяем номенклатуру по товарам в корзине
        for ($i = 0; $i < count($products); $i++)
        {
            for ($j = 0; $j < count($nomenclatures); $j++)
            {
                if ($products[$i]['ID_NOMENKLATURA'] == $nomenclatures[$j]['ID'])
                {
                    $products[$i]['NOMENKLATURA'] = $nomenclatures[$j];
                }
            }
        }
        // массив id категорий меню
        $arrIdCategories = [];
        // собираем id категорий меню
        for ($i = 0; $i < count($nomenclatures); $i++)
        {
            $arrIdCategories[] = $nomenclatures[$i]['ID_MENU_CATEGORY'];
        }
        // получаем категории
        $categoryes = Menus::getMenuCategoriesById($arrIdCategories);
        // собираем даты заказов
        $dateOrders = [];
        foreach ($this->baskets as $keyItemBasket=>$itemItemBasket)
        {
            $dateOrders[] = $itemItemBasket['ORDER_DATE'];
        }
        // получаем комплекты
        $allComplects = PlanMenuProducts::getLunchInfoByDates($arrIdNomenclaturesComplect, $dateOrders);
        $complects = [];
        // добавляем в комплексные обеды информацию о связанном меню
        foreach ($allComplects as $complect)
        {
            foreach ($this->baskets as $basket)
            {
                if ($complect['XML_ID'] == $basket['MENU_XML_ID']
                    && $complect['DATE_OF_PLANNING'] == $basket['ORDER_DATE'])
                {
                    $complect['MENU_ID'] = $basket['MENU_ID'];
                    $complects[] = $complect;
                }
            }
        }
        $arrIdComplect = [];
        //собираем id комплектов
        foreach ($complects as $itemComplect)
        {
            $arrIdComplect[] = $itemComplect['ID'];
        }
        // получаем все категории комплектов
        $categoryesComplect = Menus::getLaunchCategoriesForBasket($arrIdComplect);
        // получаем товары комплекта из инфоблока "КомплектТовары"
        $productLunch = Products::getLaunchProducts($arrIdComplect);
        // получаем товары комплекта в корзине
        $complectsProducts = Baskets::getLaunchProductsByBasket($arrIdProductBaskets);
        // распределяем категории по комплектам
        foreach ($complects as $keyItemComplect=>$itemComplect)
        {
            $complects[$keyItemComplect]['CATEGORIES'] = $categoryesComplect[$itemComplect['ID']];
        }
        // распределяем данные комплектов по товарам корзины и распределяем товары комплектов по категориям
        foreach ($complects as $complect)
        {
            for ($i = 0; $i < count($products); $i++)
            {
                if ($complect['MENU_ID'] == $products[$i]['ID_PLAN_MENU']
                    && $complect['ID_NOMENKLATURY'] == $products[$i]['ID_NOMENKLATURA'])
                {
                    $products[$i]['LUNCH'] = $complect;
                    foreach ($products[$i]['LUNCH']['CATEGORIES'] as $keyItemCategory => $itemItemCategory)
                    {
                        foreach ($complectsProducts[$products[$i]['ID']] as $keyProduct => $itemProduct)
                        {
                            if ($itemProduct['ID_CATEGORY'] == $keyItemCategory)
                            {
                                // добавляем к $itemProduct идентификатор товара комплекта из инфоблока "КомплектТовары"
                                for ($j = 0; $j < count($productLunch); $j++)
                                {
                                    if ($productLunch[$j]['NOMENCLATURE_ID'] == $itemProduct['ID_NOMENCLATURA'])
                                    {
                                        $itemProduct['ID_PRODUCT_LUNCH'] = $productLunch[$j]['ID'];
                                        break;
                                    }
                                }
                                $products[$i]['LUNCH']['CATEGORIES'][$keyItemCategory]['PRODUCTS'][] = $itemProduct;
                            }
                        }
                        /* проверяем обязательность выбора товара в категории комплекта, если не выбрано, то вешаем ошибку
                    на корзину и товар*/
                        if ($itemItemCategory['OBLIGATORY']
                            && count($products[$i]['LUNCH']['CATEGORIES'][$keyItemCategory]['PRODUCTS']) <= 0)
                        {
                            $products[$i]['ERROR'][]
                                = '<p>' . Loc::getMessage('OBLIGATORY_CATEGORY_LUNCH') . ' "' . $itemItemCategory['NAME']
                                . '", <a href="/komplect/?id=' . $products[$i]['ID_PLAN_MENU_TOVAR'] . '">' . Loc::getMessage('OBLIGATORY_CATEGORY_LUNCH') . '</a></p>';
                            $this->baskets[$products[$i]['ID_BASKET']]['ERROR'][]
                                = Loc::getMessage('OBLIGATORY_CATEGORY_LUNCH') . ' "' . $itemItemCategory['NAME']
                                . '", <a href="/komplect/?id=' . $products[$i]['ID_PLAN_MENU_TOVAR'] . '">' . Loc::getMessage('OBLIGATORY_CATEGORY_LUNCH') . '</a>';
                        }
                    }
                }
            }
        }
        // распределяем товары корзины по категориям
        foreach ($this->baskets as $keyItemBasket=>$itemItemBasket)
        {
            for ($i = 0; $i < count($products); $i++)
            {
                if ($products[$i]['ID_BASKET'] == $keyItemBasket)
                {
                    $this->baskets[$keyItemBasket]['CATEGORIES'][$products[$i]['NOMENKLATURA']['ID_MENU_CATEGORY']]['PRODUCTS'][] =
                        $products[$i];
                }
            }
            foreach ($this->baskets[$keyItemBasket]['CATEGORIES'] as $keyItemCategory=>$itemItemCategory)
            {
                // проверяем наличие категории
                if ($categoryes[$keyItemCategory])
                {
                    $this->baskets[$keyItemBasket]['CATEGORIES'][$keyItemCategory] += $categoryes[$keyItemCategory];
                }
                else // если категории нет
                {
                    $this->baskets[$keyItemBasket]['CATEGORIES'][$keyItemCategory] += [
                        'NAME'=> Loc::getMessage('ADDITIONALLY'),
                        'PREVIEW_PICTURE'=> null,
                    ];
                }
            }
        }
    }

    /**
     * Задаем общуе цену и цену товаров собственного производства корзинам
     */
    private function setPrice()
    {
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            // общая сумма товаров
            $this->baskets[$keyBasket]['SUM_FOOD'] = 0;
            // сумма товаров собственного производства
            $this->baskets[$keyBasket]['SUM_OWN_FOOD'] = 0;
            // проходим по каждой категории
            foreach ($itemBasket['CATEGORIES'] as $keyCategory => $itemCategory)
            {
                // проходим по товарам категории
                foreach ($itemCategory['PRODUCTS'] as $keyProduct => $itemProduct)
                {
                    $this->baskets[$keyBasket]['SUM_FOOD'] += $itemProduct['PRICE'] * $itemProduct['COUNT'];
                    // если товар собственного производства
                    if ($itemProduct['NOMENKLATURA']['TYPE_PRODUCT'] == 'Блюдо')
                    {
                        $this->baskets[$keyBasket]['SUM_OWN_FOOD'] += $itemProduct['PRICE'] * $itemProduct['COUNT'];
                    }
                }
            }
            // если выбрано спецпитание
            if ($this->baskets[$keyBasket]['SELECTED_SPECIAL_FOOD']
                && $this->baskets[$keyBasket]['SELECTED_SPECIAL_FOOD']['TYPE_ACCESS'] == 1
            )
            {
                $this->baskets[$keyBasket]['SUM_FOOD']
                    += $this->baskets[$keyBasket]['SELECTED_SPECIAL_FOOD']['PLAN_MENU_PRODUCTS']['PRICE'];
            }
            // убираем потерю точности в вычислении
            $this->baskets[$keyBasket]['SUM_FOOD'] = round($this->baskets[$keyBasket]['SUM_FOOD'], 2);
            $this->baskets[$keyBasket]['SUM_OWN_FOOD'] = round($this->baskets[$keyBasket]['SUM_OWN_FOOD'], 2);
        }
    }

    /**
     * Задаем доступные места доставки и выбранное место доставки всем корзинам пользователя
     */
    private function setDeliveryPlaces()
    {
        $workAreas = [];
        foreach ($this->baskets as $basket){
            $workAreas[] = $basket['WORKAREA_ID'];
        }
        // получаем места доставки
        $deliveryPlaces = DeliveryPlaces::getDeliveryPlacesForBasket($workAreas);
        // устанавливаем места доставки
        // обходим массив корзин
        foreach ($this->baskets as $key => $basket)
        {
            /*массив доступных мест доставок для выбора*/
            // обходим массив мест доставок
            foreach ($deliveryPlaces[$basket['DAY_OF_THE_WEEK']] as $id => $deliveryPlace)
            {
                // если площадка корзины и места доставки совпадают
                if ($basket['WORKAREA_ID'] !== $deliveryPlace['ID_WORK_AREA']) {
                    continue;
                }

                // кладем место доставки в корзину
                $this->baskets[$key]['DELIVERY_PLACES'][] = $deliveryPlace;

                /*проверяем существование выбранного места доставки у корзины и
                проверяем наличие дня недели в доступных местах доставки
                */
                if (!array_key_exists($basket['ID_DELIVERY_PLACE'], $deliveryPlaces[$basket['DAY_OF_THE_WEEK']])) {
                    /*устанавливаем место доставки у корзины 0, тоесть не выбрано*/
                    $status_update = \CIBlockElement::SetPropertyValueCode($basket['ID'], 'ID_MESTO_DOSTAVKI', 0);

                    if ($status_update) {
                        $this->baskets[$key]['ID_DELIVERY_PLACE'] = 0;
//                        logFile(date('Y-m-d H:i:s') . ' : Корзина с ID ' . $basket['ID'] . ' имеет не существующий'
//                            . ' id места доставки или отсутсвует активный день недели, устанавливаем в'
//                            . ' 0 : basket/class.php', false, 'errors.txt');
                        continue;
                    } //если обновление места доставки было не успешно, то нельзя пускать корзину дальше на обработку, удаляем
                    else {
                        $str_error = date('Y-m-d H:i:s') . ' : Корзина с ID ' . $basket['ID'] . ' имеет не существующий'
                            . ' id места доставки или отсутствует активный день недели, ошибка установки в 0,'
                            . ' корзина удалена из обработки : basket/class.php';
                        $this->baskets[$key]['ERROR'] = $str_error;
//                        logFile($str_error, false, 'errors.txt');
                        continue;
                    }
                }
                //если все условия выше пройдены успешно, то добавляем данные места доставки в корзину
                $this->baskets[$key]['SELECTED_DELIVERY_PLACE'] =
                    $deliveryPlaces[$basket['DAY_OF_THE_WEEK']][$basket['ID_DELIVERY_PLACE']];

                foreach ($this->baskets[$key]['SELECTED_DELIVERY_PLACE']['SCHEDULE']
                         as $keySchedule => $itemSchedule) {
                    if ($basket['DELIVERY_PERIOD'] == $itemSchedule['STR_FULL_TIME']) {
                        $this->baskets[$key]['SELECTED_DELIVERY_PLACE']['SCHEDULE'][$keySchedule]['SELECTED'] = true;
                    }
                }

            }
        }
    }

    /**
     * Задаем льготы корзинам
     * @param $user - массив данных пользователя
     */
    private function setFoodBenefits($user)
    {
        // получаем статусы заказов
        $orderStatuses = OrderStatuses::getInstance();
        // формируем время относительно рабочей площадки
        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->modify("+".$user->workArea['TIME_DISPLACEMENT']." hour");
        // получаем использованные льготы в заказах относительно дат корзин
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            // делаем экземпляр класса льгот
            $userFoodBenefits = new UserFoodBenefits($user->bitrixId, $itemBasket['ORDER_DATE']);
            // если дата корзины равна текущей дате
            if ($itemBasket['ORDER_DATE'] == $date->format('d.m.Y'))
            {
                // получаем заказы только со статусом "Отправлен"
                $orders = Orders::getOrders($orderStatuses->getOrderStatusId('Otpravlen'),
                    $user->bitrixId, $itemBasket['ORDER_DATE']);
                // задаем использованные льготы в заказах
                $userFoodBenefits->inOrderBenefits
                    = FoodBenefits::getBenefitsInOrders($orders, $userFoodBenefits->benefitsInfo, $user->bitrixId,
                    $itemBasket['ORDER_DATE']);
                // делаем расчет доступных льгот
                $userFoodBenefits->setUserFoodBenefitsBalance();
            }
            // если дата корзины не равна текущей дате
            else
            {
                // получаем заказы со статусами "Отправлен", "ВОбработке", "ВПроизводстве", "Выполнен"
                $orders = Orders::getOrders(
                    [
                        $orderStatuses->getOrderStatusId('Otpravlen'),
                        $orderStatuses->getOrderStatusId('VObrabotke'),
                        $orderStatuses->getOrderStatusId('VProizvodstve'),
                        $orderStatuses->getOrderStatusId('Vypolnen'),
                    ],
                    $user->bitrixId,
                    $itemBasket['ORDER_DATE']
                );
                // задаем использованные льготы в заказах
                $userFoodBenefits->inOrderBenefits
                    = FoodBenefits::getBenefitsInOrders($orders, $userFoodBenefits->benefitsInfo, $user->bitrixId,
                    $itemBasket['ORDER_DATE']);
                // делаем расчет доступных льгот
                $userFoodBenefits->setUserFoodBenefitsBalanceWithoutUsedBenefits();
            }
            // задаем корзине данные видов льгот
            $this->baskets[$keyBasket]['FOOD_BENEFITS'] = $userFoodBenefits->benefitsInfo;
            // общая сумма доступных льгот
            $this->baskets[$keyBasket]['TOTAL_AMOUNT_BENEFITS'] = 0;
            // устанавливаем флаг не доступности спецпитания
            $this->baskets[$keyBasket]['SPECIAL_FOOD_APPOINTED'] = false;
            /* дополняем массив видов льгот у корзины данными по доступным остаткам льгот и
            определяем активность льгот*/
            foreach ($userFoodBenefits->balanceBenefits as $key => $item)
            {
                if (!$this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]) continue;
                // присваиваем остатки льгот
                $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key] += $item;
                // дополняем назначенными льготами по умолчанию без вычета использованных
                $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key] += $userFoodBenefits->defaultBenefits[$key];
                // изначально льгота доступна, а далее обрабатываем случаи недоступности
                $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['ACTIVE'] = true;
                // проверяем выбрано ли место доставки, если нет, то льготы не активны
                if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE'])
                {
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['ACTIVE'] = false;
                }
                /* если место доставки подразумевает все способы оплаты и льгота предоставляется в местах
                где нет ККМ (допустим льгота ПВЗП)*/
                if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['PAYMENT_TYPE']
                    && $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['IN_PLACE_PAYMENT'])
                {
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['ACTIVE'] = false;
                }
                // проверяем на доступность льготы при минимальной сумме заказа (Спецпитания и ПЛУ)
                if ($this->baskets[$keyBasket]['SUM_OWN_FOOD'] < $this->baskets[$keyBasket]['MIN_SUM_ORDER']
                    && $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['ACCESS_WITH_MIN_ORDER_AMOUNT'])
                {
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['ACTIVE'] = false;
                    // добавляем рекомендацию для получения доступа к льготе
                    $this->baskets[$keyBasket]['RECOMMENDATION'][]
                        = $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['NAME'].' доступно при заказе товаров собственного'.
                        ' приготовления на сумму не менее ' . $this->baskets[$keyBasket]['MIN_SUM_ORDER'] . ' руб.';
                }
                // если льгота активна и учет по сумме, то суммируем с общей суммой льгот
                if ($this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['ACTIVE']
                    && $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['UCHET_OST_PO_SUMME'])
                {
                    // общая сумма доступных льгот
                    $this->baskets[$keyBasket]['TOTAL_AMOUNT_BENEFITS']
                        += $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['BALANCE_DAY_SUM'];
                }
                // если льгота активна, учет по количеству и количество льготы более нуля, то пользователю назначено
                // спецпитание
                if ($this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['ACTIVE']
                    && $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['UCHET_OST_PO_KOLICH']
                    && $this->baskets[$keyBasket]['FOOD_BENEFITS'][$key]['BALANCE_DAY_KOLICH'] > 0)
                {
                    // устанавливаем флаг доступности спецпитания
                    $this->baskets[$keyBasket]['SPECIAL_FOOD_APPOINTED'] = true;
                }
            }
        }
    }

    /**
     * Задаем доступное спецпитание корзинам. У каждой корзины может быть своя площадка
     * для каждой площадки свое спецпитание.
     */
    private function setSpecialFood()
    {
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            // если спецпитание пользователю не назначено - пропускаем
            if (!$itemBasket['SPECIAL_FOOD_APPOINTED']) continue;
            $specialFoodTypes = \KS\SpecialFoodTypes::getSpecialFoodTypes($itemBasket['WORKAREA_ID']);
            $specialFoodTypeIds = [];
            foreach ($specialFoodTypes as $specialFoodType)
            {
                $specialFoodTypeIds[] = $specialFoodType['ID'];
            }
            // присваиваем массив спецпитания
            $this->baskets[$keyBasket]['SPECIAL_FOOD'] = SpecialFood::getSpecialFood($itemBasket['WORKAREA_ID'], $specialFoodTypeIds);
            if (empty($this->baskets[$keyBasket]['SPECIAL_FOOD']))
            {
                unset($this->baskets[$keyBasket]['SPECIAL_FOOD']);
                continue;
            };
            // массив id номенклатур спецпитания
            $arrIdNomenklatura = [];
            foreach ($this->baskets[$keyBasket]['SPECIAL_FOOD'] as $keySpecialFood => $itemSpecialFood)
            {
                foreach ($itemSpecialFood['BENEFIT'] as $keyBenefit => $itemBenefit)
                {
                    /*проверяем наличие льготы спецпитания у корзины, если у корзины нет,
                    то удаляем льготу у спецпитания*/
                    if (!isset($this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]))
                    {
                        unset($this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['BENEFIT'][$keyBenefit]);
                    }
                }
                // если нет ни одной льготы у спецпитания
                if (!$this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['BENEFIT'])
                {
                    unset($this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]);
                    continue;
                }
                // собираем id номенклатур спецпитания
                $arrIdNomenklatura[] = $itemSpecialFood['ID_NOMENKLATURA'];
            }
            // получаем ПланМенюТовар для проставки цен у спецпитания
            $planMenuProducts = PlanMenuProducts::getPlanMenuProductsByIdNomenklatures($arrIdNomenklatura);
            // распределяем ПланМенюТовар по спецпитанию
            foreach ($this->baskets[$keyBasket]['SPECIAL_FOOD'] as $keySpecialFood => $itemSpecialFood)
            {
                // если у спецпитания нет записи в планМенюТовар - удаляем
                if (is_null($planMenuProducts[$itemSpecialFood['ID_NOMENKLATURA']]))
                {
                    unset($this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]);
                    continue;
                }
                // присваиваем ПланМенюПродукт спецпитанию
                $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['PLAN_MENU_PRODUCTS'] = $planMenuProducts[$itemSpecialFood['ID_NOMENKLATURA']];
                // устанавливаем свойство выбранности элементу спецпитания
                if ($this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['PLAN_MENU_PRODUCTS']['ID']
                    == $this->baskets[$keyBasket]['ID_PLAN_MENU_PRODUCT_SPECIAL_FOOD'])
                {
                    $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['SELECTED'] = true;
                }
                /* значение указывающее на доступность спецпитания
                    0 - запрещен
                    1 - с ценой
                    2 - без цены */
                $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['TYPE_ACCESS'] = 0;
                // имя спецпитания для отображения в списке
                $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['SHOW_NAME'] = '';
                // проходим по каждому id льготы у спецпитания
                foreach ($itemSpecialFood['BENEFIT'] as $keyBenefit => $itemBenefit)
                {
                    /*если есть активная льгота, тип доступа менее двух и есть в наличии льгота
                     с доступным количеством*/
                    if ($this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['ACTIVE']
                        && $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['TYPE_ACCESS']<2
                        && $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['BALANCE_DAY_KOLICH']>0)
                    {
                        $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['TYPE_ACCESS'] = 2;
                        $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['SHOW_NAME']
                            = $planMenuProducts[$itemSpecialFood['ID_NOMENKLATURA']]['NAME'];
                    }
                    /*если есть активная льгота, тип доступа менее двух и нет в наличии льготы
                     с доступным количеством*/
                    elseif ($this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['ACTIVE']
                        && $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['TYPE_ACCESS']<1
                        && $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['BALANCE_DAY_KOLICH']<=0)
                    {
                        $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['TYPE_ACCESS'] = 1;
                        $this->baskets[$keyBasket]['SPECIAL_FOOD'][$keySpecialFood]['SHOW_NAME']
                            = $planMenuProducts[$itemSpecialFood['ID_NOMENKLATURA']]['NAME'].
                            ' '.$itemSpecialFood['PLAN_MENU_PRODUCTS']['PRICE'].' руб.';
                    }
                }
            }
            if ($this->baskets[$keyBasket]['ID_SPECIAL_FOOD'])
            {
                $this->baskets[$keyBasket]['SELECTED_SPECIAL_FOOD']
                    = $this->baskets[$keyBasket]['SPECIAL_FOOD'][$this->baskets[$keyBasket]['ID_NOMENKLATURA_SPECIAL_FOOD']];
            }
        }
    }

    /**
     * Задаем использованные льготы в корзине для оплаты
     */
    private function setUsedBenefits()
    {
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            // дублируем общую сумму корзины для вычесления использованных льгот для погашения всей суммы
            $sumFood = $this->baskets[$keyBasket]['SUM_FOOD'];
            // общая сумма оплаченная льготами
            $this->baskets[$keyBasket]['PAID_WITH_BENEFITS'] = 0;
            // общая сумма льгот использованных как дополнительная оплата ПВЗП
            $this->baskets[$keyBasket]['ADDITION_PAYMENT_SUM'] = 0;
            // булево значение указывающие на наличие льгот, учет которых идет по сумме
            $this->baskets[$keyBasket]['IS_BENEFITS_ON_SUM'] = false;

            // если пользователю не назначены льготы, то присваиваем общую стоимость корзины без вычета льгот
            if (!$this->baskets[$keyBasket]['FOOD_BENEFITS'])
            {
                $this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT'] = $this->baskets[$keyBasket]['SUM_FOOD'];
            }

            // определяем какие льготы и их количество были использованы в корзине
            foreach ($this->baskets[$keyBasket]['FOOD_BENEFITS'] as $keyBenefit => $itemBenefit)
            {
                // общее количество использованных льгот
                $useBalanceBenefit = 0;

                // проставляем флаг наличия льготы, учет которой идет по сумме
                if ($itemBenefit['UCHET_OST_PO_SUMME'] && !$this->baskets[$keyBasket]['IS_BENEFITS_ON_SUM'])
                {
                    $this->baskets[$keyBasket]['IS_BENEFITS_ON_SUM'] = true;
                }
                
                // если учет по сумме, льгота активна и осталась не оплаченная сумма корзины
                if ($itemBenefit['UCHET_OST_PO_SUMME'] && $itemBenefit['ACTIVE'] && $sumFood>0)
                {
                    // если для погашения суммы хватает одной льготы
                    if ($sumFood<=$itemBenefit['BALANCE_DAY_SUM'])
                    {
                        $useBalanceBenefit = $sumFood;
                    }
                    // если для погашения суммы не хватает льготы
                    else
                    {
                        $useBalanceBenefit = $itemBenefit['BALANCE_DAY_SUM'];
                    }

                    // если може использоваться как дополнительная оплата и дополнительная оплата в счет ПВЗП активирована
                    if ($itemBenefit['ADD_ADDITION_PAY']
                        && $this->baskets[$keyBasket]['PAYMENT_METHODS']['ADDITION_PAY']['ACTIVE']
                        && $this->baskets[$keyBasket]['PAYMENT_METHODS']['ADDITION_PAY']['CHECKED'])
                    {
                        $this->baskets[$keyBasket]['ADDITION_PAYMENT_SUM'] += $useBalanceBenefit;
                    }

                    // указывает часть использованной льготы для погашения суммы корзины
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['USE'] = ceil($useBalanceBenefit);
                    // остаток от льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['REST_BALANCE']
                        = $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['BALANCE_DAY_SUM'];
                    // процент использованной льготы
                    if ($itemBenefit['BALANCE_DAY_SUM'] == 0)
                    {
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE'] = 100;
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST'] = 0;
                    }
                    else
                    {
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE']
                            = ceil($useBalanceBenefit*100/$itemBenefit['BALANCE_DAY_SUM']);
                        // процент остатка льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST']
                            = 100 - ceil($useBalanceBenefit*100/$itemBenefit['BALANCE_DAY_SUM']);
                    }
                }
                // если сумма корзины погашена полностью
                elseif ($itemBenefit['UCHET_OST_PO_SUMME'] && $itemBenefit['ACTIVE'] && $sumFood<=0)
                {
                    $useBalanceBenefit = 0;
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['USE'] = 0;
                    // остаток от льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['REST_BALANCE']
                        = $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['BALANCE_DAY_SUM'];
                    // процент использованной льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE']
                        = 0;
                    // процент остатка льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST']
                        = 100;
                }
                // остальные случаи
                else
                {
                    $useBalanceBenefit = 0;
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['USE'] = 0;
                    // остаток от льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['REST_BALANCE']
                        = $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['BALANCE_DAY_SUM'];
                    // процент использованной льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE']
                        = 0;
                    // процент остатка льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST']
                        = 100;
                }
                // вычитаем из общей суммы корзины использованную часть льготы
                $sumFood -= $useBalanceBenefit;
                // суммируем к общей сумме использованных льгот часть использованной льготы
                $this->baskets[$keyBasket]['PAID_WITH_BENEFITS'] += $useBalanceBenefit;
                // если выбрано спецпитание
                if ($this->baskets[$keyBasket]['SELECTED_SPECIAL_FOOD'])
                {
                    // количество, которое может быть использовано спецпитанием
                    $quantityUseSpecialFood = 1;
                    // если учет по количеству, активен, количество использованного не менне 1 и есть актуальная льгота
                    if ($itemBenefit['UCHET_OST_PO_KOLICH'] && $itemBenefit['ACTIVE']
                        && $quantityUseSpecialFood>0 && $itemBenefit['BALANCE_DAY_KOLICH']>0)
                    {
                        // использованная часть льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['USE'] = 1;
                        // остаток льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['REST_BALANCE']
                            = $itemBenefit['BALANCE_DAY_KOLICH'];
                        // процент использованной льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE']
                            = 1*100/$itemBenefit['BALANCE_DAY_KOLICH'];
                        // процент остатка льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST']
                            = 100 - 1*100/$itemBenefit['BALANCE_DAY_KOLICH'];
                    }
                    // если учет по количеству, активен, количество использованного 0 и менне
                    elseif ($itemBenefit['UCHET_OST_PO_KOLICH'] && $itemBenefit['ACTIVE']
                        && $quantityUseSpecialFood<=0)
                    {
                        // использованная часть льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['USE'] = 0;
                        // остаток льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['REST_BALANCE']
                            = $itemBenefit['BALANCE_DAY_KOLICH'];
                        // процент использованной льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE']
                            = 0;
                        // процент остатка льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST']
                            = 100;
                    }
                    // остальные случаи
                    elseif ($itemBenefit['UCHET_OST_PO_KOLICH'] && $itemBenefit['ACTIVE'])
                    {
                        // использованная часть льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['USE'] = 0;
                        // остаток льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['REST_BALANCE']
                            = $itemBenefit['BALANCE_DAY_KOLICH'];
                        // процент использованной льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE']
                            = 0;
                        // процент остатка льготы
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST']
                            = 100;
                    }
                }
                // если не выбрано спецпитание
                elseif ($itemBenefit['UCHET_OST_PO_KOLICH'])
                {
                    // использованная часть льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['USE'] = 0;
                    // остаток льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['REST_BALANCE']
                        = $itemBenefit['BALANCE_DAY_KOLICH'];
                    // процент использованной льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_USE']
                        = 0;
                    // процент остатка льготы
                    $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['DATA_SHOW']['PROGRESS_REST']
                        = 100;
                }
                // если не выбрано место доставки, то доплата при получении равна 0
                if (!isset($this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']))
                {
                    $this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT'] = 0;
                }
                /* если выбрано место доставки, то доплата при получении получается разницой общей суммы корзины и
                общей суммы достуных льгот */
                else
                {
                    $this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT']
                        = round($this->baskets[$keyBasket]['SUM_FOOD'] - $this->baskets[$keyBasket]['PAID_WITH_BENEFITS'], 2);
                    // если менее нуля
                    if ($this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT'] < 0)
                    {
                        $this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT'] = 0;
                    }
                }
            }
        }
    }

    /**
     * Задаем дополнительную оплату (в счет заработной платы - ПВЗП)
     */
    private function setAdditionPayment()
    {
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            // дополнительная оплата в счет ПВЗП
            $this->baskets[$keyBasket]['PAYMENT_METHODS']['ADDITION_PAY'] =[
                'ACTIVE' => true,
                'MESSAGE' => '',
                'CHECKED' => $this->baskets[$keyBasket]['ADDITION_PAY'],
            ];
            // если место доставки не выбрано
            if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['ADDITION_PAY'] =[
                    'ACTIVE' => false,
                    'MESSAGE' => '',
                    'CHECKED' => false,
                ];
                continue;
            }
            /* если место доставки подразумевает только безналичный расчет, тоесть только в счет льгот, то льгота,
            используемая в качестве дополнительной оплаты будет использоваться по умолчанию*/
            if ($this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['PAYMENT_TYPE'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['ADDITION_PAY'] =[
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('ADDITION_PAY_NOT_ACCESS_1'),
                    'CHECKED' => false,
                ];
                continue;
            }

            // общая сумма льгот, которые могут быть использованы как дополнительные
            $totalAmountAdditionPaymentBenefits = 0;
            // получаем сумму всех льгот, которые могут быть использованы в качестве дополнительной оплаты
            foreach ($this->baskets[$keyBasket]['FOOD_BENEFITS'] as $keyBenefit => $itemBenefit)
            {
                // если льгота доступна в качестве дополнительной оплаты и она не активна
                if ($itemBenefit['ADD_ADDITION_PAY'] && !$itemBenefit['ACTIVE'])
                {
                    $totalAmountAdditionPaymentBenefits
                        += $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['BALANCE_DAY_SUM'];
                }
            }

            /* если общая сумма активных льгот и льгот использованных как
            дополнительная оплата не гасят всю сумму заказа */
            if (($totalAmountAdditionPaymentBenefits + $this->baskets[$keyBasket]['TOTAL_AMOUNT_BENEFITS'])
                < $this->baskets[$keyBasket]['SUM_FOOD'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['ADDITION_PAY'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('ADDITION_PAY_NOT_ACCESS_2'),
                    'CHECKED' => false,
                ];

                /* если не назначены льготы и не существует льгот, учет которых идет по сумме, то выводить рекомендацию
                   об причине недоступности льгот нет необходимости */
                if ($this->baskets[$keyBasket]['FOOD_BENEFITS'] && $this->baskets[$keyBasket]['IS_BENEFITS_ON_SUM'])
                {
                    $this->baskets[$keyBasket]['RECOMMENDATION'][]
                        = Loc::getMessage('RECOMMENDATION_PART_1') .
                        $totalAmountAdditionPaymentBenefits .
                        Loc::getMessage('RECOMMENDATION_PART_2');
                }
                continue;
            }

            // если дополнительная оплата включена
            if ($this->baskets[$keyBasket]['ADDITION_PAY'])
            {
                // дополняем итоговую сумму льгот корзины дополнительными льготами
                $this->baskets[$keyBasket]['TOTAL_AMOUNT_BENEFITS'] += $totalAmountAdditionPaymentBenefits;
                // активируем льготы, которые были использованы как дополнительная оплата
                foreach ($this->baskets[$keyBasket]['FOOD_BENEFITS'] as $keyBenefit => $itemBenefit)
                {
                    // если льгота доступна в качестве дополнительной оплаты и она не активна
                    if ($itemBenefit['ADD_ADDITION_PAY'] && !$itemBenefit['ACTIVE'])
                    {
                        $this->baskets[$keyBasket]['FOOD_BENEFITS'][$keyBenefit]['ACTIVE'] = true;
                    }
                }
            }
        }
    }

    /**
     * Задаем безналичный расчет (оплата банковской картой)
     */
    private function setCashlessPayment()
    {
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            // безналичный расчет на месте (оплата банковской картой)
            $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT'] = [
                'ACTIVE' => true,
                'MESSAGE' => '',
                'CHECKED' => $this->baskets[$keyBasket]['CASHLESS_PAYMENT'],
            ];
            // если место доставки не выбрано, то деактивируем безналичный расчет
            if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => '',
                    'CHECKED' => false,
                ];
                continue;
            }
            // место доставки не подразумевает оплату только в счет льгот
            if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['PAYMENT_TYPE'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('CASHLESS_PAYMENT_NOT_ACCESS_1'),
                    'CHECKED' => false,
                ];
                continue;
            }
            // если место доставки не подразумевает безналичный расчет
            if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['CASHLESS_PAYMENTS'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('CASHLESS_PAYMENT_NOT_ACCESS_2'),
                    'CHECKED' => false,
                ];
                continue;
            }
            // сумма доступных льгот достаточна для погашения всей суммы заказа
            if ($this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT']<=0)
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('CASHLESS_PAYMENT_NOT_ACCESS_3'),
                    'CHECKED' => false,
                ];
                continue;
            }
        }
    }

    /**
     * Задаем наличный расчет
     */
    private function setCashPayment()
    {
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            // наличный расчет на месте
            $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT'] = [
                'ACTIVE' => true,
                'MESSAGE' => '',
                'CHECKED' => $this->baskets[$keyBasket]['CASH_PAYMENT'],
            ];
            // если место доставки не выбрано, то деактивируем наличный расчет
            if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => '',
                    'CHECKED' => false,
                ];
                continue;
            }
            // место доставки не подразумевает оплату только в счет льгот
            if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['PAYMENT_TYPE'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('CASH_PAYMENT_NOT_ACCESS_1'),
                    'CHECKED' => false,
                ];
                continue;
            }
            // если место доставки не подразумевает наличный расчет
            if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['CASH_PAYMENT'])
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('CASH_PAYMENT_NOT_ACCESS_2'),
                    'CHECKED' => false,
                ];
                continue;
            }
            // сумма доступных льгот достаточна для погашения всей суммы заказа
            if ($this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT']<=0)
            {
                $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT'] = [
                    'ACTIVE' => false,
                    'MESSAGE' => Loc::getMessage('CASH_PAYMENT_NOT_ACCESS_3'),
                    'CHECKED' => false,
                ];
                continue;
            }
        }
    }

    /**
     * Проверяем корзины на доступность осуществления заказа
     * @param $user - данные пользователя
     */
    private function checkBaskets($user)
    {
        foreach ($this->baskets as $keyBasket => $itemBasket)
        {
            $this->baskets[$keyBasket]['ACTIVE'] = 1;
            // если корзина имеет ошибки при формировании
            if ($this->baskets[$keyBasket]['ERROR'])
            {
                $this->baskets[$keyBasket]['ACTIVE'] = 0;
            }
            // если не выбрано место доставки
            else if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE'])
            {
                $this->baskets[$keyBasket]['ACTIVE'] = 0;
                $this->baskets[$keyBasket]['ERROR'][] = Loc::getMessage('ERROR_BASKET_1');
            }
            // если место доставки подразумевает оплату, тоесть имеет кассовый аппарат
            else if (!$this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['PAYMENT_TYPE'])
            {
                // проверяем выбран ли период доставки
                if (!$this->baskets[$keyBasket]['DELIVERY_PERIOD'])
                {
                    $this->baskets[$keyBasket]['ACTIVE'] = 0;
                    $this->baskets[$keyBasket]['ERROR'][] = Loc::getMessage('ERROR_BASKET_2');
                }
                // проверяем наличие минимальной суммы заказа
                if ($this->baskets[$keyBasket]['MIN_SUM_ORDER'] > $this->baskets[$keyBasket]['SUM_FOOD'] )
                {
                    $this->baskets[$keyBasket]['ACTIVE'] = 0;
                    $this->baskets[$keyBasket]['ERROR'][] = Loc::getMessage('ERROR_BASKET_3') . ' '
                        . $this->baskets[$keyBasket]['MIN_SUM_ORDER'] . ' ' . Loc::getMessage('RUR') . '.';
                }
            }
            // если место доставки подразумевает оплату только в счет льгот
            else if ($this->baskets[$keyBasket]['SELECTED_DELIVERY_PLACE']['PAYMENT_TYPE'])
            {
                // если доплата при получении более нуля, тоесть не хватило льгот для погашения всей суммы корзины
                if ($this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT']>0)
                {
                    // если не выбраны дополнительные способы оплаты, то корзина становится не доступной для оформления
                    if (!(($this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE']
                        && $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['CHECKED'])
                        || ($this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE']
                            && $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT']['CHECKED'])) )
                    {
                        $this->baskets[$keyBasket]['ACTIVE'] = 0;
                        /* Целевой вид строки
                        В указанном месте доставки оплата происходит только в счет доступных
                        льгот (123 руб.). Вы превысили допустимую сумму для оплаты доступными льготами. Для оформления
                        заказа необходимо исключить из корзины товары на сумму  123 руб. */
                        $str_error = Loc::getMessage('ERROR_BASKET_4_PART_1')." ("
                            .$this->baskets[$keyBasket]['TOTAL_AMOUNT_BENEFITS']." ".Loc::getMessage('RUR').".). "
                            .Loc::getMessage('ERROR_BASKET_4_PART_2')." "
                            .$this->baskets[$keyBasket]['PAYMENT_ON_RECEIPT']
                            ." ".Loc::getMessage('RUR').".";
                        if ($this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE']
                            || $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE'])
                        {
                            $str_error .= ' '.Loc::getMessage('ERROR_BASKET_4_PART_3');
                            if ($this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE'])
                            {
                                $str_error .= ' '.Loc::getMessage('ERROR_BASKET_4_PART_4');
                            }
                            if ($this->baskets[$keyBasket]['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE']
                                && $this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE'])
                            {
                                $str_error .= ', '.Loc::getMessage('ERROR_BASKET_4_PART_5');
                            }
                            else if ($this->baskets[$keyBasket]['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE'])
                            {
                                $str_error .= ' '.Loc::getMessage('ERROR_BASKET_4_PART_5');
                            }
                        }
                        $str_error .= '.';
                        $this->baskets[$keyBasket]['ERROR'][] = $str_error;
                    }
                }
                // проверяем выбран ли период доставки
                if (!$this->baskets[$keyBasket]['DELIVERY_PERIOD'])
                {
                    $this->baskets[$keyBasket]['ACTIVE'] = 0;
                    $this->baskets[$keyBasket]['ERROR'][] = Loc::getMessage('ERROR_BASKET_2');
                }
                // проверяем наличие минимальной суммы заказа
                if ($this->baskets[$keyBasket]['MIN_SUM_ORDER'] > $this->baskets[$keyBasket]['SUM_FOOD'] )
                {
                    $this->baskets[$keyBasket]['ACTIVE'] = 0;
                    $this->baskets[$keyBasket]['ERROR'][] = Loc::getMessage('ERROR_BASKET_5') . ' '
                        . $this->baskets[$keyBasket]['MIN_SUM_ORDER'] . ' ' . Loc::getMessage('RUR') . '.';
                }
            }
        }
    }

    /**
     * Метод необходимый для получения сформированных данных по корзинам из ajax запросов, идущих в файл
     * ajax.php в этой директории
     * @params $idBaskets - id корзины
     * @return baskets array|bool - массив данных пользователя, false в случае ошибки
     */
    public function executeComponentAjax($idBaskets)
    {
        //подключаем необходимые модули
        if (!$this->checkModules()) return false;
        $user = User::getInstance();
        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        // получаем корзины
        $this->baskets = Baskets::getBasketsById(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            $idBaskets
        );
        // получаем данные пользователя
        $user = User::getInstance();
        // проверяем корзины на истечение времени их доступности
        $this->checkBasketsAccessTime();
        // если корзины не получены
        if (!$this->baskets)
        {
            return false;
        }
        // добавляем информацию о меню, на которое оформлена корзина
        $this->setBasketMenu();
        // задаем товары корзинам
        $this->setProducts();
        // задаем цены корзинам
        $this->setPrice();
        // задаем места доставки корзинам
        $this->setDeliveryPlaces();
        // задаем льготы корзинам
        $this->setFoodBenefits($user);
        // задаем спецпитание корзинам
        $this->setSpecialFood();
        // дополнительная оплата (в счет заработной платы - ПВЗП)
        $this->setAdditionPayment();
        // задаем возможные использованные суммы льгот для оплаты корзины
        $this->setUsedBenefits();
        // безналичный расчет (банковской картой)
        $this->setCashlessPayment();
        // наличный расчет при получении
        $this->setCashPayment();
        // проверяем корзины на доступность
        $this->checkBaskets($user);
        return $this->baskets;
    }

    /**
     * Метод проверяет данные пользователя для работы компонента
     * @param $user - экземпляр класса пользователя
     * @return false|object
     */
    private function validationUser($user)
    {
        if (!$user->workArea['ID'])
        {
            ShowError('Не получен id площадки.');
            return false;
        }
        else if (!$user->workArea['TIME_DISPLACEMENT'])
        {
            ShowError('Не получено смещение по времени.');
            return false;
        }
        else if (!$user->workArea['MIN_ORDER_AMOUNT'])
        {
            ShowError('Не получена минимальная сумма по площадке.');
            return false;
        }
        return $user;
    }

    /**
     * Метод добавляет в корзины информацию о меню, на которое оформлена корзина (по дате и периоду питания)
     * @return void
     */
    private function setBasketMenu()
    {
        $dates = [];
        $feedingPeriods = [];
        foreach ($this->baskets as $basket)
        {
            $dates[] = ConvertDateTime($basket['ORDER_DATE'], 'Y-m-d');
            $feedingPeriods[] = $basket['ID_FOOD_PERIOD'];
        }
        $menus = \KS\PlansMenu::getMenuByBaskets($dates, $feedingPeriods);
        foreach ($this->baskets as  &$basket)
        {
            foreach ($menus as $menu)
            {
                if ($basket['ID_FOOD_PERIOD'] == $menu['FEEDING_PERIOD_ID']
                    && strtotime($basket['ORDER_DATE']) == strtotime($menu['DATE']))
                {
                    $basket['MENU_XML_ID'] = $menu['XML_ID'];
                    $basket['MENU_ID'] = $menu['ID'];
                }
            }
        }
    }

    /**
     * Стартующий метод
     * @return mixed|void|null
     */
    public function executeComponent()
    {
        global $USER;
        // компонент работает только с авторизованными пользователями
        if (!$USER->IsAuthorized()) return false;
        // проверяем наличие шаблона компонента
        if (!$this->checkTemplate()) return false;
        //подключаем необходимые модули
        if (!$this->checkModules()) return false;
        // получаем данные пользователя
        if (!$user = $this->validationUser(User::getInstance())) return false;
        $this->arResult['USER'] = $user;
        // получаем статусы заказов
        $statuses = OrderStatuses::getInstance();
        // получаем корзины
        $this->baskets = Baskets::getBaskets(
            $statuses->getOrderStatusId('VKorzine'),
            $user->bitrixId,
            ConvertDateTime(WorkArea::getDateTimeWorkArea($user->workArea['TIME_DISPLACEMENT']), "Y-m-d", "ru")
        );
        // добавляем информацию о меню, на которое оформлена корзина
        $this->setBasketMenu();
        // проверяем корзины на истечение времени их доступности
        $this->checkBasketsAccessTime();
        // если корзины не получены
        if (!$this->baskets)
        {
            $this->includeComponentTemplate();
            return;
        }
        // задаем подсказки корзинам
        $this->setHelps();
        // задаем товары корзинам
        $this->setProducts();
        // задаем цены корзинам
        $this->setPrice();
        // задаем места доставки корзинам
        $this->setDeliveryPlaces();
        // задаем льготы корзинам
        $this->setFoodBenefits($user);
        // задаем спецпитание корзинам
        $this->setSpecialFood();
        // дополнительная оплата (в счет заработной платы - ПВЗП)
        $this->setAdditionPayment();
        // задаем возможные использованные суммы льгот для оплаты корзины
        $this->setUsedBenefits();
        // безналичный расчет (банковской картой)
        $this->setCashlessPayment();
        // наличный расчет при получении
        $this->setCashPayment();
        // проверяем корзины на доступность
        $this->checkBaskets($user);
        $this->arResult['BASKETS'] = $this->baskets;
        $this->arResult['TEMPLATE_PATH'] = $this->__template->__folder;
        $this->includeComponentTemplate();
    }
}