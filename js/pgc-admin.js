(function (win, $) {

  var document = win.document;

  win.pgc_on_submit = function () {

    // TODO: Not used anymore, because we also have public API key.
    // var file = $("#pgc_client_secret");
    // if (file.length) {
    //   file = file[0];
    //   if ("files" in file) {
    //     if (file.files.length === 0) {
    //       alert('Select a file');
    //       return false;
    //     }
    //   } 
    // }

    // Public calendars
    document.querySelectorAll("input[data-delete-target-id]").forEach(function (input) {
      if (input.checked) {
        var tr = document.querySelector("tr[data-source-id='" + input.getAttribute("data-delete-target-id") + "']");
        tr.parentNode.removeChild(tr);
      }
    });

    var publicCalendarRows = document.querySelectorAll("tr.pgc-public-calendar-row");
    for (var i = 0; i < publicCalendarRows.length; i++) {
      var tr = publicCalendarRows[i];
      var emptyInputs = 0;
      var inputs = tr.querySelectorAll("input.pgc-public-calendar-id, input.pgc-public-calendar-title, input.pgc-public-calendar-backgroundcolor");
      inputs.forEach(function (input) {
        if (input.value === "") {
          emptyInputs += 1;
        }
      });
      if (tr.hasAttribute("data-source-id")) {
        if (emptyInputs > 0) {
          alert("Fill in all public calendar fields, or leave completely empty.");
          return false;
        }
      } else {
        if (emptyInputs === inputs.length) {
          tr.parentNode.removeChild(tr);
        } else if (emptyInputs > 0) {
          alert("Fill in all public calendar fields, or leave completely empty.");
          return false;
        }
      }
    }
    return true;
  };

  window.addEventListener('DOMContentLoaded', function () {

    document.body.addEventListener("click", function (e) {
      if (e.target.className === 'pgc-copy-text') {
        navigator.clipboard.writeText(e.target.innerText);
      }
    });

    function handleFCVersion(value) {
      var isFour = value == 4;
      document.getElementById('pgc_fullcalendar_theme').disabled = isFour;
    }

    document.getElementById('pgc_fullcalendar_version').addEventListener('change', function () {
      handleFCVersion(this.value);
    });

    handleFCVersion(document.getElementById('pgc_fullcalendar_version').value);

  });

}(window));