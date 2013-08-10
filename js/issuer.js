jQuery(function() {
  jQuery(document).on("click", ".issuer", function(e) {
    e.preventDefault();
    var button = jQuery(e.target);
    var root_url = button.attr("data-root");
    var tax_id = button.attr("data-tax_id");
    if (button.hasClass("current-active")) {
      jQuery.post(root_url + "/?issuer", {issuer_active: tax_id});
      var old_active = jQuery(".issuer-disabled");
      button.toggleClass("current-disabled current-active btn-success btn-primary").html("Active").attr("disabled", "disabled");
      if (old_active.length > 0) {
        old_active.toggleClass("current-disabled current-active btn-success btn-primary").html("Make Active").removeAttr("disabled");
      }
    } else if (button.hasClass("exclude-active")) {
      jQuery.post(root_url + "/?issuer", {issuer_exclude: tax_id});
      button.toggleClass("exclude-disabled exclude-active btn-warning btn-danger").html("Hidden");
    } else if (button.hasClass("exclude-disabled"))  {
      jQuery.post(root_url + "/?issuer", {issuer_include: tax_id});
      button.toggleClass("exclude-disabled exclude-active btn-warning btn-danger").html("Hide");
    }
  }); 
});
