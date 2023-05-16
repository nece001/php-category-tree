<?php

require_once './Category.php';

class CategoryTest
{
    public function testCreate()
    {
        $c = new Category();

        // 创建子分类
        $c->create(2);
        $c->create(3);
        echo '创建子分类后的结果', PHP_EOL;
        printTable($c->getData());

        // 修改父级
        $c->update(2, 3);
        echo '1.修改父级后的结果', PHP_EOL;
        printTable($c->getData());

        $c->update(2, 1);
        echo '2.修改父级后的结果', PHP_EOL;
        printTable($c->getData());

        $c->moveUp(6);
        echo '1.移动后的结果', PHP_EOL;
        printTable($c->getData());

        $c->moveUp(3);
        echo '2.移动后的结果', PHP_EOL;
        printTable($c->getData());

        $c->moveDown(4);
        echo '3.移动后的结果', PHP_EOL;
        printTable($c->getData());
    }
}


$a = new CategoryTest();
$a->testCreate();


function printTable($data)
{
    $headers = array_keys(current($data));
    foreach ($headers as $name) {
        printf('%20s|', $name);
    }

    echo PHP_EOL;

    foreach ($data as $row) {
        foreach ($row as $value) {
            printf('%-20s|', $value);
        }

        echo PHP_EOL;
    }
    echo PHP_EOL;
}
