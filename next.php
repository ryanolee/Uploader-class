<?php
require("/uploader.php");
//Headers cannot be sent before this point
//Simply call the handle upload function and any uploads will be automaticly verified (The script will exit on it bieng called).
\uploader\handle_upload();
?>
