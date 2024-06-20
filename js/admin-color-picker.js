// js/admin-color-picker.js
jQuery(document).ready(function ($) {
  // ライセンスが有効な場合のみカラーピッカーを適用
  if (ButtonSettings.licenseStatus === "valid") {
    $(".my-color-field").wpColorPicker();
  } else {
    // ライセンスが無効の場合はカラーピッカーを無効化し、テキストフィールドも無効化
    $(".my-color-field").prop("disabled", true);
  }
});
