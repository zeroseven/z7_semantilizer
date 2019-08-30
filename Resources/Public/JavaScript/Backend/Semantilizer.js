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
    console.log(Semantilizer.element);
    $('.t3js-sortable').on( 'drop', Semantilizer.addOverlay );
  };

  Semantilizer.addOverlay = function() {
    Icons.getIcon('actions-system-refresh', Icons.sizes.small).done(function(icon) {
      $('.js-semantilzer')
        .closest('.callout')
        .css('position', 'relative')
        .append('<div class="ui-block"></div>')
        .append(
          '<div style="position: absolute; top: 50%; left: 50%; transform: translate3d(-50%, -50%, 0); z-index: 5000;">' +
            '<a class="btn btn-default" href="' + window.location.href + '">' + icon + '&nbsp;' + TYPO3.lang['overview.reload'] + '</a>' +
          '</div>'
        );
    });
  };

  $(document).ready(Semantilizer.watchSorting);

  TYPO3 = TYPO3 || {};
  TYPO3.Semantilizer = Semantilizer;

  // Return the object in the global space
  return Semantilizer;
});
