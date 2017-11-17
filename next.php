<?php
require(__DIR__."/uploader.php");
//Headers cannot be sent before this point
//Simply call the handle upload function and any uploads will be automaticly verified (The script will exit on it being called).
\uploader\handle_upload();
?>
