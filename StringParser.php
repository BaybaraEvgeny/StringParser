<?php


class StringParser
{
    private $result;

    private $strings;

    private const config = ['127.0.0.1', 'root', '', 'stringparser'];

    private $mysqli;

    public function __construct(string $str)
    {
        set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line) {
            throw new ErrorException ($err_msg, 0, $err_severity, $err_file, $err_line);
        });

        $this->strings = [];

        try {
            $this->parse($str);
        } catch (Exception $e) {
            echo 'Incorrect string input';
            echo $e->getMessage();
        }

        try {
            $this->recursiveMakeStrings($this->result, '');
        } catch (Exception $e) {
            echo 'Incorrect string input';
            echo $e->getMessage();
        }

        $this->dbConnect();
        $this->createTable();
        $this->insertIntoTable();

        echo PHP_EOL;

    }

    public function printVariants(): void
    {
        print_r($this->strings);
    }

    private function dbConnect()
    {
        $mysqli = new mysqli(...self::config);

        if ($mysqli->connect_errno) {
            echo "Ошибка: Не удалась создать соединение с базой MySQL и вот почему: \n";
            echo "Номер ошибки: " . $mysqli->connect_errno . "\n";
            echo "Ошибка: " . $mysqli->connect_error . "\n";

            exit;
        }

        echo "Successful Mysql Connection: " . $mysqli->host_info . PHP_EOL;

        $this->mysqli = $mysqli;
    }

    private function createTable()
    {
        if (!$this->mysqli->query("
                CREATE TABLE IF NOT EXISTS strings(
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    string LONGTEXT NOT NULL)")) {
            echo "Не удалось создать таблицу: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
        }

        echo "Table Created" . PHP_EOL;

    }

    private function insertIntoTable()
    {
        foreach ($this->strings as $string) {
            $res = $this->mysqli->query("
                SELECT * FROM strings
                    WHERE string = '$string'");
            if (!$res->fetch_all()) {
                $this->mysqli->query("INSERT INTO strings VALUES (NULL, '$string')");
            }

        }

        echo "Table Filled" . PHP_EOL;

    }

    private function parse(string $str): void
    {
        $this->result = $str;
        $this->recursiveParse($this->result);
    }

    private function recursiveParse(&$obj)
    {
        $obj = $this->parseStep($obj);

        if (gettype($obj) != 'string') {
            foreach ($obj as $key => $item) {
                $this->recursiveParse($obj[$key]);
            }
            unset($item);
        }

        return $obj;

    }

    private function parseStep($str)
    {
        $matches = [];
        if (gettype(mb_stripos($str, '{') == 'integer')) {
            $matches = $this->getMatchesPos($str);
        }

        $result_set = [];

        if ($matches) {
            if (gettype($matches) == 'array') {
                foreach ($matches as $key => $match) {
                    if ($key == 0) {
                        if ($match[0] != 0) {
                            $result_set[] = mb_substr($str, 0, $match[0]);
                        }
                        $result_set[] = mb_substr($str, $match[0], $match[1] - $match[0] + 1);

                        if ($key != count($matches) - 1) {
                            if ($matches[$key + 1][0] - $matches[$key][1] - 1 > 0) {
                                $result_set[] = mb_substr($str, $match[1] + 1, $matches[$key + 1][0] - $matches[$key][1] - 1);
                            }
                        } else {
                            if ($match[1] != mb_strlen($str) - 1) {
                                $result_set[] = mb_substr($str, $match[1] + 1);
                            }
                        }
                        continue;
                    }

                    if ($key == count($matches) - 1) {
                        $result_set[] = mb_substr($str, $match[0], $match[1] - $match[0] + 1);
                        if ($match[1] != mb_strlen($str) - 1) {
                            $result_set[] = mb_substr($str, $match[1] + 1);
                        }
                        continue;
                    }

                    $result_set[] = mb_substr($str, $match[0], $match[1] - $match[0] + 1);

                    if ($matches[$key + 1][0] - $matches[$key][1] - 1 > 0) {
                        $result_set[] = mb_substr($str, $match[1] + 1, $matches[$key + 1][0] - $matches[$key][1] - 1);
                    }
                }
            } else if (gettype($matches) == 'object') {
                $result_set = $matches;
            }
        } else {
            $result_set = $str;
        }

        return $result_set;

    }

    private function getMatchesPos($str)
    {
        $matches = [];
        $balance = 0;
        $started = false;

        $startpos = -1;
        $endpos = -1;

        for ($i = 0; $i < mb_strlen($str); $i++) {
            if (mb_substr($str, $i, 1) == '{') {
                $balance++;
                if (!$started) {
                    $started = true;
                    $startpos = $i;
                }
            }

            if (mb_substr($str, $i, 1) == '}') {
                $endpos = $i;
                $balance--;
            }

            if ($started && $balance == 0) {
                $matches[] = [$startpos, $endpos];
                $started = false;
            }

            if (mb_substr($str, 0, 1) == '{' && mb_substr($str, -1, 1) == '}' && $balance == 1) {
                return $this->parseObject($str);
            }

        }

        return $matches;

    }

    private function parseObject($str): object
    {
        $bars = [];

        $str = mb_substr($str, 1, mb_strlen($str) - 2);

        $balance = 0;

        for ($i = 0; $i < mb_strlen($str); $i++) {
            switch (mb_substr($str, $i, 1)) {
                case '{' :
                    $balance++;
                    break;
                case '}' :
                    $balance--;
                    break;
                case '|' :
                    if ($balance == 0) {
                        $bars[] = $i;
                    }
                    break;
            }
        }

        $result_set = new ArrayObject();

        foreach ($bars as $key => $bar) {
            if ($key == 0) {
                $result_set[] = mb_substr($str, 0, $bar);

                if ($key != count($bars) - 1) {
                    $result_set[] = mb_substr($str, $bar + 1, $bars[$key + 1] - $bar - 1);
                } else {
                    $result_set[] = mb_substr($str, $bar + 1, mb_strlen($str) - $bar - 1);
                }

                continue;
            }

            if ($key == count($bars) - 1) {
                $result_set[] = mb_substr($str, $bar + 1, mb_strlen($str) - $bar - 1);
                continue;
            }

            $result_set[] = mb_substr($str, $bar + 1, $bars[$key + 1] - $bar - 1);
        }

        return $result_set;

    }

    private function recursiveMakeStrings($arr, string $prefix): void
    {
        if (gettype($arr) == 'string') {
            $this->strings[] = $prefix . $arr;
            return;
        }

        if (count($arr) == 0) {
            $this->strings[] = $prefix;
            return;
        }

        if (gettype($arr) == 'object') {
            $arr = [$arr];
        }

        switch (gettype($arr[0])) {
            case 'string' :
                $this->recursiveMakeStrings(array_slice($arr, 1), $prefix . $arr[0]);
                break;
            case 'array' :
                $arrayPart = $arr[0];
                $newArr = array_slice($arr, 1);
                $newArr = array_merge($arrayPart, $newArr);
                $this->recursiveMakeStrings($newArr, $prefix);
                break;
            case 'object' :
                foreach ($arr[0] as $item) {
                    $newArr = $arr;
                    $newArr[0] = $item;
                    $this->recursiveMakeStrings($newArr, $prefix);
                }
                break;
        }

    }

}