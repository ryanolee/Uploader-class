<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.4/skeleton.min.css"/>

<?php
//First we require our uploader class
require("/uploader.php");
//Then we create a new instance of the uploader class
$upload=new \uploader\upload("/uploads/",array("image/png","image/jpeg"),"test1","next.php");
//finally we get the html for that segment
echo $upload->get_html_segment();
//this is true for multiple uploads
$upload2=new \uploader\upload("/uploads/",array("audio/mpeg"),"test2","next.php");
//Fetching the html segment for this upload can be done at any time
echo $upload2->get_html_segment();
?>
