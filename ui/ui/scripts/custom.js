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
  var $activeTargets = null;

  // Show modern loading state for tables
  function showTableLoading() {
    var $targets = $(".table-responsive");
    var $tables = $(".table-with-loader, table.table");

    $tables.each(function () {
      var $t = $(this);
      if (!$t.closest(".table-responsive").length) {
        var $p = $t.closest(".panel-body, .card-body, .box-body, .panel, .card");
        if ($p.length) {
          $targets = $targets.add($p);
        } else {
          $targets = $targets.add($t.parent());
        }
      }
    });

    $activeTargets = $targets;

    $targets.each(function () {
      var $wrap = $(this);
      $wrap.addClass("loading-state");
      if ($wrap.css("position") === "static") {
        $wrap.addClass("table-loading-relative");
      }
      var $existing = $wrap.find(".table-loading-overlay");
      if ($existing.length > 0 && $existing.hasClass("is-fading-out")) {
        $existing.stop(true, false).removeClass("is-fading-out").remove();
        $existing = $();
      }
      if ($existing.length === 0) {
        $wrap.append(
          '<div class="table-loading-overlay">' +
            '<div class="table-loading-bar"></div>' +
            '<div class="table-loading-badge">' +
              '<div class="table-loading-spinner"></div>' +
              '<span>Loading...</span>' +
            '</div>' +
          '</div>'
        );
      }
    });

    $(".table-with-loader, table.table").addClass("table-loading-dimmed");
  }

  // Hide modern loading state
  function hideTableLoading() {
    if ($activeTargets && $activeTargets.length) {
      $activeTargets.removeClass("loading-state table-loading-relative");
      $activeTargets = null;
    }
    $(".table-responsive, .panel-body, .card-body, .box-body, .panel, .card").removeClass("loading-state table-loading-relative");
    $(".table-with-loader, table.table").removeClass("table-loading-dimmed loading");
    var $overlays = $(".table-loading-overlay").not(".is-fading-out");
    if ($overlays.length > 0) {
      $overlays.addClass("is-fading-out").fadeOut(150, function () {
        if ($(this).hasClass("is-fading-out")) {
          $(this).remove();
        }
      });
    }
  }

  // Handle temporary loading state
  function handleTableLoading(duration) {
    showTableLoading();
    setTimeout(hideTableLoading, duration || 450);
  }

  // Expose helpers globally
  window.showTableLoading = showTableLoading;
  window.hideTableLoading = hideTableLoading;
  window.handleTableLoading = handleTableLoading;

  // Global entries-per-page change handler
  window.changePerPage = function (select) {
    showTableLoading();
    var val = select.value;
    // The select can name its cookie (data-cookie). Otherwise it is guessed from the address, which is either
    // "?_route=plan/voucher/" or, with URL rewriting, "/plan/voucher" (the query string is empty then).
    var where = ((window.location.search || "") + " " + (window.location.pathname || "")).toLowerCase();
    var cookieName = select.getAttribute("data-cookie") || "customer_per_page";
    if (!select.getAttribute("data-cookie")) {
      if (where.indexOf("voucher") !== -1) {
        cookieName = "voucher_per_page";
      } else if (where.indexOf("hotspot") !== -1) {
        cookieName = "hotspot_per_page";
      } else if (where.indexOf("pppoe") !== -1) {
        cookieName = "pppoe_per_page";
      }
    }
    document.cookie = cookieName + "=" + val + "; path=/; max-age=" + (365 * 24 * 60 * 60);
    setTimeout(function () {
      location.reload();
    }, 250);
  };

  // Trigger loading on page load if any table is present
  if ($(".table-responsive, .table-with-loader, table.table").length > 0) {
    handleTableLoading(450);
  }

  // Dismiss loading state when window load finishes (with slight buffer)
  $(window).on("load", function () {
    setTimeout(hideTableLoading, 200);
  });

  // Show loading state on search form submission
  $(document).on("submit", "form#site-search, form[action*='customers'], form[action*='hotspot'], form[action*='pppoe'], form[action*='plan'], form[action*='routers'], form[action*='voucher'], form[action*='bandwidth'], form[action*='logs']", function () {
    showTableLoading();
  });

  // Show loading state when changing entries per page
  $(document).on("change", "#per_page, select[name='per_page']", function () {
    showTableLoading();
  });

  // Add loading state on pagination clicks
  $(document).on("click", ".pagination a, .pager a", function () {
    showTableLoading();
  });
});
