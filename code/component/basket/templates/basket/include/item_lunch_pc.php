<?php use Bitrix\Main\Localization\Loc; ?>
<div id="product-item-pc-<?= $itemProduct['ID'] ?>" class="pc cart_item cart_item_<?= $itemProduct['ID'] ?> row
    <?php if (is_countable($itemProduct['ERROR']) && count($itemProduct['ERROR']) > 0) { echo 'bg_warning_in_cart'; } ?>">
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
        <p><?= $itemProduct['NOMENKLATURA']['NAME'] ?></p>
        <?php if ($itemProduct['NOMENKLATURA']['TYPE_PRODUCT'] == 'Блюдо'): ?>
            <img class="povar_korzina" src="/img/povar.png" alt="povar" title="<?= Loc::getMessage('OWN_FOOD') ?>">
        <?php endif; ?>
        <a href="#" id="show-lunch-pc-<?= $itemProduct['ID'] ?>"
           class="show_complect">
            <?= Loc::getMessage('SHOW') ?>
        </a>
        <a href="#" id="hide-lunch-pc-<?= $itemProduct['ID'] ?>" class="close_complect">
            <?= Loc::getMessage('HIDE') ?>
        </a>
    </div>
    <div class="col-2 cart_item_count">
        <div class="count_product">
            <input id="js-decrease-count-pc-<?= $itemProduct['ID'] ?>" class="count_product_minus"
                   data-id-basket="<?= $itemBasket['ID'] ?>"
                   data-id="<?= $itemProduct['ID'] ?>" type="button" value="-">
            <input id="quantity-pc-<?= $itemProduct['ID'] ?>" class="count_product_number" type="text"
                   value="<?= $itemProduct['COUNT'] ?>" disabled>
            <input id="js-add-count-pc-<?= $itemProduct['ID'] ?>" class="count_product_plus"
                   data-id-basket="<?= $itemBasket['ID'] ?>"
                   data-id="<?= $itemProduct['ID'] ?>" type="button" value="+">
        </div>
        <a class="show_all_orders" href="/komplect/?id=<?= $itemProduct['ID_PLAN_MENU_TOVAR'] ?>"><?= Loc::getMessage('UPDATE') ?></a>
    </div>
    <div class="col-2 cart_item_price">
        <div class="text-right">
            <span id="sum-item-product-pc-<?= $itemProduct['ID'] ?>"
                  class="cart_item_price_sum cart_item_price_sum_<?= $itemProduct['ID'] ?>">
                <?= $itemProduct['PRICE'] * $itemProduct['COUNT'] ?>
            </span> <?= Loc::getMessage('RUR') ?>.
        </div>
    </div>
    <div class="col-1 cart_item_remove">
        <a id="remove-product-pc-<?= $itemProduct['ID'] ?>" data-id-basket="<?= $itemBasket['ID'] ?>"
           data-id="<?= $itemProduct['ID'] ?>"
           href="#">X</a>
    </div>
    <div id="lunch-products-pc-<?= $itemProduct['ID'] ?>"
         class="col-11 offset-1 zakaz_tovar_complect">
        <?php foreach ($itemProduct['LUNCH']['CATEGORIES'] as $itemCategoryLunch): ?>
            <?php if (is_countable($itemCategoryLunch['PRODUCTS']) && count($itemCategoryLunch['PRODUCTS']) > 0): ?>
                <div class="col-12 row zakaz_tovar_complect-category">
                    <p class="zakaz_tovar_complect-category_title"><?= $itemCategoryLunch['NAME'] ?></p>
                    <?php foreach ($itemCategoryLunch['PRODUCTS'] as $itemProductLunch): ?>
                        <div class="col-12 row zakaz_tovar_complect-category_tovar">
                            <div class="col-1">
                                <?php if (!empty($itemProductLunch['PREVIEW_PICTURE'])): ?>
                                    <img src="<?= CFile::GetPath($itemProductLunch['PREVIEW_PICTURE']); ?>"
                                         alt="img">
                                <?php elseif (!empty($itemCategoryLunch['PREVIEW_PICTURE'])): ?>
                                    <img src="<?= CFile::GetPath($itemCategoryLunch['PREVIEW_PICTURE']); ?>"
                                         alt="img">
                                <?php else: ?>
                                    <img src="/img/default_bludo.png"
                                         alt="default_img">
                                <?php endif; ?>
                            </div>
                            <div class="col-5"><?= $itemProductLunch['NAME'] ?> </div>
                            <div class="col-5"><?= $itemProductLunch['COUNT'] ?> <?= Loc::getMessage('PC') ?>.</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="col-10 offset-1 warning_complect_in_cart">
        <?php
        foreach ($itemProduct['ERROR'] as $itemError) {
            echo htmlspecialchars_decode($itemError);
        }
        ?>
    </div>
</div>