<?php
use Bitrix\Main\Localization\Loc;

/**
 * @var $itemBasket
 */
?>
<?php if (!empty($itemBasket['SPECIAL_FOOD'])): ?>
    <div id="special-food-<?= $itemBasket['ID'] ?>" class="cart_items_specpitanie js-special-food-<?= $itemBasket['ID'] ?> <?= !$itemBasket['SPECIAL_FOOD_APPOINTED'] ? 'hide' : ''; ?>">
<?php else: ?>
    <div id="special-food-<?= $itemBasket['ID'] ?>" class="cart_items_specpitanie js-special-food-<?= $itemBasket['ID'] ?> hide">
<?php endif; ?>
    <div class="cart_items_specpitanie_title"><?= Loc::getMessage('SPECIAL_FOOD') ?></div>
    <select id="special-food-select-<?= $itemBasket['ID'] ?>"
            class="js-special-food-select" data-id-basket="<?= $itemBasket['ID'] ?>">
        <option value="0"><?= Loc::getMessage('NO_SELECT_SPECIAL_FOOD') ?></option>
        <?php foreach ($itemBasket['SPECIAL_FOOD'] as $itemSpecialFood) { ?>
            <option value="<?= $itemSpecialFood['PLAN_MENU_PRODUCTS']['ID'] ?>" <?= $itemSpecialFood['SELECTED'] ? 'selected' : '' ?>>
                <?= $itemSpecialFood['SHOW_NAME'] ?>
            </option>
        <?php } ?>
    </select>
</div>