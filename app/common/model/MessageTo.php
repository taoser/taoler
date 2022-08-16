<?php
/*
 * @Author: TaoLer <317927823@qq.com>
 * @Date: 2021-12-06 16:04:50
 * @LastEditTime: 2022-08-16 12:01:52
 * @LastEditors: TaoLer
 * @Description: 优化版
 * @FilePath: \TaoLer\app\common\model\MessageTo.php
 * Copyright (c) 2020~2022 https://www.aieok.com All rights reserved.
 */
namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class MessageTo extends Model
{
	use SoftDelete;	
	protected $deleteTime = 'delete_time';
	protected $defaultSoftDelete = 0;
	//用户关联评论
	public function user()
	{
		return $this->hasMany('User','user_id','id');
	}
	
	public function messages()
	{
		//评论关联用户
		return $this->belongsTo('Message','message_id','id');
	}

	//得到消息数
	public function getMsgNum($id)
	{
		$msg = $this::where(['receve_id'=>$id,'is_read'=>0])->column('id');
		if($num = count($msg)) {
			return $num;
		} else {
			return 0;
		}
	}

	
}