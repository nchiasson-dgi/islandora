(function (Drupal, $) {
  var ImageName = $('input[id=edit-name-0-value]').val();
  var ImageDescription = $('input[data-drupal-selector=object-description]').val();
  $(document).ajaxSuccess(function () {
    if (ImageDescription) {
      $('input[data-drupal-selector=edit-field-media-image-0-alt]').val(ImageDescription);
    }
    else {
      $('input[data-drupal-selector=edit-field-media-image-0-alt]').val(ImageName);
    }
  });
})(Drupal, jQuery);
