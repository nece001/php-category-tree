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

    public function toTree($items)
    {
        $depth = array();
        foreach ($items as $item) {
            $item->depth = 0;
            if (isset($depth[$item->parent_id])) {
                $item->depth = $depth[$item->parent_id] + 1;
            }

            $depth[$item->id] = $item->depth;
        }
        return $items;
    }
}
