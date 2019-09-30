/**
 * Module: TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer
 */
define(["jquery", 'TYPO3/CMS/Backend/Icons'], function($, Icons) {

  var Semantilizer = {};

  Semantilizer.updateSelects = function() {
    $('.js-semantilizer-select').change(function () {
      var url = this.value;

      if(url) {
        window.location = url;
        Semantilizer.addLoader();
      }
    });
  }

  Semantilizer.addLoader = function() {
    Icons.getIcon('spinner-circle-light', Icons.sizes.large).done(function(spinner) {
      $('body').append('<div class="ui-block">' + spinner + '</div>');
    });
  };

  Semantilizer.watchSorting = function() {
    $('.t3js-sortable').one( 'drop', Semantilizer.addRefreshButton );
  };

  Semantilizer.addRefreshButton = function() {
    Icons.getIcon('actions-system-refresh', Icons.sizes.small).done(function(icon) {
      $('#js-semantilizer-control').hide();
      $('#js-semantilizer-list').css({'background-image': 'none', 'padding': '2em 1em'}).html(
        '<p role="alert" style="opacity: 0.5">' + TYPO3.lang['overview.orderChanged'] + '</p>' +
         '<a href="' + window.location.href + '">' + TYPO3.lang['overview.refresh'] + '</a> ' + icon
      );
    });
  };

  $(document).ready(Semantilizer.watchSorting);
  $(document).ready(Semantilizer.updateSelects);

  TYPO3 = TYPO3 || {};
  TYPO3.Semantilizer = Semantilizer;

  // Return the object in the global space
  return Semantilizer;
});
