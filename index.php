<?php

require 'EburyDrive.php';

try {

    EburyDrive::init();

} catch ( Exception $e) {

    echo $e->getMessage();

}

