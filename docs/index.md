# Uploader-class


----------


The uploader-class attempts to greatly reduce the amount of code required to make uploading files possible in php.

Dependencies include:

 - jQuerey
 - Access to [session](http://php.net/manual/en/book.session.php) functionality. (including the use of cookies)


The code required to produce an upload form is as follows:


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.4/skeleton.min.css"/>
    
    <?php
    //First we require our uploader class
    require("/uploader.php");
    //Then we create a new instance of the uploader class
    $upload=new \uploader\upload(__DIR__."/uploads/",array("image/png","image/jpeg"),"test1","next.php");
    //finally we get the html for that segment
    echo $upload->get_html_segment();
    ?>
  
 When the file is uploaded to the server, using ajax, an endpoint has to be available so the file can be validated. It is recommended this php file is not hidden behind any .htaccess rules.
The example code for this file is as follows:

    <?php
    require("/uploader.php");
    //Headers cannot be sent before this point
    //Simply call the handle upload function and any uploads will be automatically verified (The function will exit on it being called).
    \uploader\handle_upload();
    ?>


----------
## The uploader-class

 A class to allow for upload of files to specified locations on the given server. Should handle all client and sever side interaction during upload of files via AJAX.This includes: 

 1. Generation of HTML for client.
 2. The handling of JavaScript to send the file to the server.
 3. To store session variables so uploads can be accepted and/or validated before being placed at the given directory.

	
### Properties
- *string* **\$upload_dir** The directory to upload files to.
- *int* **\$upload_id** A unique identifier for the class instance.
- *int* **\$upload_limit** The maximum sized files can be uploaded in (in bytes.)
-  *string[]* **\$accepted_datatypes** The list of mime types that are acceptable for
- *string* **\$file_name** The name of the file after it
   has been uploaded (File extention should not be given.)  
- *string* **\$upload_endpoint** where the form should point to for file uploads to be allowed.

##### **__construct ( \$dir\_to\_upload\_to, \$accepted\_datatypes, \$file\_name, \$upload\_endpoint, \$max\_size = 52428800 )**

 *  *string* **$dir_to_upload_to** gives a specified upload directory 
 *  *string[]* **$accepted_datatypes** A list of the accepted datatypes (must be in mime format without "*" wildcards)
 *  *string* **$file_name** The file name that should be held as the name of the file upon it being uploaded 
 *  *string* **$upload_endpoint** The endpoint the upload handle should try to upload information to.
 *  *number* **$max_size** maximum accepted size of file uploaded(in bytes)
#### Things of note:

 1. File uploaded automatically overwrite other files. This is true of all defined mime types.
 2.  Mime-types need to be used carefully , the "*" wildcard cannot be used in any capacity. Only direct mime types can be given.
