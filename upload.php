<?php

$uploaddir = getcwd() . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;

if (!file_exists($uploaddir))
  mkdir($uploaddir);

$uploadfile = $uploaddir . basename($_FILES['file']['name']);

copy($_FILES['file']['tmp_name'], $uploadfile);

echo json_encode(array(
  'status' => 'success',
  'path'   => $uploadfile,
  'old'    => $_FILES['file']['tmp_name'],
));
