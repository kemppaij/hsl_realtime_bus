<?php
header('Content-Type: application/json');
if (file_exists('buses.json')) {
    echo file_get_contents('buses.json');
} else {
    echo '[]';
}