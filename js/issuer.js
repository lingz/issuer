jQuery(function() {
  jQuery(document).on("click", ".issuer-active", function(e) {
    e.preventDefault();
    var button = jQuery(e.target);
    var root_url = button.attr("data-root");
    var tax_id = button.attr("data-tax_id");
    jQuery.post(root_url + "/?issuer", {issuer: tax_id});
    var old_active = jQuery(".issuer-disabled");
    button.toggleClass("issuer-disabled issuer-active btn-success btn-primary").html("Active").attr("disabled", "disabled");
    if (old_active.length > 0) {
      old_active.toggleClass("issuer-disabled issuer-active btn-success btn-primary").html("Make Active").removeAttr("disabled");
    }
  }); 
});
