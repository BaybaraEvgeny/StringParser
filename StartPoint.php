<?php

require 'StringParser.php';

$mainString = '{Пожалуйста,|Просто|Если сможете,} сделайте так, чтобы это {удивительное|крутое|простое|важное|бесполезное} тестовое предложение {изменялось {быстро|мгновенно|оперативно|правильно} случайным образом|менялось каждый раз}. ';

$str = '{qwerty|uiop|hhu{111|222}hu|888}';

$parser = new StringParser($mainString);

//$parser->printVariants();









