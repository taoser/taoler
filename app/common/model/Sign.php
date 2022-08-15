<?php
/*
 * @Author: TaoLer <317927823@qq.com>
 * @Date: 2021-12-06 16:04:50
 * @LastEditTime: 2022-08-14 07:32:32
 * @LastEditors: TaoLer
 * @Description: 签到模型
 * @FilePath: \github\TaoLer\app\common\model\Sign.php
 * Copyright (c) 2020~2022 https://www.aieok.com All rights reserved.
 */
namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use think\Db;
use think\facade\Session;

class Sign extends Model
{
	
	//用户关联评论
	public function user()
	{
		return $this->hasMany('User','user_id','id');
	}
	

	
}