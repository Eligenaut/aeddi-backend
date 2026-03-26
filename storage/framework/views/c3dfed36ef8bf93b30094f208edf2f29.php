<?php
  $defaultFormatMethod = 'scale';
  $retrieveFormattedVideo = cloudinary()
    ->videoTag($publicId ?? '')
    ->setAttributes([
      'controls' => true,
      'loop' => true,
      'preload' => 'auto',
    ])
    ->fallback('Your browser does not support HTML5 video tags.')
    ->$defaultFormatMethod($width ?? '', $height ?? '');
?>

<?php
  echo $retrieveFormattedVideo->serialize();
?>
<?php /**PATH C:\wamp64\www\aeddi\aeddiBack\vendor\cloudinary-labs\cloudinary-laravel\views\components\video.blade.php ENDPATH**/ ?>