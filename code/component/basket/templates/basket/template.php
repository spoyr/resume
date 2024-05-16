<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * @var $arResult - массив даннных, результрат работы компонента
 */
use Bitrix\Main\Localization\Loc;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 back_button_top pc">
            <a class="show_all_orders" href="/"><< <?= Loc::getMessage('BACK_BUTTON') ?></a>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php // *START* участок кода необходимый для показа при удалении всех корзин, изначально скрыт ?>
        <div id="no-basket-block" class="col-12 cart_not_have_item cart_not_have_item_mobile">
            <h1 class="title_cart_not_item"><?= Loc::getMessage('NO_ACTUAL_BASKET') ?></h1>
            <p class="title_cart_p"><?= Loc::getMessage('CHOOSE_PRODUCT_PART_1') ?>
                <a href="/"> <?= Loc::getMessage('CHOOSE_PRODUCT_PART_2') ?></a>
            </p>
        </div>
        <?php // *END* участок кода необходимый для показа при удалении всех корзин, изначально скрыт ?>
        <?php if (is_null($arResult['BASKETS']) || count($arResult['BASKETS']) <= 0): ?>
            <div class="col-12 cart_not_have_item_mobile">
                <h1 class="title_cart_not_item"><?= Loc::getMessage('NO_ACTUAL_BASKET') ?></h1>
                <p class="title_cart_p"><?= Loc::getMessage('CHOOSE_PRODUCT_PART_1') ?>
                    <a href="/"> <?= Loc::getMessage('CHOOSE_PRODUCT_PART_2') ?></a>
                </p>
            </div>
        <?php else: ?>
            <div id="title-basket" class="col-12">
                <h1 class="title_cart"><?= Loc::getMessage('BASKETS') ?></h1>
            </div>
            <div id="js-baskets" class="col-12 js-input-ajax">
                <?php foreach ($arResult['BASKETS'] as $itemBasket): ?>
                    <div id="basket-<?= $itemBasket['ID'] ?>"
                         class="item_date_cart item_date_cart_<?= $itemBasket['ID'] ?>"
                         data-id_cart="<?= $itemBasket['ID'] ?>">
                        <h2><?= $itemBasket['WORKAREA_NAME'] . ' ' . $itemBasket['ORDER_DATE'] . ' ' . $itemBasket['PERIOD_NAME'] ?> <span
                                    class="desc_ing_title_cart js_show_help"
                                    title="<?= $itemBasket['TIPS']['tip_for_basket_header']['DETAIL_TEXT'] ?>">?</span>
                        </h2>
                        <?php // *START* участок кода необходимый для показа при оформлении корзины ?>
                        <div id="status-order-<?= $itemBasket['ID'] ?>" class="status_zakaza">
                            <p>
                                <?= Loc::getMessage('LINK_SEE_ORDER_PART_1') ?>
                                <a href="/orders/"><?= Loc::getMessage('LINK_SEE_ORDER_PART_2') ?></a>
                            </p>
                            <p><?= Loc::getMessage('NUM_ORDER') ?>:
                                <span class="cart_items_number_order_<?= $itemBasket['ID'] ?>"></span>
                            </p>
                            <a class="show_all_orders" href="/korzina/print.php?id=<?= $itemBasket['ID'] ?>" target="_blank">
                                <?= Loc::getMessage('PRINT_ORDER') ?>
                            </a>
                            <?php // Уведомление о передаче заказа оператору в течение часа ?>
                            <div class="alert alert-warning border border-primary mt-3" role="alert">
                                    <svg class="svg-icon" width="20px" height="20px" viewBox="0 0 20 20">
                                        <path d="M10.219,1.688c-4.471,0-8.094,3.623-8.094,8.094s3.623,8.094,8.094,8.094s8.094-3.623,8.094-8.094S14.689,1.688,10.219,1.688 M10.219,17.022c-3.994,0-7.242-3.247-7.242-7.241c0-3.994,3.248-7.242,7.242-7.242c3.994,0,7.241,3.248,7.241,7.242C17.46,13.775,14.213,17.022,10.219,17.022 M15.099,7.03c-0.167-0.167-0.438-0.167-0.604,0.002L9.062,12.48l-2.269-2.277c-0.166-0.167-0.437-0.167-0.603,0c-0.166,0.166-0.168,0.437-0.002,0.603l2.573,2.578c0.079,0.08,0.188,0.125,0.3,0.125s0.222-0.045,0.303-0.125l5.736-5.751C15.268,7.466,15.265,7.196,15.099,7.03"></path>
                                    </svg>
                                    <?= Loc::getMessage('ORDER_NOTICE') ?>
                            </div>
                        </div>
                        <?php // *END* участок кода необходимый для показа при оформлении корзины ?>
                        <div id="item-basket-<?= $itemBasket['ID'] ?>" class="cart_items">
                            <?php $count = 1; ?>
                            <?php foreach ($itemBasket['CATEGORIES'] as $itemCategory): ?>
                                <?php foreach ($itemCategory['PRODUCTS'] as $itemProduct): ?>
                                    <?php if ($itemProduct['IS_COMPLECT']): ?>
                                        <?php include(__DIR__ . "/include/item_lunch_pc.php"); ?>
                                        <?php include(__DIR__ . '/include/item_lunch_mobile.php'); ?>
                                    <?php else: ?>
                                        <?php include(__DIR__ . "/include/item_pc.php"); ?>
                                        <?php include(__DIR__ . '/include/item_mobile.php'); ?>
                                    <?php endif; ?>
                                    <?php $count++; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <p class="bludo_l">
                                <span class="icon_bludo_css"></span>
                                <?= Loc::getMessage('SUM_OWN_FOOD') ?> :
                                <span id="sum-own-product-<?= $itemBasket['ID'] ?>"
                                      class="item_date_cart_itogo_bludo_<?= $itemBasket['ID'] ?>">
                                    <?= $itemBasket['SUM_OWN_FOOD'] ?>
                                </span>
                                <?= Loc::getMessage('RUR') ?>.
                            </p>
                            <div class="clearfix"></div>
                            <input id="remove-basket-<?= $itemBasket['ID'] ?>"
                                   class="js_icon_remove_cart remove_cart_button"
                                   type="button" value="Удалить корзину"
                                   data-id-basket="<?= $itemBasket['ID'] ?>">
                            <textarea id="js-basket-comment-<?= $itemBasket['ID'] ?>" rows="2"
                                      placeholder="<?= Loc::getMessage('COMMENT') ?>"
                                      class="cart_items_comment"
                                      data-id-basket="<?= $itemBasket['ID'] ?>"><?= $itemBasket['COMMENT']['TEXT'] ?></textarea>
                            <div id="basket-recomendation-<?= $itemBasket['ID'] ?>"
                                 class="js_cart_recomendation_<?= $itemBasket['ID'] ?> cart_recomendation">
                                <?php foreach ($itemBasket['RECOMMENDATION'] as $itemRecommendation): ?>
                                    <p><span>!</span><?= $itemRecommendation ?></p>
                                <?php endforeach; ?>
                            </div>
                            <div class="clearfix"></div>
                            <?php require __DIR__.'/include/delivery_and_delivery-period.php'; ?>
                            <?php require __DIR__.'/include/special-food.php'; ?>
                            <div class="clearfix"></div>
                            <?php require __DIR__.'/include/food-benefits.php'; ?>
                            <?php require __DIR__.'/include/payment_methods.php'?>
                            <div class="cart_items_itog_sum">
                                <p><?= Loc::getMessage('TOTAL') ?>:
                                    <span id="sum-food-<?= $itemBasket['ID'] ?>">
                                        <?= $itemBasket['SUM_FOOD'] ?>
                                    </span>
                                    <?= Loc::getMessage('RUR') ?>.
                                </p>
                                <div class="clearfix"></div>
                                <p><?= Loc::getMessage('PAID') ?>:
                                    <span id="paid-with-benefits-<?= $itemBasket['ID'] ?>">
                                        <?= $itemBasket['PAID_WITH_BENEFITS'] ?>
                                    </span>
                                    <?= Loc::getMessage('RUR') ?>.
                                </p>
                                <div class="clearfix"></div>
                                <p class="doplata_pri_polychenii"><?= Loc::getMessage('PAYMENT_ON_RECEIPT') ?>:
                                    <span id="payment-on-receipt-<?= $itemBasket['ID'] ?>">
                                        <?= $itemBasket['PAYMENT_ON_RECEIPT'] ?>
                                    </span>
                                    <?= Loc::getMessage('RUR') ?>.
                                </p>
                            </div>
                            <div class="clearfix"></div>
                            <input id="make-order-<?= $itemBasket['ID'] ?>"
                                   class="add_order <?= !$itemBasket['ACTIVE'] ? 'disabled' : '' ?>"
                                   type="button" value="Оформить заказ" data-id-basket="<?= $itemBasket['ID'] ?>"
                                   <?= !$itemBasket['ACTIVE'] ? 'disabled' : '' ?>>
                            <div class="clearfix"></div>
                            <div id="js-error-<?= $itemBasket['ID'] ?>" class="error-basket">
                                <?php if (isset($itemBasket['ERROR'])) { ?>
                                    <?php foreach ($itemBasket['ERROR'] as $itemError) { ?>
                                        <p><?= $itemError ?></p>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                            <?php $APPLICATION->IncludeComponent(
                                "it-group:cart.warning",
                                ".default",
                                array(
                                    "BEFORE_TIME" => array(
                                        0 => "30",
                                        1 => "60",
                                        2 => "",
                                    ),
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "SHOW_POPAP" => "N",
                                    "ADD_STYLE" => "text-align: right; color:red;",
                                    "PERIOD_CHECK_FOR_POPAP" => "600000",
                                    "ID_CART" => $itemBasket['ID'],
                                    "CHECK_REAL_TIME" => "Y"
                                ),
                                false
                            ); ?>
                            <?= GetMessagePriOtmeneZakaza($USER->GetID(), $itemBasket['PROPERTY_DATA_ZAKAZA_VALUE'],
                                'color: red; text-align: right; margin-top: 10px;', $arResult['TIME_DISPLACEMENT']); ?>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php CJSCore::Init(['ajax']);
?>
<script type="text/javascript">
    let arResult = <?=json_encode($arResult)?>;
</script>
