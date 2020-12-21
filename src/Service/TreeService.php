<?php


namespace Ycbl\AdminAuth\Service;


use Hyperf\Utils\Context;

class TreeService
{
    protected $pidName = 'pid';
    /**
     * @var array
     */
    private $tree;

    public function init(array $arr)
    {
        $this->tree = $arr;
        return $this;
    }

    public function getArr()
    {
        return $this->tree;
    }

    /**
     * 根据ID获取子集
     * @param $my_id
     * @return array
     */
    public function getChild($my_id)
    {
        $newArr = [];
        foreach ($this->getArr() as $value) {
            if (!isset($value['id'])) {
                continue;
            }
            if ($value[$this->pidName] == $my_id) {
                $newArr[$value['id']] = $value;
            }
        }
        return $newArr;
    }

    /**
     * 读取指定节点的所有子节点
     * @param $my_id
     * @param false $withSelf
     * @return array
     */
    public function getChildren($my_id, $withSelf = false)
    {
        $newArr = [];
        foreach ($this->getArr() as $value) {
            //数组不包含ID就不会有子级
            if (!isset($value['id'])) {
                continue;
            }
            if ($value[$this->pidName] == $my_id) {
                $newArr[] = $value;
                //递归获取子级数组并合并
                $newArr = array_merge($newArr, $this->getChildren($value['id']));
            } elseif ($withSelf && $value['id'] == $my_id) {
                $newArr[] = $value;
            }
        }
        return $newArr;
    }

    /**
     * 读取指定节点的所有孩子节点ID
     * @param $my_id
     * @param boolean $withSelf 是否包含自身
     * @return array
     */
    public function getChildrenIds($my_id, $withSelf = false)
    {
        $childrenList = $this->getChildren($my_id, $withSelf);
        $childrenIds = [];
        foreach ($childrenList as $k => $v) {
            $childrenIds[] = $v['id'];
        }
        return $childrenIds;
    }

    /**
     * 得到当前位置父辈数组
     * @param int
     * @return array
     */
    public function getParent($my_id)
    {
        $pid = 0;
        $newArr = [];
        foreach ($this->getArr() as $value) {
            //没有id 不会是上级节点
            if (!isset($value['id'])) {
                continue;
            }
            //查找到自己的位置
            if ($value['id'] == $my_id) {
                //获取到PID
                $pid = $value[$this->pidName];
                break;
            }
        }
        //如果pid存在
        if ($pid) {
            foreach ($this->getArr() as $value) {
                //获取上级数组
                if ($value['id'] == $pid) {
                    $newArr[] = $value;
                    break;
                }
            }
        }
        return $newArr;
    }

    /**
     * 得到当前位置所有父辈数组
     * @param $my_id
     * @param bool $withSelf 是否包含自己
     * @return array
     */
    public function getParents($my_id, $withSelf = false)
    {
        $pid = 0;
        $newArr = [];
        foreach ($this->getArr() as $value) {
            //没有id 不会是上级节点
            if (!isset($value['id'])) {
                continue;
            }
            //查找到自己的位置
            if ($value['id'] == $my_id) {
                //如果包含自己则添加自身
                if ($withSelf) {
                    $newArr[] = $value;
                }
                $pid = $value[$this->pidName];
                break;
            }
        }
        //如果PID存在
        if ($pid) {
            //递归获取上级数组
            $arr = $this->getParents($pid, true);
            $newArr = array_merge($arr, $newArr);
        }
        return $newArr;
    }

    /**
     * 读取指定节点所有父类节点ID
     * @param $my_id
     * @param boolean $withSelf
     * @return array
     */
    public function getParentsIds($my_id, $withSelf = false)
    {
        $parentList = $this->getParents($my_id, $withSelf);
        $parentsIds = [];
        foreach ($parentList as $k => $v) {
            $parentsIds[] = $v['id'];
        }
        return $parentsIds;
    }

    public function getTreeArray($my_id)
    {
        $childes = $this->getChild($my_id);
        $num = 0;
        $data = [];
        if ($childes) {
            foreach ($childes as $id => $value) {
                $data[$num] = $value;
                $data[$num]['childList'] = $this->getTreeArray($id);
                $num++;
            }
        }
        return $data;
    }

    public function getTreeList($data = [], $field = 'name')
    {
        $arr = [];
        foreach ($data as $key => $value) {
            $child_list = isset($value['childList']) ? $value['childList'] : [];
            unset($value['childList']);
            $value['hasChild'] = $child_list ? 1 : 0;
            if ($value['id']) {
                $arr[] = $value;
            }
            if ($child_list) {
                $arr = array_merge($arr, $this->getTreeList($child_list, $field));
            }
        }
        return $arr;
    }

}