/**
 * Module: TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer
 */
define(["jquery", 'TYPO3/CMS/Backend/Icons'], function($, Icons) {

  var Semantilizer = {};

  Semantilizer.update = function(url) {
    if(url) {
      window.location = url;
      Semantilizer.addLoader();
    }
  };

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
      $('#js-semantilizer-list').css({'background-image': 'none', 'padding': '2em 1em'}).html('<a style="text-decoration: none" href="' + window.location.href + '">' + icon + '&nbsp;' + TYPO3.lang['overview.refresh'] + '</a>');
    });
  };

  $(document).ready(Semantilizer.watchSorting);

  TYPO3 = TYPO3 || {};
  TYPO3.Semantilizer = Semantilizer;

  // Return the object in the global space
  return Semantilizer;
});
