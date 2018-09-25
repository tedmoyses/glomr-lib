<?php

if(file_exists($_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'])){
  return false;
} else {
  http_response_code(404);
  if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/error.html')) {
    readfile($_SERVER['DOCUMENT_ROOT'] . '/error.html');
  } else {
    echo "<h1>Ooops!</h1><p>Something went wrong, sorry</p>";
  }
}
