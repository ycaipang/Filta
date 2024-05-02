/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.custom_bootstrap_sasss = {
    attach: function(context, settings) {

      var roots = {
        ".nav-link--root-a": "root-a",
        ".nav-link--root-b": "root-b"
      };
      
      $.each(roots, function(root, className) {
        if ($(root).parent().hasClass("active") || $(root).siblings("ul.dropdown-menu").find("li a.is-active").length) {
          $("#root-banner").addClass(className);
          return false; // Break the loop if a match is found
        }
      });

    }
  };

})(jQuery, Drupal);