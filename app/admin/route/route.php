<?php
/*
 * @Author: TaoLer <alipay_tao@qq.com>
 * @Date: 2021-12-06 16:04:50
 * @LastEditTime: 2022-05-07 18:33:23
 * @LastEditors: TaoLer
 * @Description: admin路由配置
 * @FilePath: \TaoLer\app\admin\route\route.php
 * Copyright (c) 2020~2022 https://www.aieok.com All rights reserved.
 */
use think\facade\Route;

// 动态详情页URL别称
$detail_as = config('taoler.url_rewrite.article_as');

Route::get('captcha/[:config]','\\think\\captcha\\CaptchaController@index');
Route::get("$detail_as<id>$", '\app\index\controller\Article@detail')->name('detail_id');
