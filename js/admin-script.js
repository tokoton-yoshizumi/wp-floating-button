jQuery(document).ready(function ($) {
  console.log(ButtonSettings);

  function setupImageUploadButton(buttonId, deviceType) {
    $("#upload_image_button_" + buttonId)
      .off("click")
      .on("click", function (e) {
        e.preventDefault();

        let image_frame = wp.media({
          title: "Select Media",
          multiple: false,
          library: { type: "image" },
        });

        image_frame.on("select", function () {
          var media_attachment = image_frame
            .state()
            .get("selection")
            .first()
            .toJSON();
          $("#floating_button_image_id_" + buttonId).val(media_attachment.id);
          $("#image_preview_" + buttonId).html(
            '<img src="' +
              media_attachment.url +
              '" />' +
              '<button type="button" id="remove_image_button_' +
              buttonId +
              '" class="remove-image-button">×</button>'
          );

          // ここで削除ボタンのイベントを再設定
          setupImageRemoveButton(buttonId);

          $("#remove_image_button_" + buttonId).show(); // バツボタンを表示
        });

        image_frame.open();
      });
  }

  function setupImageRemoveButton(buttonId) {
    $("#remove_image_button_" + buttonId)
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        $("#floating_button_image_id_" + buttonId).val("");
        $("#image_preview_" + buttonId).html("");
      });
  }

  function toggleButtonSettings(count) {
    for (var i = 1; i <= 3; i++) {
      $(".floating-button-settings-group.floating-button-field-" + i).toggle(
        i <= count
      );
    }
  }
  toggleButtonSettings($("#floating_button_columns").val());

  // 初期設定時に全ての設定ボタンにイベントを適用
  var initialButtonCount = parseInt($("#floating_button_columns").val());
  for (let i = 1; i <= initialButtonCount; i++) {
    setupImageUploadButton(i);
    setupImageRemoveButton(i);
  }

  $("#floating_button_columns").change(function () {
    var buttonCount = parseInt($(this).val());
    toggleButtonSettings(buttonCount);

    for (let i = 1; i <= buttonCount; i++) {
      setupImageUploadButton(i);
      setupImageRemoveButton(i);
    }
  });

  $(".button-settings-container").on(
    "change",
    "[id^='use_banner_']",
    function () {
      var checked = $(this).is(":checked");
      var container = $(this).closest(".floating-button-settings-group");
      container.find(".image-upload-settings").toggle(checked);
      container.find(".button-text-settings").toggle(!checked);
    }
  );

  // ボタン設定コンテナ内でプリセットカラーセレクタの変更を監視
  $(".button-settings-container").on(
    "change",
    ".preset-color-selector",
    function () {
      var selectedPreset = $(this).val();
      var container = $(this).closest(".floating-button-settings-group");
      var customColorContainer = container.find(".custom-color");

      if (
        selectedPreset === "custom_color" &&
        ButtonSettings.licenseStatus === "valid"
      ) {
        customColorContainer.show();
      } else {
        customColorContainer.hide();
      }
    }
  );

  $(".preset-color-selector").each(function (index) {
    var selector = $(this);
    var container = selector.closest(".floating-button-settings-group");
    var presetKey = "preset_" + (index + 1); // 1から始まるインデックスを生成
    var selectedPreset = ButtonSettings.presets[presetKey]; // サーバーからのプリセット値を取得

    // カスタムカラーの表示状態を更新
    var customColorContainer = container.find(".custom-color");
    if (
      selectedPreset === "custom_color" &&
      ButtonSettings.licenseStatus === "valid"
    ) {
      customColorContainer.show();
    } else {
      customColorContainer.hide();
    }

    // オプションの正しい選択を強制
    selector.find("option").prop("selected", false); // まず全ての選択を解除
    selector.find(`option[value="${selectedPreset}"]`).prop("selected", true); // 正しい値を選択
  });

  $("#reset-settings-form").on("submit", function (e) {
    if (!confirm("初期設定にリセットします。よろしいですか？")) {
      e.preventDefault();
    }
  });
  $("#license-revoke").on("submit", function (e) {
    if (
      !confirm(
        "ライセンス認証を解除すると全ての設定が初期設定にリセットされます。よろしいですか？"
      )
    ) {
      e.preventDefault();
    }
  });

  $(document).on("click", ".reset-click-count-button", function () {
    var buttonId = $(this).data("button-id");
    $.ajax({
      url: "/wp-admin/admin-ajax.php",
      type: "POST",
      data: {
        action: "reset_button_click_count",
        button_id: buttonId,
      },
      success: function (response) {
        location.reload();
      },
    });
  });

  // フォームの送信イベントを捕捉
  $("#floating-button-form").on("submit", function (e) {
    var formIsValid = true; // フォームが有効かどうかのフラグ

    // 表示されているボタン設定のみを検証
    $(".floating-button-settings-group:visible").each(function () {
      var index = $(this).index() + 1; // ボタンのインデックスを取得
      var useBanner = $("#use_banner_" + index).is(":checked");
      var imageId = $("#floating_button_image_id_" + index).val();

      if (useBanner && !imageId) {
        alert(
          "ボタン " +
            index +
            " のバナー画像が未設定です、画像を選択してください。"
        );
        formIsValid = false;
        return false; // ループを中断
      }
    });

    if (!formIsValid) {
      e.preventDefault(); // フォームの送信を阻止
    }
  });
});

function renderClickChart(canvasId, label, data) {
  const rawDates = Object.keys(ClickChartData.button1);
  const formattedDates = rawDates.map((dateStr) => {
    const date = new Date(dateStr);
    const month = date.getMonth() + 1;
    const day = date.getDate();
    return `${month}/${day}`; // 例: 3/27
  });

  const button1 = Object.values(ClickChartData.button1);
  const button2 = Object.values(ClickChartData.button2);
  const button3 = Object.values(ClickChartData.button3);

  const ctx = document.getElementById("click-chart").getContext("2d");
  new Chart(ctx, {
    type: "bar",
    data: {
      labels: formattedDates, // ←ここが修正ポイント
      datasets: [
        {
          label: "ボタン 1",
          data: button1,
          backgroundColor: "rgba(255, 99, 132, 0.6)",
        },
        {
          label: "ボタン 2",
          data: button2,
          backgroundColor: "rgba(54, 162, 235, 0.6)",
        },
        {
          label: "ボタン 3",
          data: button3,
          backgroundColor: "rgba(75, 192, 192, 0.6)",
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            footer: function (tooltipItems) {
              const total = tooltipItems.reduce(
                (sum, item) => sum + item.parsed.y,
                0
              );
              return `合計: ${total} 回`;
            },
          },
        },
        legend: {
          position: "top",
        },
      },
      scales: {
        x: {
          stacked: true,
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: {
            stepSize: 1,
          },
        },
      },
    },
  });
}

renderClickChart("click-chart-1", "ボタン 1", ClickChartData.button1);
renderClickChart("click-chart-2", "ボタン 2", ClickChartData.button2);
renderClickChart("click-chart-3", "ボタン 3", ClickChartData.button3);
