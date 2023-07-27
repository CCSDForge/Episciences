<?php

set_include_path(__DIR__ . '/../library');

require __DIR__ . '/../vendor/autoload.php';
// Autoloader
require_once('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

require_once 'const.php';

define_simple_constants();
define_app_constants();
define_review_constants();


/***************************************************
 * Only these origins are allowed to upload images *
 ***************************************************/
$accepted_origins = [
    HTTP . '://' . RVCODE . '.' . DOMAIN
];

/*********************************************
 * Change this line to set the upload folder *
 *********************************************/
$imageFolder = REVIEW_PATH . 'public/';

if (!is_dir($imageFolder) && !mkdir($imageFolder, 0777, true) && !is_dir($imageFolder)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', $imageFolder));
}


if (isset($_SERVER['HTTP_ORIGIN'])) {
    // same-origin requests won't set an origin. If the origin is set, it must be valid.
    if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    } else {
        header("HTTP/1.1 403 Origin Denied");
        return;
    }
}

// Don't attempt to process the upload on an OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    return;
}

reset ($_FILES);
$temp = current($_FILES);
if (is_uploaded_file($temp['tmp_name'])){
    /*
      If your script needs to receive cookies, set images_upload_credentials : true in
      the configuration and enable the following two headers.
    */
    //header('Access-Control-Allow-Credentials: true');
    //header('P3P: CP="There is no P3P policy."');

    // Sanitize input
//    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
//        header("HTTP/1.1 400 Invalid file name.");
//        return;
//    }

    // Verify extension
    if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png"))) {
        header("HTTP/1.1 400 Invalid extension.");
        return;
    }

    $renamedName = Ccsd_File::renameFile($temp['name'], $imageFolder);

    $fileToWrite = $imageFolder . $renamedName;

    move_uploaded_file($temp['tmp_name'], $fileToWrite);

    // Respond to the successful upload with JSON.
    // Use a location key to specify the path to the saved image resource.
    //{ location : '/your/uploaded/image/file'}
    try {
        echo json_encode(array('location' => '/public/' . $renamedName), JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        header("HTTP/1.1 500 Server Error");
    }
} else {
    // Notify editor that the upload failed
    header("HTTP/1.1 500 Server Error");
}
