<?php /*a:3:{s:44:"E:\github\TaoLer\view\index\index\index.html";i:1731046101;s:48:"E:\github\TaoLer\addons\ads\view\ads_slider.html";i:1686394417;s:48:"E:\github\TaoLer\addons\addonhook\view\hook.html";i:1730470933;}*/ ?>
<!-- 插件位管理工具 --><?php if(is_array($hooks) || $hooks instanceof \think\Collection || $hooks instanceof \think\Paginator): $i = 0; $__LIST__ = $hooks;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;switch($vo['hook_type']): case "1": if(app('request')->controller() == 'Index' && app('request')->action() == 'index'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "2": if(app('request')->controller() == 'Article' && app('request')->action() == 'cate'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "3": if(app('request')->controller() == 'Article' && app('request')->action() == 'detail'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "4": if(app('request')->controller() == 'Search'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "5": if(app('request')->controller() == 'Tag'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "6": if(app('request')->controller() == 'Login' && app('request')->action() == 'reg'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "7": if(app('request')->controller() == 'Login' && app('request')->action() == 'index'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "8": if(app('request')->controller() == 'User'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "9": if(app('request')->controller() == 'User' && app('request')->action() == 'home'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; case "10": if(app('request')->action() == 'add' || app('request')->action() == 'edit' || app('request')->action() == 'detail'): ?><?php echo hook($vo['hook_name']); ?><?php endif; break; default: ?><?php echo hook($vo['hook_name']); ?><?php endswitch; ?><?php endforeach; endif; else: echo "" ;endif; ?>