<?php
$filePath = $_POST["path"];
$file = file_get_contents($filePath);
echo $file;
?>