BX.ready(function () {

    let timeOut;
    let loader = BX('loader');

    /* обработчики на элементы DOM */
    for (let itemBasket in arResult.BASKETS)
    {
        /* делаем удобочитаемый вид */
        let basket = arResult.BASKETS[itemBasket];

        /* добавление обработчиков для товарной части корзины */
        for (let itemCategory in arResult.BASKETS[itemBasket].CATEGORIES)
        {
            for (let itemProduct in arResult.BASKETS[itemBasket].CATEGORIES[itemCategory].PRODUCTS)
            {
                let product = arResult.BASKETS[itemBasket].CATEGORIES[itemCategory].PRODUCTS[itemProduct];

                /* если произошло событие добавления количества товара в ПК версии */
                BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'js-add-count-pc-' + product.ID}}, function (e) {
                    let data = {
                        'idBasket': e.target.getAttribute('data-id-basket'),
                        'id': e.target.getAttribute('data-id'),
                    };
                    sendAjax(data, 'increaseQuantityGoods', false);
                });
                /* если произошло событие уменьшение количества товара в ПК версии */
                BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'js-decrease-count-pc-' + product.ID}}, function (e) {
                    let data = {
                        'idBasket': e.target.getAttribute('data-id-basket'),
                        'id': e.target.getAttribute('data-id'),
                    };
                    if (+BX('quantity-pc-' + data.id).value > 1)
                    {
                        sendAjax(data, 'reduceQuantityGoods', false);
                    }
                });
                /* если произошло событие добавления количества товара в мобильной версии */
                BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'js-add-count-mb-' + product.ID}}, function (e) {
                    let data = {
                        'idBasket': e.target.getAttribute('data-id-basket'),
                        'id': e.target.getAttribute('data-id'),
                    };
                    sendAjax(data, 'increaseQuantityGoods', false);
                });
                /* если произошло событие уменьшение количества товара в мобильной версии */
                BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'js-decrease-count-mb-' + product.ID}}, function (e) {
                    let data = {
                        'idBasket': e.target.getAttribute('data-id-basket'),
                        'id': e.target.getAttribute('data-id'),
                    };
                    if (+BX('quantity-mb-' + data.id).value > 1)
                    {
                        sendAjax(data, 'reduceQuantityGoods', false);
                    }
                });

                if (product.IS_COMPLECT)
                {
                    // развернуть, свернуть комплект
                    BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'show-lunch-pc-' + product.ID}}, function (e) {
                        e.preventDefault();
                        BX.hide(BX('show-lunch-pc-' + product.ID));
                        BX.show(BX('lunch-products-pc-' + product.ID));
                        BX.show(BX('hide-lunch-pc-' + product.ID));
                    });
                    BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'hide-lunch-pc-' + product.ID}}, function (e) {
                        e.preventDefault();
                        BX.show(BX('show-lunch-pc-' + product.ID));
                        BX.hide(BX('lunch-products-pc-' + product.ID));
                        BX.hide(BX('hide-lunch-pc-' + product.ID));
                    });
                }

                /* удаление товара из корзины в ПК версии */
                BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'remove-product-pc-' + product.ID}}, function (e) {
                    e.preventDefault();
                    let data = {
                        'idBasket': e.target.getAttribute('data-id-basket'),
                        'id': e.target.getAttribute('data-id'),
                    };
                    sendAjax(data, 'removeProduct', false);
                });
                /* удаление товара из корзины в мобильной версии */
                BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'remove-product-mb-' + product.ID}}, function (e) {
                    e.preventDefault();
                    let data = {
                        'idBasket': e.target.getAttribute('data-id-basket'),
                        'id': e.target.getAttribute('data-id'),
                    };
                    sendAjax(data, 'removeProduct', false);
                });

            }
        }

        /* обработчик выбора спецпитания */
        BX.bindDelegate(BX('js-baskets'), 'change', {attribute: {'id': 'special-food-select-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
                'value': e.target.value,
            };
            sendAjax(data, 'updateSpecialFood', false);
        });

        /* если был закончен ввод данных в текстовое поле комментария, то отсчитываем 2 секунды и сохраняем */
        BX.bindDelegate(BX('js-baskets'), 'keyup', {attribute: {'id': 'js-basket-comment-' + basket.ID}}, function (e) {
            /* очищаем переменную времени */
            clearTimeout(timeOut);
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
                'value': e.target.value,
            };
            /* сохраняем время */
            timeOut = setTimeout(sendAjax(data, 'updateComment', true), 2000);
        });
        /* если начался ввод данных в текстовое поле комментариев, то сбрасываем оставшееся время до отправки данных*/
        BX.bindDelegate(BX('js-baskets'), 'keydown', {attribute: {'id': 'js-basket-comment-' + basket.ID}}, function (e) {
            /* очищаем переменную времени */
            clearTimeout(timeOut);
        });

        /* обработчик на выбор периода доставки */
        BX.bindDelegate(BX('js-baskets'), 'change', {attribute: {'id': 'js-delivery-period-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
                'idDeliveryPlace': e.target.getAttribute('data-id-delivery-place'),
                'value': e.target.value,
            };
            sendAjax(data, 'updateDeliveryPeriod', false);
        });

        /* обработчик на выбор места доставки */
        BX.bindDelegate(BX('js-baskets'), 'change', {attribute: {'id': 'js-delivery-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
                'value': e.target.value,
            };
            sendAjax(data, 'updateDeliveryPlace', false);
        });

        /* обработчик удаления корзины */
        BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'remove-basket-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
            };
            sendAjax(data, 'removeBasket', false);
        });

        /* обработчик выбора дополнительго способа оплаты "Использовать ПВЗП" */
        BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'addition-payment-input-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
            };
            sendAjax(data, 'additionPayment', false);
        });

        /* обработчик выбора дополнительго способа оплаты "Оплата банковской картой при получении" */
        BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'cashless-payment-input-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
            };
            sendAjax(data, 'cashlessPayment', false);
        });

        /* обработчик выбора дополнительго способа оплаты "Оплата банковской картой при получении" */
        BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'cash-payment-input-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
            };
            sendAjax(data, 'cashPayment', false);
        });

        /* обработчик создания заказа из корзины */
        BX.bindDelegate(BX('js-baskets'), 'click', {attribute: {'id': 'make-order-' + basket.ID}}, function (e) {
            let data = {
                'idBasket': e.target.getAttribute('data-id-basket'),
            };
            sendAjax(data, 'makeOrder', false);
        });
    }

    /* метод для отправки ajax запросов*/
    function sendAjax(date, nameMethods, quickQuery) {
        if (!quickQuery)
        {
            BX.show(BX('loader'));
        }
        BX.ajax.runComponentAction('it-group:basket',
            // имя метода-обработчика пишется без суффикса Action
            nameMethods, {
                mode: 'ajax',
                // ключи объекта data соответствуют параметрам метода-обработчика
                data: {
                    sessid: BX.bitrix_sessid(),
                    post: date
                }
            })
            .then(function (response) {
                    if (typeof response.data.removeBasketId != 'undefined')
                    {
                        response.data.removeBasketId.forEach(function (itemIdBasket, i, arr) {
                            BX.remove(BX("basket-" + itemIdBasket));
                            delete arResult.BASKETS[itemIdBasket];
                        });
                    }

                    if (typeof response.data.MAKE_ORDER != 'undefined')
                    {
                        let orderId = response.data.MAKE_ORDER[0];
                        let orderNum = BX.findChild(
                            BX('status-order-' + orderId),
                            {
                                'class': 'cart_items_number_order_' + orderId
                            },
                            true
                        );
                        orderNum.innerText = response.data.ORDER_NUM;
                        response.data.MAKE_ORDER.forEach(function (itemIdOrder, i, arr) {
                            BX.hide(BX('item-basket-' + itemIdOrder));
                            BX.show(BX('status-order-' + itemIdOrder));
                        });
                    }

                    if (Object.keys(arResult.BASKETS).length <= 0)
                    {
                        // если корзин для обработки не осталось
                        BX.show(BX('no-basket-block'));
                        BX.hide(BX('title-basket'));
                    }

                    /* если получены ошибки по обработке корзин */
                    if (typeof response.data.error != 'undefined')
                    {
                        alert(response.data.error);
                        BX.hide(BX('loader'));
                        return;
                    }

                    /* если получены данные по корзинам */
                    if (typeof response.data.baskets != 'undefined')
                    {
                        updateBaskets(response.data.baskets);
                    }

                    BX.hide(BX('loader'));
                }
            );
    }

    function updateBaskets(baskets) {
        for (let item in baskets)
        {
            /* обрабатываем пришедшие ошибки */
            BX.cleanNode(BX("js-error-" + item));
            if (typeof baskets[item].ERROR != 'undefined')
            {
                baskets[item].ERROR.forEach(function (itemError, i, arr) {
                    BX.append(BX.create('p', {'text': itemError}), BX("js-error-" + item));
                });
            }

            /* обрабатываем спецпитание */
            BX.cleanNode(BX("special-food-select-" + item));
            if (baskets[item].SPECIAL_FOOD_APPOINTED && baskets[item].SPECIAL_FOOD !== undefined)
            {
                BX.removeClass(BX("special-food-" + item), 'hide');
                BX.append(
                    BX.create(
                        'option',
                        {
                            'text': 'Спецпитание не выбрано',
                            'props': {
                                'value': 0,
                            }
                        }),
                    BX("special-food-select-" + item)
                );
                let specialFoods = baskets[item].SPECIAL_FOOD;
                for (let specialFood in specialFoods)
                {
                    let props = {
                        'value': specialFoods[specialFood]['PLAN_MENU_PRODUCTS']['ID'],
                    };
                    if (typeof baskets[item].SELECTED_SPECIAL_FOOD != 'undefined')
                    {
                        if (baskets[item]['SELECTED_SPECIAL_FOOD']['PLAN_MENU_PRODUCTS']['ID']
                            == specialFoods[specialFood]['PLAN_MENU_PRODUCTS']['ID'])
                        {
                            props['selected'] = true;
                        }
                    }
                    BX.append(
                        BX.create(
                            'option',
                            {
                                'text': specialFoods[specialFood]['SHOW_NAME'],
                                'props': props
                            }),
                        BX("special-food-select-" + item)
                    );
                }
            }
            else
            {
                BX.addClass(
                    BX("special-food-" + item),
                    'hide'
                );
            }

            /* изменение общей цены товаров собственного производства */
            BX.adjust(
                BX("sum-own-product-" + item),
                {
                    'text': baskets[item].SUM_OWN_FOOD,
                }
            );

            /* обрабатываем рекомендации */
            BX.cleanNode(BX("basket-recomendation-" + item));
            if (typeof baskets[item].RECOMMENDATION != 'undefined')
            {
                baskets[item].RECOMMENDATION.forEach(function (itemRecommendation, i, arr) {
                    BX.append(
                        BX.create(
                            'p',
                            {
                                'html': '<span>!</span>' + itemRecommendation,
                            }),
                        BX("basket-recomendation-" + item)
                    );
                });
            }

            /* обрабатываем льготы */
            BX.cleanNode(BX("food-benefits-" + item));
            if (typeof baskets[item].FOOD_BENEFITS != 'undefined')
            {
                let foodBenefits = [];
                let count = 0;
                for (let itemBenefit in baskets[item].FOOD_BENEFITS)
                {
                    ++count;
                    foodBenefits.push(baskets[item].FOOD_BENEFITS[itemBenefit])
                }
                for (let i = 0; i < count; i++)
                {
                    for (let j = 0; j < count - 1; j++)
                    {
                        if (+foodBenefits[j].SORT > +foodBenefits[j + 1].SORT)
                        {
                            let buf = foodBenefits[j];
                            foodBenefits[j] = foodBenefits[j + 1];
                            foodBenefits[j + 1] = buf;
                        }
                    }
                }
                foodBenefits.forEach(function (itemBenefit, i, arr) {
                    BX.append(
                        BX.create(
                            'p',
                            {
                                props: {
                                    className: 'item_date_cart_lgoty_item'
                                        + (itemBenefit.ACTIVE ? '' : ' opacity_06'),
                                },
                                children: [
                                    BX.create(
                                        'span',
                                        {
                                            props: {
                                                className: 'desc_ing  js_show_help',
                                                title: (itemBenefit.DESC) ? itemBenefit.DESC : ''
                                            },
                                            text: '?',
                                            style: {
                                                'display': (itemBenefit.DESC) ? '' : 'none'
                                            },
                                        }
                                    ),
                                    BX.create(
                                        'span',
                                        {
                                            text: ((itemBenefit.FULL_NAME) ? itemBenefit.FULL_NAME : itemBenefit.NAME)
                                                + ': использовано ' + itemBenefit.DATA_SHOW.USE
                                                + ' ' + itemBenefit.MEASURE + ' из ' + itemBenefit.DATA_SHOW.REST_BALANCE
                                                + ' ' + itemBenefit.MEASURE,
                                        }
                                    ),
                                ],

                            }),
                        BX("food-benefits-" + item)
                    );
                    BX.append(
                        BX.create(
                            'div',
                            {
                                props: {
                                    className: 'pos_progress_bar' + (itemBenefit.ACTIVE ? '' : ' opacity_06'),
                                },
                                children: [
                                    BX.create(
                                        'div',
                                        {
                                            props: {
                                                className: 'progress_bar' + (itemBenefit.ACTIVE ? '' : ' opacity_06'),
                                            },
                                            children: [
                                                BX.create(
                                                    'div',
                                                    {
                                                        props: {
                                                            className: 'green_progress_bar',
                                                        },
                                                        style: {
                                                            'width': itemBenefit.DATA_SHOW.PROGRESS_REST + '%'
                                                        },
                                                    }
                                                ),
                                                BX.create(
                                                    'div',
                                                    {
                                                        props: {
                                                            className: 'red_progress_bar',
                                                        },
                                                        style: {
                                                            'width': itemBenefit.DATA_SHOW.PROGRESS_USE + '%'
                                                        },
                                                    }
                                                ),
                                            ],
                                        }
                                    ),
                                ],
                            }
                        ),
                        BX("food-benefits-" + item)
                    );
                });
            }

            /* удаление товара в корзине */
            if (typeof baskets[item].removeProductId != 'undefined')
            {
                baskets[item].removeProductId.forEach(function (itemProduct, i, arr) {
                    BX.remove(BX("product-item-pc-" + itemProduct));
                    BX.remove(BX("product-item-mb-" + itemProduct));
                });
            }

            /* обрабатываем периоды доставки */
            BX.cleanNode(BX("js-delivery-period-" + item));
            if (typeof baskets[item].SELECTED_DELIVERY_PLACE != 'undefined')
            {
                BX.removeClass(BX("js-delivery-period-" + item), 'hide');
                BX.adjust(
                    BX("js-delivery-period-" + item),
                    {
                        'attrs': {
                            'data-id-delivery-place': baskets[item].SELECTED_DELIVERY_PLACE.ID,
                        }
                    }
                );
                BX.append(
                    BX.create(
                        'option',
                        {
                            'text': 'Время доставки не выбрано',
                            'props': {
                                'value': 0,
                            }
                        }),
                    BX("js-delivery-period-" + item)
                );
                let schedule = baskets[item].SELECTED_DELIVERY_PLACE.SCHEDULE;
                for (let itemSchedule in schedule)
                {
                    let props = {
                        'value': schedule[itemSchedule]['ID'],
                    };
                    if (typeof schedule[itemSchedule]['SELECTED'] != 'undefined')
                    {
                        props['selected'] = true;
                    }
                    BX.append(
                        BX.create(
                            'option',
                            {
                                'text': schedule[itemSchedule]['STR_TIME'],
                                'props': props
                            }),
                        BX("js-delivery-period-" + item)
                    );
                }
            }
            else
            {
                BX.addClass(
                    BX("js-delivery-period-" + item),
                    'hide'
                );
            }

            /* обрабатываем количество товаров */
            if (typeof baskets[item].CATEGORIES !== undefined)
            {
                let categories = baskets[item].CATEGORIES;
                for (let itemCategory in categories)
                {
                    categories[itemCategory].PRODUCTS.forEach(function (itemProduct, i, arr) {
                        BX.adjust(
                            BX("quantity-pc-" + itemProduct.ID),
                            {
                                'attrs': {
                                    'value': itemProduct.COUNT,
                                }
                            }
                        );
                        BX.adjust(
                            BX("quantity-mb-" + itemProduct.ID),
                            {
                                'attrs': {
                                    'value': itemProduct.COUNT,
                                }
                            }
                        );
                        BX.adjust(
                            BX("sum-item-product-pc-" + itemProduct.ID),
                            {
                                'text': itemProduct.SUM,
                            }
                        );
                        BX.adjust(
                            BX("sum-item-product-mb-" + itemProduct.ID),
                            {
                                'text': itemProduct.SUM,
                            }
                        );
                    });
                }
            }

            /* дополнительный способ оплаты "Использовать ПВЗП" */
            BX.cleanNode(BX("addition-payment-mes-" + item));
            if (baskets[item].PAYMENT_METHODS.ADDITION_PAY['ACTIVE'])
            {
                BX.adjust(
                    BX("addition-payment-input-" + item),
                    {
                        'props': {
                            'disabled': false,
                            'checked': baskets[item].PAYMENT_METHODS.ADDITION_PAY['CHECKED'] ? true : false,
                        }
                    }
                );
                BX.removeClass(BX("addition-payment-name-" + item), 'addition_setting_span_no_active');
                BX.adjust(
                    BX("addition-payment-mes-" + item),
                    {
                        'text': '',
                    }
                );
            }
            else
            {
                BX.adjust(
                    BX("addition-payment-input-" + item),
                    {
                        'props': {
                            'disabled': true,
                            'checked': false,
                        }
                    }
                );
                BX.addClass(BX("addition-payment-name-" + item), 'addition_setting_span_no_active');
                BX.append(
                    BX.create(
                        'p',
                        {
                            'text': baskets[item].PAYMENT_METHODS.ADDITION_PAY['MESSAGE'],
                        }),
                    BX("addition-payment-mes-" + item)
                );
            }

            /* дополнительный способ оплаты "Оплата банковской картой при получении" */
            BX.cleanNode(BX("cashless-payment-mes-" + item));
            if (baskets[item].PAYMENT_METHODS.CASHLESS_PAYMENT['ACTIVE'])
            {
                BX.adjust(
                    BX("cashless-payment-input-" + item),
                    {
                        'props': {
                            'disabled': false,
                            'checked': baskets[item].PAYMENT_METHODS.CASHLESS_PAYMENT['CHECKED'] ? true : false,
                        }
                    }
                );
                BX.removeClass(BX("cashless-payment-name-" + item), 'addition_setting_span_no_active');
            }
            else
            {
                BX.adjust(
                    BX("cashless-payment-input-" + item),
                    {
                        'props': {
                            'disabled': true,
                            'checked': false,
                        }
                    }
                );
                BX.addClass(BX("cashless-payment-name-" + item), 'addition_setting_span_no_active');
                BX.append(
                    BX.create(
                        'p',
                        {
                            'text': baskets[item].PAYMENT_METHODS.CASHLESS_PAYMENT['MESSAGE'],
                        }),
                    BX("cashless-payment-mes-" + item)
                );
            }

            /* дополнительный способ оплаты "Оплата за наличный расчет при получении" */
            BX.cleanNode(BX("cash-payment-mes-" + item));
            if (baskets[item].PAYMENT_METHODS.CASH_PAYMENT['ACTIVE'])
            {
                BX.adjust(
                    BX("cash-payment-input-" + item),
                    {
                        'props': {
                            'disabled': false,
                            'checked': baskets[item].PAYMENT_METHODS.CASH_PAYMENT['CHECKED'] ? true : false,
                        }
                    }
                );
                BX.removeClass(BX("cash-payment-name-" + item), 'addition_setting_span_no_active');
            }
            else
            {
                BX.adjust(
                    BX("cash-payment-input-" + item),
                    {
                        'props': {
                            'disabled': true,
                            'checked': false,
                        }
                    }
                );
                BX.addClass(BX("cash-payment-name-" + item), 'addition_setting_span_no_active');
                BX.append(
                    BX.create(
                        'p',
                        {
                            'text': baskets[item].PAYMENT_METHODS.CASH_PAYMENT['MESSAGE'],
                        }),
                    BX("cash-payment-mes-" + item)
                );
            }

            /* обновляем итоговую сумму корзины */
            BX.adjust(
                BX("sum-food-" + item),
                {
                    'text': baskets[item].SUM_FOOD,
                }
            );

            /* обновляем сумму оплаченную льготами */
            BX.adjust(
                BX("paid-with-benefits-" + item),
                {
                    'text': baskets[item].PAID_WITH_BENEFITS,
                }
            );

            /* обновляем сумму необходимую для оплаты при получении */
            BX.adjust(
                BX("payment-on-receipt-" + item),
                {
                    'text': baskets[item].PAYMENT_ON_RECEIPT,
                }
            );

            /* обрабатываем возможность оформить заказ */
            if (baskets[item].ACTIVE)
            {
                BX.adjust(
                    BX("make-order-" + item),
                    {
                        'props': {
                            'disabled': false,
                        }
                    }
                );
                BX.removeClass(
                    BX("make-order-" + item),
                    'disabled'
                );
            }
            else
            {
                BX.adjust(
                    BX("make-order-" + item),
                    {
                        'props': {
                            'disabled': true,
                        }
                    }
                );
                BX.addClass(
                    BX("make-order-" + item),
                    'disabled'
                );
            }
        }
    }
});