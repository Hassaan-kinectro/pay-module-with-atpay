/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 *
 */

define(["Magento_Checkout/js/view/payment/default"], function (Component) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Pay_WithAtPay/payment/custompayment",
        },
    });
});
