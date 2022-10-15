<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\lib\SqlFile;
use think\Exception;
use think\facade\View;
use think\facade\Request;
use think\facade\Config;
use app\admin\model\Addons as AddonsModel;
use taoler\com\Files;
use taoler\com\Api;
use app\common\lib\Zip;
use think\response\Json;
use app\admin\model\AuthRule;
use Symfony\Component\VarExporter\VarExporter;

class Addons extends AdminController
{
    /**
     * @return string
     */
    public function index()
    {
		return View::fetch();
    }

    /**
     * 插件动态列表
     * @return Json
     */
    public function addonsList()
    {
		$data = Request::param();
        $res = [];
        //本地插件列表
        $addonsList = Files::getDirName('../addons/');
        $url = $this->getSystem()['api_url'].'/v1/addons';
        $addons = Api::urlGet($url);

        switch($data['type']){
            //已安装
            case 'installed':
                if($addonsList){
                    $res = ['code'=>0,'msg'=>'','count'=>5];
                        foreach($addonsList as $v){
                            $info_file = '../addons/'.$v.'/info.ini';
                            $info = parse_ini_file($info_file);
							$info['show'] = $info['status'] ? '启用' : '禁用';
							$info['install'] = $info['status'] ? '是' : '否';
                            $res['data'][] = $info;
                        }
                    $res['col'] = [
                        ['type' => 'numbers'],
                        ['field' => 'name','title'=> '插件', 'width'=> 120],
                        ['field'=> 'title','title'=> '标题', 'width'=> 100],
                        ['field'=> 'version','title'=> '版本', 'templet' => '<div>{{d.version}}</div>', 'width'=> 60],
                        ['field' => 'author','title'=> '作者', 'width'=> 80],
                        ['field' => 'description','title'=> '简介', 'minWidth'=> 200],
                        ['field' => 'install','title'=> '安装', 'width'=> 100],
                        ['field' => 'ctime','title'=> '到期时间', 'width'=> 100],
                        ['field' => 'status','title'=> '状态', 'width'=> 95, 'templet' => '#buttonStatus'],
                        ['title' => '操作', 'width'=> 150, 'align'=>'center', 'toolbar'=> '#addons-installed-tool']
                    ];
                } else {
					$res = ['code'=>-1,'msg'=>'没有安装任何插件'];
				}
                break;
            //在线全部
            case 'onlineAddons':
					if( $addons->code !== -1){
                        // 与本地文件对比
                        foreach($addons->data as $v){
                            switch ($data['selector']) {
                                case 'free':
                                    if($v->price == 0) {
                                        if(in_array($v->name,$addonsList)) {
                                            $info = get_addons_info($v->name);
                                            //已安装
                                            $v->isInstall = 1;
                                            //判断是否有新版本
                                            if($v->version > $info['version']) $v->have_newversion = 1;
                                            $v->price =  $v->price ? $v->price : '免费';
                                        }
                                        $res['data'][] = $v;
                                    }
                                    break;
                                case 'pay':
                                    if($v->price > 0) {
                                        if(in_array($v->name,$addonsList)) {
                                            $info = get_addons_info($v->name);
                                            //已安装
                                            $v->isInstall = 1;
                                            //判断是否有新版本
                                            if($v->version > $info['version']) $v->have_newversion = 1;
                                            $v->price =  $v->price ? $v->price : '免费';
                                        }
                                        $res['data'][] = $v;
                                    }
                                    break;
                                case 'all':
                                    if(in_array($v->name,$addonsList)) {
                                        $info = get_addons_info($v->name);
                                        //已安装
                                        $v->isInstall = 1;
                                        //判断是否有新版本
                                        if($v->version > $info['version']) $v->have_newversion = 1;
                                        $v->price =  $v->price ? $v->price : '免费';
                                    }
                                    $res['data'][] = $v;
                                    break;
                            }
                        };

						$res['code'] = 0;
						$res['msg'] = '';
						$res['col'] = [
							['type' => 'numbers'],
							['field' => 'title','title'=> '插件', 'width'=> 200],
                            ['field' => 'description','title'=> '简介', 'minWidth'=> 200],
							['field' => 'author','title'=> '作者', 'width'=> 100],
							['field' => 'price','title'=> '价格(元)','width'=> 85],
							['field' => 'downloads','title'=> '下载', 'width'=> 70],
                            ['field' => 'version','title'=> '版本', 'templet' => '<div>{{d.version}} {{#  if(d.have_newversion == 1){ }}<span class="layui-badge-dot"></span>{{#  } }}</div>','width'=> 75],
                            ['field' => 'status','title'=> '在线', 'width'=> 70],
							['title' => '操作', 'width'=> 150, 'align'=>'center', 'toolbar'=> '#addons-tool']
						];
					} else {
						$res = ['code'=>-1,'msg'=>'未获取到服务器信息'];
					}
                break;
        }
		return json($res);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function add()
    {
        //添加版本
		if(Request::isAjax()){
			$data = Request::param();
			$result = AddonsModel::create($data);
			if($result){
				$res = ['code'=>0,'msg'=>'添加成功'];
			}else{
				$res = ['code'=>-1,'msg'=>'添加失败'];
			}
		return json($res);
		}
		return View::fetch();
    }

    /**
     * 上传插件文件zip
     * @return Json
     */
    public function uploadZip()
    {
		$id = Request::param();
        $file = request()->file('file');
		try {
			validate(['file'=>'filesize:2048|fileExt:zip,rar,7z'])
            ->check(array($file));
			$saveName = \think\facade\Filesystem::disk('public')->putFile('addons',$file);
		} catch (\think\exception\ValidateException $e) {
            return json(['code' => -1,'msg' => $e->getMessage()]);
		}
		$upload = Config::get('filesystem.disks.public.url');
		
		if($saveName){
            $name_path =str_replace('\\',"/",$upload.'/'.$saveName);
            $res = ['code'=>0,'msg'=>'插件上传成功','src'=>$name_path];
        } else {
            $res = ['code'=>-1,'msg'=>'上传错误'];
        }
		return json($res);
    }

    /**
     * 上传接口
     *
     * @return void
     */
    public function uploads()
    {
        $type = Request::param('type');
        return $this->uploadFiles($type);
    }

    /**
     * 安装插件
     * @return Json
     * @throws \Exception
     */
    public function install()
    {
        $data = Request::param();
        $url = $this->getSystem()['api_url'].'/v1/getaddons';
        $data = ['name'=>$data['name'], 'version'=>$data['version'], 'uid'=>$data['uid'], 'token'=>$data['token']];
         $addons = Api::urlPost($url,$data);
         if( $addons->code < 0) {
             return json(['code'=>$addons->code,'msg'=>$addons->msg]);
         }
         //$this->pay($name,$extend);
         //版本判断，是否能够安装？
         $addInstalledVersion = get_addons_info($data['name']);
         if(!empty($addInstalledVersion)){
             $verRes = version_compare($data['version'],$addInstalledVersion['version'],'>');
             if(!$verRes){
                 return json(['code'=>-1,'msg'=>'不能降级，请选择正确版本']);
             }
             //$tpl_ver_res = version_compare($addInstalledVersion['template_version'], config('taoler.template_version'),'<');
         }

         $file_url = $addons->addons_src;
         //判断远程文件是否可用存在
         $header = get_headers($file_url, true);
         if(!isset($header[0]) && (strpos($header[0], '200') || strpos($header[0], '304'))){
             return json(['code'=>-1,'msg'=>'获取远程文件失败']);
         }

         //把远程文件放入本地

         //拼接路径
         $addons_dir = Files::getDirPath('../runtime/addons/');
         Files::mkdirs($addons_dir);

         $package_file = $addons_dir . $data['name'] .'.zip';  //升级的压缩包文件
         $cpfile = copy($file_url,$package_file);
         if(!$cpfile) return json(['code'=>-1,'msg'=>'下载升级文件失败']);

         $uzip = new Zip();
         $zipDir = strstr($package_file, '.zip',true);   //返回文件名后缀前的字符串
         $zipPath = Files::getDirPath($zipDir);  //转换为带/的路径 压缩文件解压到的路径
         $unzip_res = $uzip->unzip($package_file,$zipPath,true);
         if(!$unzip_res) return json(['code'=>-1,'msg'=>'解压失败']);

         //升级插件

         //升级前的写入文件权限检查
         $allUpdateFiles = Files::getAllFile($zipPath);

         if (empty($allUpdateFiles)) return json(['code' => -1, 'msg' => '无可更新文件。']);
         $checkString    = '';
         foreach ($allUpdateFiles as $updateFile) {
             $coverFile  = ltrim(str_replace($zipPath, '', $updateFile), DIRECTORY_SEPARATOR);
             $dirPath    = dirname('../'.$coverFile);
             if (file_exists('../'.$coverFile)) {
                 if (!is_writable('../'.$coverFile)) $checkString .= $coverFile . '&nbsp;[<span class="text-red">' . '无写入权限' . '</span>]<br>';
             } else {
                 if (!is_dir($dirPath)) @mkdir($dirPath, 0777, true);
                 if (!is_writable($dirPath)) $checkString .= $dirPath . '&nbsp;[<span class="text-red">' . '无写入权限' . '</span>]<br>';
             }
         }

         if (!empty($checkString)) return json(['code' => -1, 'msg' => $checkString]);
         $addonsPath = '../';
         $cpRes = Files::copyDirs($zipPath,$addonsPath);
         $cpData = $cpRes->getData();
         //更新失败
         if($cpData['code'] == -1) return json(['code'=>-1,'msg'=>$cpData['msg']]);

        $class = get_addons_instance($data['name']);
        //添加数据库
        $sqlInstallFile = root_path().'addons/'.$data['name'].'/install.sql';
        if(file_exists($sqlInstallFile)) {
            SqlFile::dbExecute($sqlInstallFile);
        }
        //安装菜单
        $menu = get_addons_menu($data['name']);
        if(!empty($menu)){
            if(isset($menu['is_nav']) && $menu['is_nav']==1){
                $pid = 0;
            }else{
                $pid = AuthRule::where('name','addons')->value('id');
            }
            $menu_arr[] = $menu['menu'];
            $this->addAddonMenu($menu_arr, (int)$pid,1);
        }
        //执行插件安装
        $class->install();

		Files::delDirAndFile('../runtime/addons/'.$data['name'] . DS);

		return json(['code'=>0,'msg'=>'插件安装成功！']);

    }
    /**
     * 卸载插件
     */
    public function uninstall()
    {
        $name = input('name');
        //执行插件卸载
        $class = get_addons_instance($name);
        $class->uninstall();
        //卸载菜单
        $menu = get_addons_menu($name);
        if(!empty($menu)){
            $menu_arr[] = $menu['menu'];
            $this->delAddonMenu($menu_arr);
        }

        //卸载插件数据库
        $sqlUninstallFile = root_path().'addons/'.$name.'/uninstall.sql';
        if(file_exists($sqlUninstallFile)) {
            SqlFile::dbExecute($sqlUninstallFile);
        }


        // 插件addons下目录
        $addonsDir = root_path() . 'addons' . DS . $name . DS;
        // 插件管理后台目录
        $admin_controller = app_path() . 'controller' . DS . $name . DS;
        $admin_model = app_path() . 'model' . DS . $name  . DS;
        $admin_view = app_path() . 'view' . DS . $name . DS;
        $admin_validate = app_path() . 'validate' . DS . $name . DS;
        // 插件静态资源目录
        $addon_public = public_path() . 'addons' . DS . $name . DS;

        try {
            if(file_exists($addonsDir)) Files::delDir($addonsDir);
            if(file_exists($admin_controller)) Files::delDir($admin_controller);
            if(file_exists($admin_model)) Files::delDir($admin_model);
            if(file_exists($admin_view)) Files::delDir($admin_view);
            if(file_exists($admin_validate)) Files::delDir($admin_validate);
            if(file_exists($addon_public)) Files::delDir($addon_public);

        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => $e->getMessage()]);
        }

        return json(['code' => 0, 'msg' => '插件卸载成功']);
    }

    /**
     * 启用禁用插件
     * @return Json
     * @throws Exception
     */
    public function status(){
        $name = input('name');
        $info = get_addons_info($name);
        try{
            $arr = ['status' => $info['status'] ? 0 :1];
            set_addons_info($name,$arr);
            $class = get_addons_instance($name);
            if($arr['status']) {
                $res = ['code'=>0,'msg'=>'启用成功'];
            } else {
                $res = ['code'=>0,'msg'=>'已被禁用'];
            }
            $info['status']==1 ?$class->enabled():$class->disabled();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        return json($res);
    }

    /**
     * 配置插件
     * @param $name
     * @return string|Json
     * @throws \Exception
     */
	public function config($name)
	{
		$name = input('name');
		$config = get_addons_config($name);
		if(empty($config)) return json(['code'=>-1,'msg'=>'无配置项！无需操作']);
		if(Request::isAjax()){
			$params = Request::param('params/a',[],'trim');
			if ($params) {
                foreach ($config as $k => &$v) {
                    if (isset($params[$k])) {
                        if ($v['type'] == 'array') {
                            $arr = [];
                            $params[$k] = is_array($params[$k]) ? $params[$k] :[];
                            foreach ($params[$k]['key'] as $kk=>$vv){
                                $arr[$vv] =  $params[$k]['value'][$kk];
                            }
                            $params[$k] = $arr;
                            $value = $params[$k];
                            $v['content'] = $value;
                            $v['value'] = $value;
                        } else {
                            $value =  $params[$k];
                        }
                        $v['value'] = $value;
                    }
                }
                unset($v);
                set_addons_config($name,$config);
   
            }
			return json(['code'=>0,'msg'=>'配置成功！']);
		}

		//模板引擎初始化
        $view = ['formData'=>$config,'title'=>'title'];
		View::assign($view);
        $configFile = root_path() . 'addons' . DS . $name . DS . 'config.html';
        $viewFile = is_file($configFile) ? $configFile : '';

		return View::fetch($viewFile);
		
	}

    /**
     * 添加菜单
     * @param array $menu
     * @param int $pid
     * @param int $type
     * @return void
     * @throws \Exception
     */
    public function addAddonMenu(array $menu,int $pid = 0, int $type = 1)
    {
        foreach ($menu as $v){
            $hasChild = isset($v['sublist']) && $v['sublist'] ? true : false;
            try {
                $v['pid'] = $pid;
                $v['name'] = trim($v['name'],'/');
                $v['type'] = $type;
                $menu = AuthRule::withTrashed()->where('name',$v['name'])->find();
                if($menu){
                    $menu->restore();
                } else {
                    $menu = AuthRule::create($v);
                }

                if ($hasChild) {
                    $this->addAddonMenu($v['sublist'], $menu->id,$type);
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

    }

    /**
     * 循环删除菜单
     * @param array $menu
     * @param string $module
     * @return void
     * @throws \Exception
     */
    public function delAddonMenu(array $menu,string $module = 'addon')
    {
        foreach ($menu as $k=>$v){
            $hasChild = isset($v['sublist']) && $v['sublist'] ? true : false;
            try {
                $v['name'] = trim($v['name'],'/');
                $menu_rule = AuthRule::withTrashed()->where('name',$v['name'])->find();
                if(!is_null($menu_rule)){
                    $menu_rule->delete(true);
                    if ($hasChild) {
                        $this->delAddonMenu($v['sublist']);
                    }
                }

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

    }

    /**
     * 用户登录
     * @return mixed|Json
     */
    public function userLogin()
    {
        $data = Request::param();
        $url = $this->getSystem()['api_url'].'/v1/user/login';
        $user = Api::urlPost($url,$data);
        if($user->code == 0) {
            return $user;
        } else {
            return json(['code'=>-1,'msg'=>$user->msg]);
        }
    }

    /**
     * 订单
     * @return string|Json
     */
    public function pay()
    {
        $data = Request::only(['id','name','version','uid','price']);
        $url = $this->getSystem()['api_url'].'/v1/createOrder';
        $order = Api::urlPost($url,$data);

        if ($order->code == 0) {
            $orderData = json_decode(json_encode($order->data),TRUE);
            View::assign('orderData',$orderData);
            return View::fetch();
        } else {
            return json(['code'=>-1,'msg'=>$order->msg]);
        }
    }

    /**
     * 支付查询
     * @return Json
     */
    public function isPay()
    {
        $param = Request::only(['name','userinfo']);
        //halt($data);
        $data = [
            'name'=>$param['name'],
            'uid'=> $param['userinfo']['uid'],
        ];
        $url = $this->getSystem()['api_url'].'/v1/ispay';
        $res =  Api::urlPost($url,$data);
        if($res->code == 0) {
            return json(['code'=>0,'msg'=>'payed']);
        } else {
            return json(['code'=>-1,'msg'=>'no pay']);
        }
    }

}
