<?php use Bitrix\Main\Localization\Loc; ?>
<div id="product-item-mb-<?= $itemProduct['ID'] ?>" class="cart_item cart_item_<?= $itemProduct['ID'] ?> mobile">
    <div class="js_cart_item_detail_show cart_item_detail_show row" data_id_element="<?= $itemProduct['ID'] ?>">
        <div class="col-1 cart_item_number">
            <?= $count ?>
        </div>
        <div class="col-2 cart_item_img">
            <?php if (!empty($itemProduct['NOMENKLATURA']['PREVIEW_PICTURE'])): ?>
                <img src="<?= CFile::GetPath($itemProduct['NOMENKLATURA']['PREVIEW_PICTURE']); ?>" alt="img">
            <?php elseif (!empty($itemCategory['PREVIEW_PICTURE'])): ?>
                <img src="<?= CFile::GetPath($itemCategory['PREVIEW_PICTURE']); ?>" alt="img">
            <?php else: ?>
                <img src="/img/default_bludo.png" alt="img">
            <?php endif; ?>
        </div>
        <div class="col-6 cart_item_name">
            <p><?= $itemProduct['NAME'] ?></p>
            <?php if ($itemProduct['NOMENKLATURA']['TYPE_PRODUCT'] == 'Блюдо'): ?>
                <img class="povar_korzina" src="/img/povar.png" alt="povar" title="<?= Loc::getMessage('OWN_FOOD') ?>">
            <?php endif; ?>
        </div>
        <div class="col-2 cart_item_arrow">
            <img class="js_item_arrow js_cart_item_arrow_<?= $itemProduct['ID'] ?>"
                 src="/img/arrow_tovar_cart_mobile.png" alt="arrow_tovar_cart_mobile">
        </div>
    </div>
    <div class="col-12 container_cart_item_detail_show js_container_cart_item_detail_show_<?= $itemProduct['ID'] ?>">
        <div class="col-12 cart_price_mobile">
            <span id="sum-item-product-mb-<?= $itemProduct['ID'] ?>"
                  class="cart_item_price_sum cart_item_price_sum_<?= $itemProduct['ID'] ?>">
                <?= $itemProduct['PRICE'] * $itemProduct['COUNT'] ?>
            </span> <?= Loc::getMessage('RUR') ?>.
        </div>
        <div class="col-12 cart_item_count">
            <div class="count_product">
                <input id="js-decrease-count-mb-<?= $itemProduct['ID'] ?>" class="count_product_minus"
                       data-id-basket="<?= $itemBasket['ID'] ?>"
                       data-id="<?= $itemProduct['ID'] ?>" type="button" value="-">
                <input id="quantity-mb-<?= $itemProduct['ID'] ?>" class="count_product_number" type="text"
                       value="<?= $itemProduct['COUNT'] ?>" disabled>
                <input id="js-add-count-mb-<?= $itemProduct['ID'] ?>" class="count_product_plus"
                       data-id-basket="<?= $itemBasket['ID'] ?>" data-id="<?= $itemProduct['ID'] ?>" type="button"
                       value="+">
            </div>
            <input class="cart_item_price" hidden type="number" value="<?= $itemProduct['PRICE'] ?>">
        </div>
        <div class="row container_cart_item_detail_button">
            <div class="col-12">
                <a id="remove-product-mb-<?= $itemProduct['ID'] ?>" class="btn_remove_tovar"
                   data-id-basket="<?= $itemBasket['ID'] ?>"
                   data-id="<?= $itemProduct['ID'] ?>"
                   href="#"><?= Loc::getMessage('REMOVE') ?></a>
            </div>
        </div>
    </div>
</div>
