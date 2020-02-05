/**
 * Module: TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer
 */
define(["jquery", "TYPO3/CMS/Backend/Icons"], function ($, Icons) {

  var Semantilizer = {};

  /**
   * Update the page
   *
   * @param url
   * @return void
   */
  Semantilizer.update = function (url) {
    if (url) {
      window.location = url;
      Semantilizer.addLoader();
    }
  };

  /**
   * Add a loader over the whole page
   *
   * @return void
   */
  Semantilizer.addLoader = function () {
    Icons.getIcon('spinner-circle-light', Icons.sizes.large).done(function (spinner) {
      $('body').append('<div class="ui-block">' + spinner + '</div>');
    });
  };

  /**
   * Hide the headlines and add a link, to reload the page
   *
   * @return void;
   */
  Semantilizer.addRefreshButton = function () {
    Icons.getIcon('actions-system-refresh', Icons.sizes.small).done(function (icon) {
      $('#js-semantilizer-control').hide();
      $('#js-semantilizer-list').css({'background-image': 'none', 'padding': '2em 1em'}).html(
        '<p role="alert" style="opacity: 0.5">' + TYPO3.lang['overview.orderChanged'] + '</p>' +
        '<a href="' + window.location.href + '">' + TYPO3.lang['overview.refresh'] + '</a> ' + icon
      );
    });
  };

  // Watch the sorting of the content elements in the page module
  $(document).ready(function () {
    require(["jquery-ui/droppable"], function () {
      $('.t3js-sortable').one('drop', Semantilizer.addRefreshButton);
    });
  });

  // Add class to the context
  TYPO3 = TYPO3 || {};
  TYPO3.Semantilizer = Semantilizer;

  // Return the object
  return Semantilizer;
});
