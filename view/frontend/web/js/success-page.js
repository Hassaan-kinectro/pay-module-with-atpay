require(["jquery"], function ($) {
  console.log("Loading");
  console.log("Loaded");
  var interval = setInterval(async () => {
    var data = await localStorage.getItem("mode");
    console.log(data);
    const orderId = document
      .getElementsByClassName("checkout-success")[0]
      .childNodes[1].innerText.split(": ")[1];
    console.log("orderId is :", orderId);
    if (window.ReactNativeWebView && data == "atpay") {
      localStorage.removeItem("mode");
      window.ReactNativeWebView.postMessage(
        JSON.stringify({ orderId, type: "magento" })
      );
    } else {
      localStorage.removeItem("mode");
      console.log("Web Data", JSON.stringify({ orderId, type: "magento" }));
    }
    clearInterval(interval);
  }, 800);
});
