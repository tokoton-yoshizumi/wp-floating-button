jQuery(document).ready(function ($) {
  console.log(FloatingButton);

  var closeButtonHtml =
    FloatingButton.closeButton === "1"
      ? '<div class="floating-button-close">&times;</div>'
      : "";

  var container = $(
    '<div id="floating-buttons-container" class="columns-' +
      FloatingButton.columns +
      '" style="background-color: ' +
      FloatingButton.containerBgColor +
      ';">' +
      closeButtonHtml +
      "</div>"
  );

  var buttonsWrapper = $('<div class="floating-buttons-wrapper"></div>');
  container.append(buttonsWrapper);

  $("body").append(container);

  if (FloatingButton.displayOnMobile === "0") {
    container.addClass("hide-on-mobile");
  }
  if (FloatingButton.displayOnTablet === "0") {
    container.addClass("hide-on-tablet");
  }
  if (FloatingButton.displayOnDesktop === "0") {
    container.addClass("hide-on-desktop");
  }

  $(".floating-button-close").on("click", function () {
    $("#floating-buttons-container").fadeOut();
  });

  for (var i = 1; i <= FloatingButton.numButtons; i++) {
    var button = FloatingButton.buttons[i - 1]; // 各ボタンデータにアクセス

    var bgColor = button.bgColor; // バックグラウンドカラーの取得
    var textColor = button.textColor; // テキストカラーの取得

    // カスタムカラーが未設定の場合、プリセットカラーを使用
    if (button.bgColor === "" || button.textColor === "") {
      bgColor = button.presetColors.bgColor;
      textColor = button.presetColors.textColor;
    }

    if (button.imageUrl) {
      // 画像がある場合、img タグを使用して画像を表示
      var image =
        '<a href="' +
        button.linkUrl +
        '" class="floating-button-image"><img src="' +
        button.imageUrl +
        '"></a>';
      buttonsWrapper.append(image);
    } else {
      // 画像がない場合、通常のボタンを表示
      var iconHtml = button.icon
        ? '<i class="fas ' + button.icon + '"></i> '
        : "";
      var textColor = button.textColor; // テキストの色を取得

      var buttonHtml =
        '<a href="' +
        button.linkUrl +
        '" class="floating-button ' +
        FloatingButton.design +
        '" data-button-id="' +
        i +
        '" style="color: ' +
        textColor +
        "; background-color: " +
        button.bgColor +
        ';">' +
        iconHtml +
        button.text +
        "</a>";
      buttonsWrapper.append(buttonHtml);
    }
  }

  if (FloatingButton.microcopy) {
    console.log("microcopyPosition:", FloatingButton.microcopyPosition);
    var microcopyHtml =
      '<div class="floating-buttons-microcopy">' +
      FloatingButton.microcopy +
      "</div>";

    switch (FloatingButton.microcopyPosition) {
      case "top":
        container.css("flex-direction", "column");
        container.css("gap", "5px");
        container.prepend(microcopyHtml);
        break;
      case "bottom":
        container.css("flex-direction", "column");
        container.css("gap", "5px");
        container.append(microcopyHtml);
        break;
      case "left":
        container.prepend(microcopyHtml);
        break;
      case "right":
        container.append(microcopyHtml);
        break;
    }
  }

  $(".floating-button").on("click", function (e) {
    var buttonId = $(this).data("button-id");
    $.ajax({
      url: "/wp-admin/admin-ajax.php",
      type: "POST",
      data: {
        action: "record_button_click",
        button_id: buttonId,
      },
      success: function (response) {
        console.log("Button click recorded:", response);
      },
      error: function () {
        console.log("Error recording button click");
      },
    });
  });

  $(window).scroll(function () {
    if ($(this).scrollTop() > 50) {
      $("#floating-buttons-container").fadeIn();
    } else {
      $("#floating-buttons-container").fadeOut();
    }
  });
});
