# Тестовое задание StringParser

Использование:

Настроить конфигурацию MySQL в классе StringParser.php:

```private const config = ['127.0.0.1', 'root', '', 'stringparser'];```

Импортировать класс в любое место:

```require 'StringParser.php';```

Инициализировать с помощью строки:

```$parser = new StringParser($mainString);```

Напечатать результат в консоль:

```$parser->printVariants();```

StringParser.php - главный класс

StartPoint.php - скрипт инициализации

При вводе некорректных строк работоспособность не гарантируется!
 
