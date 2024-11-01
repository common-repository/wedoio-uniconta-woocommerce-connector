(function ($) {
  'use strict';
  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */
  $(document).ready(function () {
    if (!("ajaxurl" in window)) {
      window.ajaxurl = "";
    }

    $(".status-outlet").once("init").each(function () {
      var data = {
        'action': 'check_service_status'     // We pass php values differently!
      };

      $.post(ajax_object.ajax_url, data, function (response) {
        $(".status-outlet").css("background", response);
      }).fail(function () {
        $(".status-outlet").css("background", "red");
      });
    });

    $(".organisations-select-wrapper").once("init").each(function () {
      var data = {
        'action': 'load_organisations_options'     // We pass php values differently!
      };

      $(".organisations-select-wrapper .organisations-select-loading").show();
      $(".organisations-select-wrapper .organisations-select").hide();

      $.post(ajax_object.ajax_url, data, function (response) {
        $(".organisations-select-wrapper .organisations-select-loading").hide();
        $(".organisations-select-wrapper .organisations-select").remove();
        $(".organisations-select-wrapper").prepend(response);
      }).fail(function () {
        $(this).find(".organisations-select-wrapper .organisations-select-loading").hide();
        $(this).find(".organisations-select-wrapper .organisations-select-error").show();
      });
    });

    $(".mapping-selector").once("init").each(function () {
      var tabs = $(this).find(".nav-tab");
      tabs.click(function (e) {
        var target = $(this).attr("data-target");
        console.log(target);
        $(".mapping-selector .nav-tab").removeClass("nav-tab-active");
        $(this).addClass("nav-tab-active");

        $(".mapping-tab").hide();
        $(".mapping-tab").removeClass("active");

        $("#" + target).show();
        $("#" + target).addClass("active");
        e.stopPropagation();
        e.preventDefault();
      });
    });

  });
})(jQuery);
