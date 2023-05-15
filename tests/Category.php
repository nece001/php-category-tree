<?php

use Nece001\PhpCategoryTree\CategoryTreeAbstract;

require_once '../src/CategoryTreeAbstract.php';

class Category extends CategoryTreeAbstract
{
    private $data = array();

    public function __construct()
    {
        $this->buildData(3);
    }

    public function create($parent_id)
    {
        $this->addItem($parent_id);
        // $this->addItem($parent_id);
        // $this->addItem($parent_id);
    }

    public function update($id, $parent_id)
    {
        $row = $this->getById($id);

        $row['parent_id'] = $parent_id;
        $items = $this->updateNode($row, true);

        foreach ($items as $item) {
            $this->data[$item['id']] = $item;
        }
    }


















    public function getData()
    {
        return $this->data;
    }

    public function getById($id)
    {
        return isset($this->data[$id]) ? $this->data[$id] : null;
    }

    public function getAllChildsByParentNodeNo($node_no)
    {
        $data = array();
        foreach ($this->data as $row) {
            if (false !== strpos($row['node_no'], $node_no)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    public function getChildByParentId($parent_id)
    {
        $data = array();
        foreach ($this->data as $row) {
            if ($row['parent_id'] == $parent_id) {
                $data[] = $row;
            }
        }
        return $data;
    }

    protected function getPreviousSibling($id)
    {
        $prev = null;
        $item = $this->getById($id);
        if ($item) {
            $items = $this->getChildByParentId($item['parent_id']);
            $rows = self::multiArraySort($items, 'node_no', SORT_ASC);

            foreach ($rows as $row) {
                if ($row['id'] == $id) {
                    break;
                }
                $prev = $row;
            }
        }

        return $prev;
    }

    protected function getNextSibling($id)
    {
        $prev = null;
        $item = $this->getById($id);
        if ($item) {
            $items = $this->getChildByParentId($item['parent_id']);
            $rows = self::multiArraySort($items, 'node_no');

            foreach ($rows as $row) {
                if ($row['id'] == $id) {
                    break;
                }
                $prev = $row;
            }
        }

        return $prev;
    }

    protected function getChildMaxNoOfParent($parent_id)
    {
        $node_no = '';
        $items = $this->getChildByParentId($parent_id);
        $rows = self::multiArraySort($items, 'node_no');
        if ($rows) {
            $row = current($rows);
            if ($row) {
                $node_no = $row['node_no'];
            }
        }

        return $node_no;
    }

    protected function childListIsEmpty($list)
    {
        return count($list) == 0;
    }

    private function buildData($total = 10)
    {
        for ($i = 0; $i < $total; $i++) {
            $this->addItem(0);
        }
    }

    private function addItem($parent_id)
    {
        $count = count($this->data);
        $id = $count + 1;
        $row = $this->buildItem($id, $parent_id);
        $this->data[$id] = $this->crreateNode($row);
    }

    private function buildItem($id, $parent_id)
    {
        return array(
            'id' => $id,
            'parent_id' => $parent_id,
            'node_no' => '',
            'node_path' => '',
            'node_level' => 0,
        );
    }

    public static function multiArraySort(array $arr, string $sortKey, int $sort = SORT_DESC): array
    {
        $keyArr = [];
        foreach ($arr as $subArr) {
            if (!is_array($subArr) || !isset($subArr[$sortKey])) {
                return [];
            }
            array_push($keyArr, $subArr[$sortKey]);
        }
        array_multisort($keyArr, $sort, $arr);

        return $arr;
    }
}
