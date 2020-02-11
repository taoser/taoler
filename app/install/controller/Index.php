<?php
namespace app\install\controller;

use app\common\controller\BaseController;
use think\facade\View;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;

class Index extends BaseController
{
	/**
	* 安装向导
	*/
	// 检测是否安装过
	protected function initialize(){
        if(file_exists('../install.lock')){
           echo "<script>alert('已经成功安装了TaoLer社区系统，安装系统已锁定。如需重新安装，请删除根目录下的install.lock文件')</script>";
		   die();
        }
    }

	//安装首页
    public function index()
	{
		Session::set('install',1);
        return View::fetch('agreement');
    }
	
	//test
    public function test()
	{
		if(Session::get('install') == 1){
			Session::set('install',2);
			return View::fetch('test');
		} else {
			return redirect('./index.html');
		} 
    }
	
	//create
    public function create(){
		if(Session::get('install') == 2){
			Session::set('install',3);
			return View::fetch('create');
		} else {
			return redirect('./test.html');
		} 
    }
	
	// 安装
	public function install(){
		
		//if(Session::get('install') != 3){
		//	return redirect('./create.html');
		//}
		
    // 判断是否为post
    //if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if(Request::isAjax()){	
		$data = Request::param();
        //$data = $_POST;
		//var_dump($data);
        if (!preg_match("/^[a-zA-Z]{1}([0-9a-zA-Z]|[._]){4,19}$/", $data['admin_user'])) {
            die("<script>alert('后台管理用户名不符合规范：至少包含4个字符，需以字母开头');history.go(-1)</script>");
        }
       
            if (!preg_match("/^[\@A-Za-z0-9\!\#\$\%\^\&\*\.\~]{6,22}$/", $data['admin_pass'])) {
                die("<script>alert('登录密码至少包含6个字符。可使用字母，数字和符号。');history.go(-1)</script>");
            }
            if ($data['admin_pass'] != $data['admin_pass2']) {
                die("<script>alert('两次输入的密码不一致');history.go(-1)</script>");
       
            }
            $_SESSION['adminusername'] = $data['admin_user'];
			$email = $data['admin_email'];
			$user = $data['admin_user'];
			$create_time = time();
			$salt = substr(md5($create_time),-6);
			$pass = md5(substr_replace(md5($data['admin_pass']),$salt,0,6));
        
       
		if ($data['DB_TYPE'] == 'mysql') {
						
		//数据库			
		$db_s = <<<php
<?php
return [
    // 数据库连接配置信息
    'connections'     => [
    'mysql' => [
	// 数据库类型
	'type'              => 'mysql',
	// 服务器地址
	'hostname'          => '{$data['DB_HOST']}',
	// 数据库名
	'database'          => '{$data['DB_NAME']}',
	// 用户名
	'username'          => '{$data['DB_USER']}',
	// 密码
	'password'          => '{$data['DB_PWD']}',
	// 端口
	'hostport'          => '{$data['DB_PORT']}',
	// 数据库编码默认采用utf8
	'charset'           => 'utf8',
	// 数据库表前缀
	'prefix'            => '{$data['DB_PREFIX']}',
	],
    ],
];
php;
        // 创建数据库链接配置文件

        $fp = fopen('../app/install/config/database.php', "r+b");
        fputs($fp, $db_s);
        fclose($fp);	

			$db = Db::connect('mysql');

			//$sql = 'CREATE DATABASE IF NOT EXISTS '.$data['DB_NAME'].' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
			//$db->execute($sql);
			
			//创建数据表
			create_tables($db, $data['DB_PREFIX']);
			
			$table_admin = $data['DB_PREFIX'] . "admin";
			$table_user = $data['DB_PREFIX'] . "user";
			$table_system = $data['DB_PREFIX'] . "system";

			$res_a = Db::table($table_admin)->where('id',1)->update(['username'=>$user,'email'=>$email,'password'=>$pass,'status'=>1,'auth_group_id'=>1,'create_time'=>$create_time]);
			$res_u = Db::table($table_user)->where('id',1)->update(['name'=>$user,'email'=>$email,'password'=>$pass,'auth'=>1,'status'=>1,'create_time'=>$create_time]);
			$res_s = Db::table($table_system)->where('id',1)->update(['webname'=>$data['webname'],'webtitle'=>$data['webtitle'],'domain'=>Request::domain(),'create_time'=>time()]);

			Db::getConnection()->close();
        }

        $db_str = <<<php
<?php
return [
    // 自定义时间查询规则
    'time_query_rule' => [],
    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => true,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 数据库连接配置信息
    'connections'     => [
	'mysql' => [
	// 数据库类型
	'type'              => 'mysql',
	// 服务器地址
	'hostname'          => '{$data['DB_HOST']}',
	// 数据库名
	'database'          => '{$data['DB_NAME']}',
	// 用户名
	'username'          => '{$data['DB_USER']}',
	// 密码
	'password'          => '{$data['DB_PWD']}',
	// 端口
	'hostport'          => '{$data['DB_PORT']}',
	// 数据库连接参数
	'params'            => [],
	// 数据库编码默认采用utf8
	'charset'           => 'utf8',
	// 数据库表前缀
	'prefix'            => '{$data['DB_PREFIX']}',
	// 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
	'deploy'            => 0,
	// 数据库读写是否分离 主从式有效
	'rw_separate'       => false,
	// 读写分离后 主服务器数量
	'master_num'        => 1,
	// 指定从服务器序号
	'slave_no'          => '',
	// 是否严格检查字段是否存在
	'fields_strict'     => true,
	// 是否需要断线重连
	'break_reconnect'   => false,
	// 监听SQL
	'trigger_sql'       => true,
	// 开启字段缓存
	'fields_cache'      => false,
	// 字段缓存路径
	'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
	],
    ],
];
php;
        // 创建数据库链接配置文件
        $fp = fopen('../config/database.php', "r+b");
        fwrite($fp, $db_str);
        fclose($fp);
		//安装上锁
		file_put_contents('../install.lock', 'lock');
		Session::clear();
		//return View::fetch('complete');
		return json(['code' => 0,'msg'=>'安装成功','url'=>'/install.php/success/complete']);
    } else {
		return '请求失败！';
		} 

	} 
}