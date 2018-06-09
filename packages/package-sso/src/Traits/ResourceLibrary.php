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

			return $this->get('api/staff/list', ['staff' => $params]);
		}

		return $this->get('api/staff/'.$params);
	}
}