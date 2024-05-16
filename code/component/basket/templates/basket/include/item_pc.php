<?php use Bitrix\Main\Localization\Loc; ?>
<div id="product-item-pc-<?= $itemProduct['ID'] ?>" class="cart_item cart_item_<?= $itemProduct['ID'] ?> row pc">
    <div class="col-1 cart_item_number">
        <?= $count ?>
    </div>
    <div class="col-1 cart_item_img">
        <?php if (!empty($itemProduct['NOMENKLATURA']['PREVIEW_PICTURE'])): ?>
            <img src="<?= CFile::GetPath($itemProduct['NOMENKLATURA']['PREVIEW_PICTURE']); ?>" alt="img">
        <?php elseif (!empty($itemCategory['PREVIEW_PICTURE'])): ?>
            <img src="<?= CFile::GetPath($itemCategory['PREVIEW_PICTURE']); ?>" alt="img">
        <?php else: ?>
            <img src="/img/default_bludo.png" alt="img">
        <?php endif; ?>
    </div>
    <div class="col-5 cart_item_name">
        <p><?= $itemProduct['NAME'] ?></p>
        <?php if ($itemProduct['NOMENKLATURA']['TYPE_PRODUCT'] == 'Блюдо'): ?>
            <img class="povar_korzina" src="/img/povar.png" alt="povar" title="<?= Loc::getMessage('OWN_FOOD') ?>">
        <?php endif; ?>
    </div>
    <div class="col-2 cart_item_count">
        <div class="count_product">
            <input id="js-decrease-count-pc-<?= $itemProduct['ID'] ?>" class="count_product_minus"
                   data-id-basket="<?= $itemBasket['ID'] ?>"
                   data-id="<?= $itemProduct['ID'] ?>" type="button" value="-">
            <input id="quantity-pc-<?= $itemProduct['ID'] ?>" class="count_product_number" type="text"
                   value="<?= $itemProduct['COUNT'] ?>"
                   disabled>
            <input id="js-add-count-pc-<?= $itemProduct['ID'] ?>" class="count_product_plus"
                   data-id-basket="<?= $itemBasket['ID'] ?>"
                   data-id="<?= $itemProduct['ID'] ?>" type="button" value="+">
        </div>
    </div>
    <div class="col-2 cart_item_price">
        <div class="text-right">
            <span id="sum-item-product-pc-<?= $itemProduct['ID'] ?>"
                  class="cart_item_price_sum cart_item_price_sum_<?= $itemProduct['ID'] ?>">
                <?= $itemProduct['SUM'] ?>
            </span> <?= Loc::getMessage('RUR') ?>.
        </div>
    </div>
    <div class="col-1 cart_item_remove">
        <a id="remove-product-pc-<?= $itemProduct['ID'] ?>" data-id-basket="<?= $itemBasket['ID'] ?>"
           data-id="<?= $itemProduct['ID'] ?>"
           href="#">X</a>
    </div>
</div>