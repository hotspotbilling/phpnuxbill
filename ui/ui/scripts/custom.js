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
    // The select can name its cookie (data-cookie), and that always wins. Otherwise it is chosen from the page's route, matched
    // by whole segments: "?_route=plan/voucher/" or, with URL rewriting, "/plan/voucher" once the folder the site is
    // installed in (appUrl) is taken off the front.
    var cookieName = select.getAttribute("data-cookie");
    if (!cookieName) {
      var route = new URLSearchParams(window.location.search).get("_route");
      if (!route) {
        route = window.location.pathname || "";
        try {
          var base = new URL(appUrl, window.location.origin).pathname.replace(/\/+$/, "");
          if (base && route.indexOf(base + "/") === 0) {
            route = route.slice(base.length);
          }
        } catch (e) {}
      }
      var parts = route.toLowerCase().split("/").filter(Boolean);
      cookieName = "customer_per_page";
      if (parts.indexOf("voucher") !== -1) {
        cookieName = "voucher_per_page";
      } else if (parts.indexOf("hotspot") !== -1) {
        cookieName = "hotspot_per_page";
      } else if (parts.indexOf("pppoe") !== -1) {
        cookieName = "pppoe_per_page";
      }
    }
    document.cookie = cookieName + "=" + val + "; path=/; max-age=" + (365 * 24 * 60 * 60);
    setTimeout(function () {
      location.reload();
    }, 250);
  };

  // No loader on page load: the table arrives with the page, so showing one for half a second only made it flicker.
  // It shows while the next page is being fetched (search, page size, pager) and is cleared when a page is shown again,
  // including one restored by the Back button, which would otherwise come back with the loader still on.
  $(window).on("pageshow", function () {
    hideTableLoading();
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

/* Settings pages (.st): Yes/No dropdowns as switches, styled Select2 lists, Copy buttons on command blocks.
   The dropdown stays in the form (hidden) and keeps its name and values, so what is saved does not change; the switch only
   sets its value and fires "change", so scripts that watch the dropdown keep working. */
(function () {
  function ready(fn) {
    if (document.readyState !== "loading") { fn(); } else { document.addEventListener("DOMContentLoaded", fn); }
  }
  var PAIRS = [["yes", "no"], ["1", "0"], ["true", "false"], ["on", "off"], ["enable", "disable"], ["enabled", "disabled"], ["active", "inactive"]];

  function rowLabel(sel) {
    var row = sel.closest(".form-group");
    var lab = row && row.querySelector(".control-label");
    return lab ? lab.textContent.trim() : "";
  }

  function toSwitch(sel) {
    if (sel.multiple || sel.options.length !== 2 || sel.classList.contains("select2") || sel.hasAttribute("data-no-switch")) { return; }
    var vals = [sel.options[0].value.trim().toLowerCase(), sel.options[1].value.trim().toLowerCase()];
    var on = -1, off = -1;
    PAIRS.some(function (p) {
      if (vals.indexOf(p[0]) !== -1 && vals.indexOf(p[1]) !== -1) { on = vals.indexOf(p[0]); off = vals.indexOf(p[1]); return true; }
      return false;
    });
    if (on === -1) { return; }
    var onOpt = sel.options[on], offOpt = sel.options[off];

    var wrap = document.createElement("label");
    wrap.className = "st-sw";
    var box = document.createElement("input");
    box.type = "checkbox";
    box.className = "st-sw__box";
    box.setAttribute("role", "switch");
    box.setAttribute("aria-label", rowLabel(sel) || onOpt.text.trim());
    var track = document.createElement("span");
    track.className = "st-sw__track";
    track.setAttribute("aria-hidden", "true");
    var text = document.createElement("span");
    text.className = "st-sw__text";
    wrap.appendChild(box); wrap.appendChild(track); wrap.appendChild(text);

    function fromSelect() {
      box.checked = sel.value === onOpt.value;
      box.disabled = sel.disabled;
      text.textContent = (box.checked ? onOpt : offOpt).text.trim();
      wrap.classList.toggle("is-on", box.checked);
    }
    box.addEventListener("change", function () {
      sel.value = box.checked ? onOpt.value : offOpt.value;
      sel.dispatchEvent(new Event("change", { bubbles: true }));
      fromSelect();
    });
    sel.addEventListener("change", fromSelect);
    sel.classList.add("st-sw-src");
    sel.setAttribute("tabindex", "-1");
    sel.setAttribute("aria-hidden", "true");
    sel.parentNode.insertBefore(wrap, sel.nextSibling);
    fromSelect();
  }

  function copyButtons(root) {
    var form = root.closest("[data-t-copy]") || root;
    var tCopy = form.getAttribute("data-t-copy") || "Copy";
    var tCopied = form.getAttribute("data-t-copied") || "Copied";

    function makeBtn(cls, getText, label) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = cls;
      btn.innerHTML = '<i class="fa fa-copy" aria-hidden="true"></i>' + (label ? ' <span></span>' : "");
      if (label) { btn.lastChild.textContent = tCopy; }
      btn.title = tCopy;
      btn.addEventListener("click", function () {
        var txt = getText();
        var done = function () {
          btn.classList.add("is-done");
          if (label) { btn.lastChild.textContent = tCopied; }
          btn.title = tCopied;
          setTimeout(function () {
            btn.classList.remove("is-done");
            if (label) { btn.lastChild.textContent = tCopy; }
            btn.title = tCopy;
          }, 1600);
        };
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(txt).then(done, function () { if (fallback(txt)) { done(); } });
        } else if (fallback(txt)) { done(); }
      });
      return btn;
    }

    // the text of a block as lines: <br> become line breaks (a detached copy has no layout, so innerText would join them)
    function commandLines(p) {
      var clone = p.cloneNode(true);
      clone.querySelectorAll(".st-copy, small, a").forEach(function (n) { n.remove(); });
      clone.querySelectorAll("br").forEach(function (br) { br.replaceWith("\n"); });
      return clone.textContent.split("\n").map(function (l) { return l.trim(); })
        .filter(function (l) { return l !== "" && l.charAt(0) !== "#" && !/:$/.test(l); }); // no comments, no "Check:" left by a link
    }

    // a command or address marked .st-cmd is highlighted on its own line with its own Copy button, which copies just that text
    root.querySelectorAll(".st-cmd").forEach(function (cmd) {
      if (cmd.querySelector(".st-copy")) { return; }
      var code = cmd.querySelector("code") || cmd;
      cmd.appendChild(makeBtn("st-copy st-copy--cmd", function () {
        // the Copy button sits inside the block (inside the code itself when it is the block): its label is not part of the command
        var c = code.cloneNode(true);
        c.querySelectorAll(".st-copy").forEach(function (n) { n.remove(); });
        return c.textContent.trim();
      }, true));
    });

    root.querySelectorAll(".bs-callout > p").forEach(function (p) {
      if (p.querySelector(".st-cmd")) { p.classList.add("st-copy-none"); return; } // its commands have their own buttons
      if (p.querySelector(".st-copy")) { return; }
      // each address has its own button, which copies that address only
      p.querySelectorAll("a[href]").forEach(function (a) {
        var url = a.textContent.trim() || a.href;
        a.insertAdjacentElement("afterend", makeBtn("st-copy st-copy--inline", function () { return url; }, false));
      });
      // the block button copies the commands (none when the block only holds addresses)
      if (commandLines(p).length) {
        p.appendChild(makeBtn("st-copy", function () { return commandLines(p).join("\n"); }, true));
      } else {
        p.classList.add("st-copy-none");
      }
    });
  }
  // older browsers / plain http: returns whether the copy went through
  function fallback(txt) {
    var ta = document.createElement("textarea"), ok = false;
    ta.value = txt; ta.setAttribute("readonly", ""); ta.style.position = "fixed"; ta.style.opacity = "0";
    document.body.appendChild(ta); ta.select();
    try { ok = document.execCommand("copy"); } catch (e) { ok = false; }
    ta.remove();
    return ok;
  }

  ready(function () {
    var roots = document.querySelectorAll(".st");
    if (!roots.length) { return; }
    roots.forEach(function (root) {
      root.querySelectorAll("select.form-control").forEach(toSwitch);
      copyButtons(root);
    });
    // Select2 puts its open list at the end of <body>, outside .st: tag it so it takes the same field styling (.mc-s2)
    if (window.MutationObserver) {
      new MutationObserver(function () {
        if (!document.querySelector(".st .select2-container--open")) { return; }
        document.querySelectorAll("body > .select2-container--open").forEach(function (d) { d.classList.add("mc-s2"); });
      }).observe(document.body, { childList: true });
    }
  });
})();
