<?php

namespace Nece001\PhpCategoryTree;

/**
 * 无限级分类树抽象类
 *
 * @Author nece001@163.com
 * @DateTime 2023-05-11
 */
abstract class CategoryTreeAbstract
{
    /**
     * 序号单节的长度
     *
     * @var integer
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     */
    protected $node_length = 4;

    /**
     * 主键字段名
     *
     * @var string
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     */
    protected $field_id = 'id';

    /**
     * 父级ID字段名
     *
     * @var string
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     */
    protected $field_parent_id = 'parent_id';

    /**
     * 节点序号字段名
     *
     * @var string
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     */
    protected $field_node_no = 'node_no';

    /**
     * 节点路径字段名
     *
     * @var string
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     */
    protected $field_node_path = 'node_path';

    /**
     * 节点层级字段名
     *
     * @var string
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     */
    protected $field_node_level = 'node_level';

    /**
     * 根据ID获取记录
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $id
     *
     * @return \ArrayAccess
     */
    abstract public function getById($id);

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
    abstract public function getAllChildsByParentNodeNo($node_no);


    /**
     * 获取ID节点的前一兄弟节点
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $id
     *
     * @return \ArrayAccess
     */
    abstract protected function getPreviousSibling($id);

    /**
     * 获取ID节点的后一兄弟节点
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $id
     *
     * @return \ArrayAccess
     */
    abstract protected function getNextSibling($id);

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
    abstract protected function getChildMaxNoOfParent($parent_id);

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
    abstract protected function childListIsEmpty($list);

    /**
     * 构建序号
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $value
     *
     * @return string
     */
    protected function buildNodeNo($value)
    {
        return str_pad($value, $this->node_length, '0', STR_PAD_LEFT);
    }

    /**
     * 构建当前序号的下一个序号
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param string $current_max_node_no
     *
     * @return string
     */
    protected function buildNextNodeNo($current_max_node_no)
    {
        $next = 1;
        if ($current_max_node_no) {
            $length = strlen($current_max_node_no);
            $pos = $length > $this->node_length ? $length - $this->node_length : 0;
            $next = intval(substr($current_max_node_no, $pos)) + 1;
        }
        $right = $this->buildNodeNo($next);

        return $right;
    }

    /**
     * 给节点附加数据
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param \ArrayAccess $model
     * @param \ArrayAccess $parent
     * @param string $current_max_node_no
     *
     * @return \ArrayAccess
     */
    protected function appendNodeValue($model, $parent, $current_max_node_no)
    {
        $parent_node = array(
            'id' => ($parent && isset($parent[$this->field_id])) ? $parent[$this->field_id] : 0,
            'node_level' => ($parent && isset($parent[$this->field_node_level])) ? $parent[$this->field_node_level] : 0,
            'node_no' => ($parent && isset($parent[$this->field_node_no])) ? $parent[$this->field_node_no] : '',
            'node_path' => ($parent && isset($parent[$this->field_node_path])) ? $parent[$this->field_node_path] : '',
        );

        $next_node_no = $parent_node['node_no'] . $this->buildNextNodeNo($current_max_node_no);
        $parent_id = isset($parent[$this->field_id]) ? $parent[$this->field_id] : 0;

        $model[$this->field_parent_id] = $parent_node['id'];
        $model[$this->field_node_no] = $next_node_no;
        $model[$this->field_node_path] = ($parent_node['node_path'] != '' ? $parent_node['node_path'] . ',' : '') . $parent_id;
        $model[$this->field_node_level] = $parent_node['node_level'] + 1;

        return $model;
    }

    /**
     * 创建新节点（给新创建节点附加节点数据）
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param \ArrayAccess $model
     *
     * @return \ArrayAccess
     */
    protected function createNode($model)
    {
        $parent = $this->getById($model[$this->field_parent_id]);
        $current_max_node_no = $this->getChildMaxNoOfParent($model[$this->field_parent_id]);
        return $this->appendNodeValue($model, $parent, $current_max_node_no);
    }

    /**
     * 更新节点（节点父级有变动时，子级节点数据一起更新）
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param \ArrayAccess $model
     * @param bool $is_parent_changed
     *
     * @return array 所有要更新的节点数组
     */
    protected function updateNode($model, $is_parent_changed)
    {
        $items = array();
        if ($is_parent_changed) {
            $parent = $this->getById($model[$this->field_parent_id]);
            $childs = $this->getAllChildsByParentNodeNo($model[$this->field_node_no]);

			$parent_id = $parent ? $parent[$this->field_id] : 0;

            $old_parent_node_no = $model[$this->field_node_no];
            $old_parent_path = $model[$this->field_node_path];
            $current_max_node_no = $this->getChildMaxNoOfParent($parent_id);
            $model = $this->appendNodeValue($model, $parent, $current_max_node_no);

            $items[] = $model;
            if (!$this->childListIsEmpty($childs)) {
                $old_parent_node_no_length = strlen($old_parent_node_no);
                $old_parent_path_length = strlen($old_parent_path);
                foreach ($childs as $child) {

                    if ($model[$this->field_id] != $child[$this->field_id]) {
                        // 替换序号父级部分
                        $node_no = substr_replace($child[$this->field_node_no], $model[$this->field_node_no], 0, $old_parent_node_no_length);
                        $node_path = substr_replace($child[$this->field_node_path], $model[$this->field_node_path], 0, $old_parent_path_length);

                        $child[$this->field_node_no] = $node_no;
                        $child[$this->field_node_path] = $node_path;

                        $items[] = $child;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * 移动（交换节点位置）
     *
     * @Author nece001@163.com
     * @DateTime 2023-05-11
     *
     * @param int $id 待移动的节点ID
     * @param boolean $up 移动方向（默认向前）
     *
     * @return array 所有要更新的节点数组
     */
    protected function exchangeNodeNo($id, $up = true)
    {
        $items = array();
        $node = $this->getById($id);
        if ($node) {
            if ($up) {
                $sibling = $this->getPreviousSibling($id);
            } else {
                $sibling = $this->getNextSibling($id);
            }

            if ($sibling) {
                $items = array($node, $sibling);

                $old_node_no = $node[$this->field_node_no];
                $old_sibling_no = $sibling[$this->field_node_no];
                $node[$this->field_node_no] = $old_sibling_no;
                $sibling[$this->field_node_no] = $old_node_no;

                $childs1 = $this->getAllChildsByParentNodeNo($old_node_no);
                $childs2 = $this->getAllChildsByParentNodeNo($old_sibling_no);

                if (!$this->childListIsEmpty($childs1)) {
                    $old_node_no_length = strlen($old_node_no);
                    foreach ($childs1 as $child) {

                        // 替换序号父级部分
                        $node_no = substr_replace($child[$this->field_node_no], $old_sibling_no, 0, $old_node_no_length);
                        $child[$this->field_node_no] = $node_no;
                        $items[] = $child;
                    }
                }

                if (!$this->childListIsEmpty($childs2)) {
                    $old_sibling_no_length = strlen($old_sibling_no);
                    foreach ($childs2 as $child) {
                        // 替换序号父级部分
                        $node_no = substr_replace($child[$this->field_node_no], $old_node_no, 0, $old_sibling_no_length);
                        $child[$this->field_node_no] = $node_no;
                        $items[] = $child;
                    }
                }
            }
        }

        return $items;
    }
}
