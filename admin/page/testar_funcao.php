<?php

 //Set the Content Type
      header('Content-type: image/jpeg');

      // Create Image From Existing File
      $jpg_image = imagecreatefrompng('../QR_code.png');

      // Allocate A Color For The Text
      $black = imagecolorallocate($jpg_image, 0, 0, 0);

      // Set Path to Font File
      $font_path = '../../class/dompdf/lib/fonts/DejaVuSans-Oblique.ttf';

      // Set Text to Be Printed On Image
      $text = "RIFA  d112d1";

      // Print Text On Image
      imagettftext($jpg_image, 7, 0, 20, 10, $black, $font_path, $text);

      // Send Image to Browser
      imagepng($jpg_image);

      // Clear Memory
      imagedestroy($jpg_image);

?>