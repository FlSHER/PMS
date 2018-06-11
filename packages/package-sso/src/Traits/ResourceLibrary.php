<?php

namespace Fisher\SSO\Traits;

trait ResourceLibrary
{
    /**
     * 获取员工信息.
     * 
     * @param $params 非数组为员工主键
     * @return mixed
     */
    public function getStaff($params)
    {
        if (is_array($params)) {

            return $this->get('api/staff', $params);
        }

        return $this->get('api/staff/'.$params);
    }

    /**
     * 获取部门信息.
     * 
     * @author 28youth
     * @param  $params 非数组为部门主键
     * @return mixed
     */
    public function getDepartmenet($params)
    {
        if (is_array($params)) {
            
            return $this->get('api/department', $params);
        }

        return $this->get('api/department/'.$params);
    }

    /**
     * 获取品牌信息.
     * 
     * @author 28youth
     * @param  非数组为品牌主键
     * @return mixed
     */
    public function getBrand($params)
    {
        if (is_array($params)) {
            
            return $this->get('api/brand', $params);
        }

        return $this->get('api/brand/'.$params);
    }

    /**
     * 获取位置信息.
     * 
     * @author 28youth
     * @param  非数组为主键
     * @return mixed
     */
    public function getPosition($params)
    {
        if (is_array($params)) {
            
            return $this->get('api/position', $params);
        }

        return $this->get('api/position/'.$params);
    }

    /**
     * 获取商品信息.
     * 
     * @author 28youth
     * @param  非数组为商品主键
     * @return mixed
     */
    public function getShop($params)
    {
        if (is_array($params)) {
            
            return $this->get('api/shop', $params);
        }

        return $this->get('api/shop/'.$params);
    }

    /**
     * 获取用户角色信息.
     * 
     * @param  非数组为主键
     * @return mixed
     */
    public function getRole($params)
    {
        if (is_array($params)) {
            
            return $this->get('api/role', $params);
        }

        return $this->get('api/role/'.$params);
    }
    
}