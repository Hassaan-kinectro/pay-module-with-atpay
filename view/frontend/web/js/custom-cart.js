require(["jquery"], function ($) {
  $(document).ready(function () {
    var data = localStorage.getItem("customer");
    console.log(data);
    var updated = JSON.parse(data);
    var country = updated.country;
    console.log(country);

    var isCheckoutAvailable = setInterval(() => {
      var prefill = document.getElementsByName("firstname")[0];
      if (prefill && prefill.value.length <= 0) {
        document.getElementsByName("firstname")[0].focus();
        document.execCommand("insertText", false, updated.firstName);

        document.getElementsByName("lastname")[0].focus();
        document.execCommand("insertText", false, updated.lastName);

        document.getElementsByName("city")[0].focus();
        document.execCommand("insertText", false, updated.city);

        document.getElementsByName("street[0]")[0].focus();
        document.execCommand("insertText", false, updated.address);

        document.getElementsByName("postcode")[0].focus();
        document.execCommand("insertText", false, updated.postCode);

        document.getElementsByName("telephone")[0].focus();
        document.execCommand("insertText", false, updated.phone);

        document.getElementsByName("username")[1].focus();
        document.execCommand("insertText", false, updated.email);

        clearInterval(isCheckoutAvailable);
      }
    }, 1000);

    var existCondition = setInterval(function () {
      if ($(".payment-method").length) {
        async function runMyFunction() {
          console.log("script ran");
          var button = document.getElementById("button-atpay");
          button.addEventListener("click", myFunction);
          async function myFunction() {
            console.log("clicked");
            var checkBox = document.getElementById("custompayment");
            if (checkBox.checked == true) {
              await localStorage.setItem("mode", "atpay");
            } else {
              console.log("checked others");
            }
          }
        }
        runMyFunction();
        clearInterval(isCheckoutAvailable);
        clearInterval(existCondition);
      }
    }, 1000);
  });
});
