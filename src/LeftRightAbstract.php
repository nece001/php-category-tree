<?php

namespace Nece001\PhpCategoryTree;

/**
 * 左右值算法
 *
 * @Author nece001@163.com
 * @DateTime 2023-11-18
 */
abstract class LeftRightAbstract
{
    abstract protected function startTrans();
    abstract protected function commit();
    abstract protected function rollback();
    abstract protected function createModel();

    protected function getModel()
    {
        static $model = null;
        if (is_null($model)) {
            $model = $this->createModel();
        }
        return $model;
    }
}
