<?php use Bitrix\Main\Localization\Loc; ?>
<div>
    <div class="cart_items_delivery_l_help">
        <img class="cart_items_delivery_l_help_img" src="/img/circle_n1.png"
             alt="circle_n1">
        <p class="cart_items_delivery_l_help_p">
            <?= Loc::getMessage('POINTER_1') ?>
        </p>
        <img class="cart_items_delivery_l_help_img_arrow" src="/img/arrow.png" alt="arrow">
    </div>
    <div class="cart_items_delivery">
        <div class="cart_items_delivery_title"><?= Loc::getMessage('PLACE_AND_PERIOD_DELIVERY') ?></div>
        <select id="js-delivery-<?= $itemBasket['ID'] ?>"
                class="cart_items_delivery_select_<?= $itemBasket['ID'] ?>"
                data-id-basket="<?= $itemBasket['ID'] ?>">
            <option value="0"><?= Loc::getMessage('NO_PLACE_DELIVERY') ?></option>
            <?php foreach ($itemBasket['DELIVERY_PLACES'] as $itemDeliveryPlace) { ?>
                <option value="<?= $itemDeliveryPlace['ID'] ?>"
                    <?php if ($itemBasket['SELECTED_DELIVERY_PLACE']['ID']
                        == $itemDeliveryPlace['ID']) {
                        echo 'selected';
                    } ?>><?= $itemDeliveryPlace['NAME'] ?></option>
            <?php } ?>
        </select>
        <select id="js-delivery-period-<?= $itemBasket['ID'] ?>"
                class="delivery-period <?= !$itemBasket['SELECTED_DELIVERY_PLACE'] ? 'hide' : ''; ?>"
                data-id-basket="<?= $itemBasket['ID'] ?>"
                data-id-delivery-place="<?= $itemBasket['SELECTED_DELIVERY_PLACE']['ID'] ?>">
            <option value="0"><?= Loc::getMessage('NO_PERIOD_DELIVERY') ?></option>
            <?php foreach ($itemBasket['SELECTED_DELIVERY_PLACE']['SCHEDULE'] as $itemSchedule) { ?>
                <option value="<?= $itemSchedule['ID'] ?>" <?= $itemSchedule['SELECTED'] ? 'selected' : '' ?>>
                    <?= $itemSchedule['STR_TIME'] ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <div class="clearfix"></div>
</div>