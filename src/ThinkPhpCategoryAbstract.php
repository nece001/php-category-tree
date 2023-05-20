<?php

namespace Nece001\PhpCategoryTree;

/**
 * ThinkPHP的分类树抽象逻辑类
 *
 * @Author gjw
 * @DateTime 2023-05-20
 */
abstract class ThinkPhpCategoryAbstract extends CategoryTreeAbstract
{
    /**
     * 创建Model
     *
     * @Author gjw
     * @DateTime 2023-05-20
     *
     * @return \think\model
     */
    abstract public function createModel();

    protected $model;

    /**
     * 获取Model实例（用于查询）
     *
     * @Author gjw
     * @DateTime 2023-05-20
     *
     * @return \think\model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->createModel();
        }
        return $this->model;
    }

    /**
     * 保存新创建的节点
     *
     * @Author gjw
     * @DateTime 2023-05-20
     *
     * @param \think\model $item
     *
     * @return \think\model
     */
    protected function saveCreateItem($item)
    {
        $this->createNode($item);
        $item->save();
    }

    /**
     * 保存更新的节点
     *
     * @Author gjw
     * @DateTime 2023-05-20
     *
     * @param \think\model $item 传过来的节点应该保持原父级ID
     * @param integer $parent_id 新父级ID（更换父级的情况）或原父级ID
     *
     * @return void
     */
    protected function saveUpdateItem($item, $parent_id)
    {
        if ($item) {
            $is_parent_changed = $item->parent_id != $parent_id;
            $item->parent_id = $parent_id;
            $items = $this->updateNode($item, $is_parent_changed);
            foreach ($items as $itm) {
                $itm->save();
            }
            $item->save();
        }
    }

    /**
     * 交换位置
     *
     * @Author gjw
     * @DateTime 2023-05-20
     *
     * @param int $id
     * @param bool $up
     *
     * @return void
     */
    public function move($id, $up)
    {
        $items = $this->exchangeNodeNo($id, $up);
        foreach ($items as $item) {
            $item->save();
        }
    }


    /**
     * 根据ID获取记录
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $id
     *
     * @return \think\model
     */
    public function getById($id)
    {
        return $this->getModel()->find($id);
    }

    /**
     * 获取父级所有子级
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param string $node_no
     *
     * @return \Iterator
     */
    public function getAllChildsByParentNodeNo($node_no)
    {
        $query = $this->getModel()->whereLike('node_no', $node_no . '%')->order('node_no');

        return $query->select();
    }


    /**
     * 获取ID节点的前一兄弟节点
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $id
     *
     * @return \think\model
     */
    protected function getPreviousSibling($id)
    {
        $item = $this->getById($id);
        $query = $this->getModel()->where('parent_id', $item->parent_id)->where('node_no', '<', $item->node_no)->order('node_no', 'DESC');

        return $query->find();
    }

    /**
     * 获取ID节点的后一兄弟节点
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $id
     *
     * @return \think\model
     */
    protected function getNextSibling($id)
    {
        $item = $this->getById($id);
        $query = $this->getModel()->where('parent_id', $item->parent_id)->where('node_no', '>', $item->node_no)->order('node_no');

        return $query->find();
    }

    /**
     * 获取父级子节点的最大节点序号
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $parent_id
     *
     * @return string
     */
    protected function getChildMaxNoOfParent($parent_id)
    {
        $query = $this->getModel()->where('parent_id', $parent_id)->order('node_no', 'DESC');
        $item = $query->find();
        if ($item) {
            return $item->node_no;
        }

        return '';
    }

    /**
     * 子级列表是否为空
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param \Iterator $list
     *
     * @return bool
     */
    protected function childListIsEmpty($list)
    {
        if ($list) {
            if (is_array($list)) {
                return empty($list);
            }

            if ($list instanceof \think\Collection) {
                return $list->isEmpty();
            }
        }
        return true;
    }
}
