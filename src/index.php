<?php

use TorresDeveloper\HTTPMessage\Request;
use TorresDeveloper\Pull\Pull;

require_once __DIR__ .  "/../vendor/autoload.php";
var_dump(Pull::fetch()->start(new Request(
    "https://example.org/"
))->getBody()->getContents());
