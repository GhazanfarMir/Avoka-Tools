<?php

namespace EburyLabs\Utils;


class InputValidation
{

    public function validated()
    {

        if (!isset($_SERVER["HTTP_HOST"])) {

            global $argv;
            parse_str($argv[1], $_GET);
        }

        if (
            empty($_GET)
            or empty($_GET['accId'])
            or $_GET['accId'] === 'null'
            or empty($_GET['formId'])
            or $_GET['formId'] === 'null'
            or empty($_GET['filename'])
            or $_GET['filename'] === 'null'
        ) {

            throw new \InvalidArgumentException('Required data is not supplied (e.g. accId, formId, filename)');

        }

        return true;
    }
}