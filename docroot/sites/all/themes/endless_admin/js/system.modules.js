(function ($, Drupal) {

  'use strict';

  /**
   * Switchery checkbox for modules page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the switchery checkbox to modules page.
   */
  Drupal.behaviors.modulesSwitchery = {
    attach: function (context) {
      // Switchery.
      var elems = Array.prototype.slice.call(document.querySelectorAll('.system-modules .module-disabled .form-checkbox, .system-modules-uninstall .module-uninstall .form-checkbox'));

      elems.forEach(function(html) {
        var switchery = new Switchery(html);
      });
    }
  };

  /**
   * Description and details filter for modules page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Add checkboxes to show/hide description and details on modules page.
   */
  Drupal.behaviors.modulesToggleDesc = {
    attach: function (context) {
      var $filterText = $('.table-filter', context);

      var $systemModules = $('.system-modules, .system-modules-uninstall', context);
      var $moduleDescription = $('td.description', $systemModules);
      var $filterTextInput = $('input.table-filter-text', $filterText);
      var $filterHideDesc = $('<div id="hide-description" class="form-item"><label><input type="checkbox" value="1" class="form-checkbox">' + Drupal.t('Hide description') + '</label></div>');
      var $filterHideDescInput = $('input[type="checkbox"]', $filterHideDesc);

      $filterText.append($filterHideDesc);

      $filterHideDescInput.change(function() {
        if (this.checked) {
          $moduleDescription.hide();
          $systemModules.addClass('hide-description');
        }
        else {
          $moduleDescription.show();
          $systemModules.removeClass('hide-description');
        }
      })
      $filterHideDescInput.click();
    }
  };

})(jQuery, Drupal);
