<?php

namespace Nece001\PhpCategoryTree;

use Throwable;

abstract class LeftRightTp6 extends LeftRightAbstract
{

    protected function getModel()
    {
        static $model = null;
        if (is_null($model)) {
            $model = $this->createModel();
        }
        return $model;
    }

    protected function startTrans()
    {
        $this->getModel()->startTrans();
    }

    protected function commit()
    {
        $this->getModel()->commit();
    }

    protected function rollback()
    {
        $this->getModel()->rollback();
    }

    protected function getRoot()
    {
        $item = $this->getModel()->where('parent_id', 0)->find();
        if (!$item) {
            $item = $this->createModel();
            $item->title = '顶级节点';
            $item->parent_id = 0;
            $item->left = 1;
            $item->right = 2;
            $item->save();
        }
        return $item;
    }

    protected function getById($id)
    {
        return $this->getModel()->where('id', $id)->find();
    }

    public function create($catalog)
    {
        $this->startTrans();
        try {
            $item = $this->createNode($catalog->parent_id);
            $item->title = $catalog->title;
            $item->save();

            $catalog->id = $item->id;
            $this->commit();

            return $catalog->id;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function update($catalog)
    {
        $this->startTrans();
        try {
            $item = $this->updateNode($catalog->id, $catalog->parent_id);

            $data = array(
                'parent_id' => $catalog->parent_id,
                'title' => $catalog->title,
            );

            $item->save($data);
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function createNode($parent_id = 0)
    {
        if ($parent_id) {
            $parent = $this->getById($parent_id);
            $left = $parent->right;
            $right = $parent->right + 1;
        } else {
            $parent = $this->getRoot();
            $left = $parent->right;
            $right = $parent->right + 1;
        }

        // 更新原有节点：
        // 头部节点：左值<新节点左值 & 右值>=新节点左值：左值不变，右值+=2
        // 尾部节点：左值>=新节点左值：左值+=2,右值+=2
        $this->getModel()->where('left', '<', $left)->where('right', '>=', $left)->inc('right', 2)->update();
        $this->getModel()->where('left', '>=', $left)->inc('left', 2)->inc('right', 2)->update();

        $item = $this->createModel();
        $item->parent_id = $parent->id;
        $item->left = $left;
        $item->right = $right;
        return $item;
    }

    public function delete($id, $force = false)
    {
        $item = $this->getById($id);
        if ($item) {

            if (!$force) {
                $count = $this->getModel()->where('parent_id', $item->parent_id)->count();
                if ($count) {
                    throw new \Exception('has child!');
                }
            }

            $this->startTrans();
            try {
                $offset = $item->right - $item->left + 1;
                $this->getModel()->where('left', '>=', $item->left)->where('right', '<=', $item->right)->delete();
                $this->getModel()->where('left', '<', $item->left)->where('right', '>', $item->left)->inc('right', -$offset)->update();
                $this->getModel()->where('left', '>', $item->right)->inc('left', -$offset)->inc('right', -$offset)->update();
                $this->commit();
            } catch (Throwable $e) {
                $this->rollback();
                throw $e;
            }
        }
    }

    protected function updateNode($id, $parent_id)
    {
        $item = $this->getById($id);
        if ($item->parent_id != $parent_id) {
            // 父级改变
            $parent = $this->getById($parent_id);
            $count = intval(($item->right - $item->left - 1) / 2) + 1;
            $offset = $count * 2;

            if ($item->left > $parent->left) {
                // 跨越的偏移量
                $pre_offset = $item->right - $parent->right + 1;

                // 从后往前移
                // 插入位置后移：前部节点：left<=$parent->left & right>=$parent->right，左值不变，右值+=偏移量
                //              后部节点：left>$parent->right，左值+=偏移量，右值+=偏移量
                // 获取更新后的移动节点
                // 移动节点前移：left>=$item->left & right<=$item->right，左值-=前移偏移量，右值-=前移偏移量
                // 移动节点空出来的位置前移：前部节点：left<$item->left & right>$item->left，左值不变，右值-=偏移量
                //                         后部节点：left>$item->right，左值-=偏移量，右值-=偏移量
                $this->getModel()->where('left', '<=', $parent->left)->where('right', '>=', $parent->right)->inc('right', $offset)->update();
                $this->getModel()->where('left', '>', $parent->right)->inc('left', $offset)->inc('right', $offset)->update();

                $item = $this->getById($id);
                $this->getModel()->where('left', '>=', $item->left)->where('right', '<=', $item->right)->inc('left', -$pre_offset)->inc('right', -$pre_offset)->update();

                $this->getModel()->where('left', '<=', $item->left)->where('right', '>=', $item->right)->inc('right', -$offset)->update();
                $this->getModel()->where('left', '>', $item->right)->inc('left', -$offset)->inc('right', -$offset)->update();
            } else {
                // 跨越的偏移量
                $pre_offset = $parent->right - $item->left;

                // 从前往后移
                // 插入位置后移：前部节点：left<=$parent->left & right>=$parent->right，左值不变，右值+=偏移量
                //              后部节点：left>$parent->right，左值+=偏移量，右值+=偏移量
                // 被移动节点：left>=$item->left & right<=$item->right，左值=左值-$item->left+节点数，右值=右值-$item->right+节点数
                // 移动点之后的节点前移：前部节点：left<$item->left & right>$item->right，左值不变，右值-=偏移量
                //                     后部节点：left>$item->right，左值-=偏移量，右值-=偏移量
                $this->getModel()->where('left', '<=', $parent->left)->where('right', '>=', $parent->right)->inc('right', $offset)->update();
                $this->getModel()->where('left', '>', $parent->right)->inc('left', $offset)->inc('right', $offset)->update();

                $this->getModel()->where('left', '>=', $item->left)->where('right', '<=', $item->right)->inc('left', $pre_offset)->inc('right', $pre_offset)->update();

                $this->getModel()->where('left', '<=', $item->left)->where('right', '>=', $item->right)->inc('right', -$offset)->update();
                $this->getModel()->where('left', '>', $item->right)->inc('left', -$offset)->inc('right', -$offset)->update();
            }
        }

        return $item;
    }

    public function forward($id)
    {
        $this->startTrans();
        try {
            $next = $this->getById($id);
            $prev = $this->getModel()->where('parent_id', $next->parent_id)->where('left', '<', $next->left)->order('left', 'desc')->find();
            if ($prev) {
                $offset = (intval(($next->right - $next->left - 1) / 2) + 1) * 2;
                $prev_offset = $offset + ((intval(($prev->right - $prev->left - 1) / 2) + 1) * 2);

                $this->getModel()->where('left', '<', $prev->left)->where('right', '>', $prev->right)->inc('right', $offset)->update();
                $this->getModel()->where('left', '>=', $prev->left)->inc('left', $offset)->inc('right', $offset)->update();

                $this->getModel()->where('left', '>=', $next->left + $offset)->where('right', '<=', $next->right + $offset)->inc('left', -$prev_offset)->inc('right', -$prev_offset)->update();

                $this->getModel()->where('left', '<=', $next->left + $offset)->where('right', '>=', $next->right + $offset)->inc('right', -$offset)->update();
                $this->getModel()->where('left', '>', $next->right + $offset)->inc('left', -$offset)->inc('right', -$offset)->update();
            }
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function backward($id)
    {
        $this->startTrans();
        try {
            $prev = $this->getById($id);
            $next = $this->getModel()->where('parent_id', $prev->parent_id)->where('left', '>', $prev->left)->order('left')->find();
            if ($next) {
                $offset = (intval(($next->right - $next->left - 1) / 2) + 1) * 2;
                $prev_offset = $offset + ((intval(($prev->right - $prev->left - 1) / 2) + 1) * 2);

                $this->getModel()->where('left', '<', $prev->left)->where('right', '>', $prev->right)->inc('right', $offset)->update();
                $this->getModel()->where('left', '>=', $prev->left)->inc('left', $offset)->inc('right', $offset)->update();

                $this->getModel()->where('left', '>=', $next->left + $offset)->where('right', '<=', $next->right + $offset)->inc('left', -$prev_offset)->inc('right', -$prev_offset)->update();

                $this->getModel()->where('left', '<=', $next->left + $offset)->where('right', '>=', $next->right + $offset)->inc('right', -$offset)->update();
                $this->getModel()->where('left', '>', $next->right + $offset)->inc('left', -$offset)->inc('right', -$offset)->update();
            }
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function children($parent_id)
    {
        $query = $this->getModel()->where('parent_id', $parent_id)->order('left');

        return $query->select();
    }

    public function navigation($id, $root_parent_id = 0)
    {
        $item = $this->getById($id);
        $query = $this->getModel()->where('left', '<=', $item->left)->where('right', '>=', $item->right)->order('left');
        if ($root_parent_id) {
            $parent = $this->getById($root_parent_id);
            $query->where('left', '>=', $parent->left)->where('right', '<=', $parent->right);
        }

        return $query->select();
    }

    public function tree($root_parent_id = 0)
    {
        $query = $this->getModel()->order('left');
        if ($root_parent_id) {
            $parent = $this->getById($root_parent_id);
            $query->where('left', '>=', $parent->left)->where('right', '<=', $parent->right);
        }

        $depth = array();
        $items = $query->select();
        foreach ($items as $item) {
            $item->depth = 0;
            if (isset($depth[$item->parent_id])) {
                $item->depth = $depth[$item->parent_id] + 1;
            }

            $depth[$item->id] = $item->depth;
        }
        return $items;
    }

    protected function listQuery()
    {
        $query = $this->getModel()
            ->field(array('node.*', '(COUNT(parent.id) - 1) as depth'))
            ->table('(' . $this->getModel()->db()->getTable() . ' as node, ' . $this->getModel()->db()->getTable() . ' as parent)')
            ->whereRaw('node.left between parent.left AND parent.right')
            ->group('node.id')
            ->order('node.left');

        return $query;
    }

    public function pagedList(array $params, $page, $size)
    {
        $query = $this->listQuery()->page($page)->limit($size);

        return $query->select();
    }

    public function paginate(array $params, $page, $size)
    {
        $query = $this->listQuery();

        return $query->paginate(
            array('list_rows' => $size, 'page' => $page)
        );
    }
}
