<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<?php
require("/uploader.php");
$upload=new \uploader\upload("/uploads/u/",array("image/jpeg"),"test1","next.php");
echo $upload->get_html_segment();
$upload=new \uploader\upload("/uploads/u/",array("image/jpeg"),"test2","next.php");
echo $upload->get_html_segment();
$upload=new \uploader\upload("/uploads/u/",array("image/jpeg"),"test3","next.php");
echo $upload->get_html_segment();
$upload=new \uploader\upload("/uploads/u/",array("image/jpeg"),"test4","next.php");
echo $upload->get_html_segment();
$upload=new \uploader\upload("/uploads/u/",array("image/jpeg"),"test5","next.php");
echo $upload->get_html_segment();
?>