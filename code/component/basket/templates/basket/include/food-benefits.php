<?php use Bitrix\Main\Localization\Loc; ?>
<div>
    <div class="cart_items_res_l_help">
        <img class="cart_items_res_l_help_img" src="/img/circle_n2.png" alt="circle_n2">
        <p class="cart_items_res_l_help_p"><?= Loc::getMessage('CHECK_USED_BENEFITS') ?></p>
        <img class="cart_items_res_l_help_img_arrow" src="/img/arrow.png" alt="arrow">
    </div>
    <div class="cart_items_res">
        <div class="cart_items_title"><?= Loc::getMessage('USED_BENEFITS') ?></div>
        <?php if ($itemBasket['FOOD_BENEFITS']) { ?>
            <div id="food-benefits-<?= $itemBasket['ID'] ?>" class="item_date_cart_lgoty_<?= $itemBasket['ID'] ?>">
                <?php foreach ($itemBasket['FOOD_BENEFITS'] as $itemFoodBenefits) { ?>
                    <p class="item_date_cart_lgoty_item <?php if ($itemFoodBenefits['ACTIVE'] == 0) {
                        echo 'opacity_06';
                    } ?>">
                        <?php if ($itemFoodBenefits['DESC']) : ?>
                            <span class="desc_ing js_show_help"
                                  title="<?= $itemFoodBenefits['DESC'] ?>">?</span>
                        <?php endif; ?>
                        <?= $itemFoodBenefits['FULL_NAME'] ? $itemFoodBenefits['FULL_NAME'] : $itemFoodBenefits['NAME'] ?>
                        :
                        <?= Loc::getMessage('USED') ?> <?= $itemFoodBenefits['DATA_SHOW']['USE'] ?> <?= $itemFoodBenefits['MEASURE'] ?>
                        <?= Loc::getMessage('FROM') ?> <?= $itemFoodBenefits['DATA_SHOW']['REST_BALANCE'] ?> <?= $itemFoodBenefits['MEASURE'] ?>
                    </p>
                    <div class="pos_progress_bar <?php if (!$itemFoodBenefits['ACTIVE']) {
                        echo 'opacity_06';
                    } ?>">
                        <div class="progress_bar <?php if (!$itemFoodBenefits['ACTIVE']) {
                            echo 'opacity_06';
                        } ?>">
                            <div class="green_progress_bar"
                                 style="width: <?= $itemFoodBenefits['DATA_SHOW']['PROGRESS_REST'] ?>%;"></div>
                            <div class="red_progress_bar"
                                 style="width: <?= $itemFoodBenefits['DATA_SHOW']['PROGRESS_USE'] ?>%;"></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <p class="cart_items_res_no_lgoty"><?= Loc::getMessage('NO_BENEFITS') ?></p>
        <?php } ?>
    </div>
    <div class="clearfix"></div>
</div>