/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Module Created By : Rohan Hapani
 */

define([
    "uiComponent",
    "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
    "use strict";
    rendererList.push({
        type: "custompayment",
        component: "Pay_WithAtPay/js/view/payment/method-renderer/custompayment",
    });
    return Component.extend({});
});
