<?php
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

use taoler\com\Files;
use think\facade\Cache;

    $taglib_pre_load = Cache::remember('taglib', function(){
        $tagsArr = [];
        //获取应用公共标签app/common/taglib
        $common_taglib = Files::getAllFile(root_path().'app/common/taglib');
        foreach ($common_taglib as $t) {
            $tagsArr[] = str_replace('/','\\',strstr(strstr($t, 'app/'), '.php', true));
        }

        //获取插件下标签 addons/taglib文件
        $localAddons = Files::getDirName('../addons/');
        foreach($localAddons as $v) {
            $dir = root_path(). 'addons'. DIRECTORY_SEPARATOR . $v . DIRECTORY_SEPARATOR .'taglib';
            if(!file_exists($dir)) continue;
            $addons_taglib = Files::getAllFile($dir);
            foreach ($addons_taglib as $a) {
                $tagsArr[] = str_replace('/','\\',strstr(strstr($a, 'addons'), '.php', true));
            }
        }
        return implode(',', $tagsArr);
    });

return [
    // 模板引擎类型使用Think
    'type'          => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'     => 1,
    // 模板后缀
    'view_suffix'   => 'html',
    // 模板文件名分隔符
    'view_depr'     => DIRECTORY_SEPARATOR,
    // 模板引擎普通标签开始标记
    'tpl_begin'     => '{',
    // 模板引擎普通标签结束标记
    'tpl_end'       => '}',
    // 标签库标签开始标记
    'taglib_begin'  => '{',
    // 标签库标签结束标记
    'taglib_end'    => '}',
	
	'default_filter' => 'htmlspecialchars',
    // 预先加载的标签库
    'taglib_pre_load' => $taglib_pre_load,

	//模板输出替换
	'tpl_replace_string'  =>  [
    '__STATIC__'=>'/static/layui',
	'__JS__' => '/static/res/',
	]
];
