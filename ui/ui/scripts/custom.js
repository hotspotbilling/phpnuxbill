// radio checked - hotspot plan
$(document).ready(function () {
  $("input[type=radio]").change(function () {
    if ($("#Time_Limit").is(":checked")) {
      $("#DataLimit").hide();
      $("#TimeLimit").show();
    }
    if ($("#Data_Limit").is(":checked")) {
      $("#TimeLimit").hide();
      $("#DataLimit").show();
    }
    if ($("#Both_Limit").is(":checked")) {
      $("#TimeLimit").show();
      $("#DataLimit").show();
    }

    if ($("#Unlimited").is(":checked")) {
      $("#Type").hide();
      $("#TimeLimit").hide();
      $("#DataLimit").hide();
    } else {
      $("#Type").show();
    }

    if ($("#Hotspot").is(":checked")) {
      $("#p").hide();
      $("#h").show();
    }
    if ($("#PPPOE").is(":checked")) {
      $("#p").show();
      $("#h").hide();
    }
  });
});
$("#Hotspot").prop("checked", true).change();

function checkIP(f, id) {
  if (f.value.length > 6) {
    $.get(
      appUrl + "/?_route=autoload/pppoe_ip_used&ip=" + f.value + "&id=" + id,
      function (data) {
        $("#warning_ip").html(data);
      }
    );
  }
}

function checkUsername(f, id) {
  if (f.value.length > 1) {
    $.get(
      appUrl +
        "/?_route=autoload/pppoe_username_used&u=" +
        f.value +
        "&id=" +
        id,
      function (data) {
        $("#warning_username").html(data);
      }
    );
  }
}

//auto load pool - pppoe plan
var htmlobjek;
$(document).ready(function () {
  $("#routers").change(function () {
    var routers = $("#routers").val();
    $.ajax({
      url: appUrl + "/?_route=autoload/pool",
      data: "routers=" + routers,
      cache: false,
      success: function (msg) {
        $("#pool_name").html(msg);
      },
    });
  });
});

//auto load plans data - recharge user
$(function () {
  $("input[type=radio]").change(function () {
    if ($("#Hot").is(":checked")) {
      $.ajax({
        type: "POST",
        dataType: "html",
        url: appUrl + "/?_route=autoload/server",
        success: function (msg) {
          $("#server").html(msg);
        },
      });

      $("#server").change(getAjaxAlamat);
      function getAjaxAlamat() {
        var server = $("#server").val();
        $.ajax({
          type: "POST",
          dataType: "html",
          url: appUrl + "/?_route=autoload/plan",
          data: "jenis=Hotspot&server=" + server,
          success: function (msg) {
            $("#plan").html(msg);
          },
        });
      }
    } else if ($("#POE").is(":checked")) {
      $.ajax({
        type: "POST",
        dataType: "html",
        url: appUrl + "/?_route=autoload/server",
        success: function (msg) {
          $("#server").html(msg);
        },
      });
      $("#server").change(function () {
        var server = $("#server").val();
        $.ajax({
          type: "POST",
          dataType: "html",
          url: appUrl + "/?_route=autoload/plan",
          data: "jenis=PPPOE&server=" + server,
          success: function (msg) {
            $("#plan").html(msg);
          },
        });
      });
    } else {
      $.ajax({
        type: "POST",
        dataType: "html",
        url: appUrl + "/?_route=autoload/server",
        success: function (msg) {
          $("#server").html(msg);
        },
      });
      $("#server").change(function () {
        var server = $("#server").val();
        $.ajax({
          type: "POST",
          dataType: "html",
          url: appUrl + "/?_route=autoload/plan",
          data: "jenis=VPN&server=" + server,
          success: function (msg) {
            $("#plan").html(msg);
          },
        });
      });
    }
  });
});

function showPrivacy() {
  $("#HTMLModal_title").html("Privacy Policy");
  $("#HTMLModal_konten").html(
    '<center><img src="ui/ui/images/loading.gif"></center>'
  );
  $("#HTMLModal").modal({
    show: true,
    backdrop: false,
  });
  $.get("pages/Privacy_Policy.html?" + new Date(), function (data) {
    $("#HTMLModal_konten").html(data);
  });
}

function showTaC() {
  $("#HTMLModal_title").html("Terms and Conditions");
  $("#HTMLModal_konten").html(
    '<center><img src="ui/ui/images/loading.gif"></center>'
  );
  $("#HTMLModal").modal({
    show: true,
    backdrop: false,
  });
  $.get("pages/Terms_and_Conditions.html?" + new Date(), function (data) {
    $("#HTMLModal_konten").html(data);
    $("#HTMLModal").modal("handleUpdate");
  });
}

//For Security Only
/*
document.addEventListener('contextmenu', function (event) {
	event.preventDefault();
});

document.addEventListener('keydown', function(event) {
    if (event.ctrlKey && (event.key === 'u' || event.key === 'U')) {
        event.preventDefault();
    }
    if (event.key === 'F12') {
        event.preventDefault();
    }
});

document.addEventListener('contextmenu', function(event) {
    event.preventDefault();

    const menu = document.getElementById('custom-context-menu');
    menu.style.display = 'block';
    menu.style.left = `${event.pageX}px`;
    menu.style.top = `${event.pageY}px`;
});

document.addEventListener('click', function() {
    const menu = document.getElementById('custom-context-menu');
    menu.style.display = 'none';
});
*/

$(document).ready(function () {
  // Function to handle loading state for any table
  function handleTableLoading() {
    // Show loading state for all tables with loader
    $(".table-with-loader").addClass("loading");
    $(".table-responsive").addClass("loading-state");

    // Remove loading state after content is loaded
    setTimeout(function () {
      $(".table-with-loader").removeClass("loading");
      $(".table-responsive").removeClass("loading-state");
    }, 500);
  }

  // Show loading state when the page loads
  handleTableLoading();

  // Remove loading state after the page is fully loaded
  $(window).on("load", handleTableLoading);

  // Show loading state when applying filters or changing page
  $("form#site-search").on("submit", handleTableLoading);

  // Show loading state when changing entries per page
  $("#per_page").on("change", handleTableLoading);

  // Add loading state on pagination clicks
  $(document).on("click", ".pagination a", handleTableLoading);
});
