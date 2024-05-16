<?php use Bitrix\Main\Localization\Loc; ?>
<div class="payment-methods">
    <div class="addition_setting_l_help">
        <img class="addition_setting_l_help_img" src="/img/circle_n3.png" alt="circle_n3">
        <p class="addition_setting_l_help_p"><?= Loc::getMessage('POINTER_2') ?></p>
        <img class="addition_setting_l_help_img_arrow" src="/img/arrow.png" alt="arrow">
    </div>
    <div class="addition_setting">
        <div class="addition_setting_title"><?= Loc::getMessage('WAYS_PAYMENT') ?></div>
        <label class="addition_setting_buttom_line">
            <input id="addition-payment-input-<?= $itemBasket['ID'] ?>"
                   data-id-basket="<?= $itemBasket['ID'] ?>" type="checkbox"
                   name="pay_lgoty" <?= $itemBasket['PAYMENT_METHODS']['ADDITION_PAY']['ACTIVE'] ? '' : 'disabled' ?>
                   <?= $itemBasket['PAYMENT_METHODS']['ADDITION_PAY']['CHECKED'] ? 'checked' : '' ?>>
            <span id="addition-payment-name-<?= $itemBasket['ID'] ?>"
                  class="<?= $itemBasket['PAYMENT_METHODS']['ADDITION_PAY']['ACTIVE'] ? '' : 'addition_setting_span_no_active' ?>">
                <?= Loc::getMessage('USED_PVZP') ?>
            </span>
            <div id="addition-payment-mes-<?= $itemBasket['ID'] ?>" class="addition_setting_lgoty_message">
                <p><?= $itemBasket['PAYMENT_METHODS']['ADDITION_PAY']['MESSAGE'] ?></p>
            </div>
        </label>
        <br>
        <label class="addition_setting_buttom_line">
            <input id="cashless-payment-input-<?= $itemBasket['ID'] ?>"
                   data-id-basket="<?= $itemBasket['ID'] ?>" type="checkbox"
                   name="pay_bank" <?= $itemBasket['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE'] ? '' : 'disabled' ?>
                   <?= $itemBasket['PAYMENT_METHODS']['CASHLESS_PAYMENT']['CHECKED'] ? 'checked' : '' ?>>
            <span id="cashless-payment-name-<?= $itemBasket['ID'] ?>"
                  class="<?= $itemBasket['PAYMENT_METHODS']['CASHLESS_PAYMENT']['ACTIVE'] ? '' : 'addition_setting_span_no_active' ?>">
                <?= Loc::getMessage('USED_CASHLESS') ?>
            </span>
            <div id="cashless-payment-mes-<?= $itemBasket['ID'] ?>" class="addition_setting_bank_message">
                <p><?= $itemBasket['PAYMENT_METHODS']['CASHLESS_PAYMENT']['MESSAGE'] ?></p>
            </div>
        </label>
        <label>
            <input id="cash-payment-input-<?= $itemBasket['ID'] ?>"
                   data-id-basket="<?= $itemBasket['ID'] ?>" type="checkbox"
                   name="cash_payment" <?= $itemBasket['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE'] ? '' : 'disabled' ?>
                   <?= $itemBasket['PAYMENT_METHODS']['CASH_PAYMENT']['CHECKED'] ? 'checked' : '' ?>>
            <span id="cash-payment-name-<?= $itemBasket['ID'] ?>"
                  class="<?= $itemBasket['PAYMENT_METHODS']['CASH_PAYMENT']['ACTIVE'] ? '' : 'addition_setting_span_no_active' ?>">
                <?= Loc::getMessage('USED_CASH') ?>
            </span>
            <div id="cash-payment-mes-<?= $itemBasket['ID'] ?>" class="addition_setting_bank_message">
                <p><?= $itemBasket['PAYMENT_METHODS']['CASH_PAYMENT']['MESSAGE'] ?></p>
            </div>
        </label>
    </div>
    <div class="clearfix"></div>
</div>