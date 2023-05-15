<?php

require_once './Category.php';

class CategoryTest
{
    public function testCreate()
    {
        $c = new Category();
        $c->create(2);

        print_r($c->getData());

        $c->update(2, 3);

        print_r($c->getData());
    }
}


$a = new CategoryTest();
$a->testCreate();
