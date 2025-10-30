document.addEventListener("DOMContentLoaded", function () {
  // Giftcard and epasscard field mapping
  function getMappedData() {
    const mappedData = {};
    const fieldItems = document.querySelectorAll("#giftEpasss .field-item");
    let hasEmpty = false;

    fieldItems.forEach((item) => {
      const fieldName = item.getAttribute("data-field");
      const selectElement = item.querySelector("select");
      const selectedValue = selectElement.value;

      // Reset previous styles
      selectElement.style.border = "";

      if (!selectedValue) {
        hasEmpty = true;
        selectElement.style.border = "2px solid red";
      }

      mappedData[fieldName] = selectedValue;
    });

    return { mappedData, hasEmpty };
  }

  // Example usage:
  const generateBtn = document.getElementById("generateMapping");
  const mapNotification = document.getElementById("statusMessage");
  if (generateBtn) {
    generateBtn.addEventListener("click", () => {
      const { mappedData, hasEmpty } = getMappedData();
      generateBtn.querySelector(".loading-spinner").style.display = "inline-block";
      generateBtn.disabled = true;

      if (hasEmpty) {
        mapNotification.textContent =
          "Please fill in all mapping fields before submitting.";
        generateBtn.querySelector(".loading-spinner").style.display = "none";
        mapNotification.style.display = "block";
        generateBtn.disabled = false;
        setTimeout(function () {
          mapNotification.style.display = "none";
        }, 3000); // 3000ms = 3 seconds
        return;
      }

      const contentEl = document.querySelector("#giftEpasss .content");
      const template_id = contentEl.getAttribute("pass-template-id");
      const template_uid = contentEl.getAttribute("mapped-pass-uid");

      const output = Object.entries(mappedData)
        .map(([key, value]) => `$mapping['${key}'] = '${value}';`)
        .join("\n");

      fetch(egw_obj.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action: "wodgc_generate_epasscard_map",
          mapping: JSON.stringify(mappedData),
          template_id: template_id,
          template_uid: template_uid,
          nonce: egw_obj.nonce,
        }),
      })
        .then((response) => response.text())
        .then((result) => {
          generateBtn.querySelector(".loading-spinner").style.display = "none";
          mapNotification.textContent =
            "Mapping saved successfully!";
          mapNotification.style.display = "block";
          generateBtn.disabled = false;
          setTimeout(function () {
            mapNotification.style.display = "none";
          }, 3000); // 3000ms = 3 seconds
        })
        .catch((error) => {
          console.error("Error:", error);
          generateBtn.querySelector(".loading-spinner").style.display = "none";
          mapNotification.textContent =
            "Failed to save mapping.";
          mapNotification.style.display = "block";
          generateBtn.disabled = false;
          setTimeout(function () {
            mapNotification.style.display = "none";
          }, 3000); // 3000ms = 3 seconds
        });


    });
  }

  //Pass create by admin from gift card
  if (document.querySelector(".pass_create")) {
    document.querySelector(".pass_create").addEventListener("click", function () {
      const gift_card_id = this.getAttribute("data-id");
      this.querySelector(".loading-spinner").style.display = "inline-block";

      fetch(egw_obj.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action: "wodgc_pass_create_by_admin",
          giftcard_id: gift_card_id,
          nonce: egw_obj.nonce,
        }),
      })
        .then((response) => response.text())
        .then((result) => {
          //location.reload();
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    });
  }

});
