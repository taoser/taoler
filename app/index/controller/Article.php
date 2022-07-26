<?php

namespace app\index\controller;

use app\common\controller\BaseController;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Config;
use app\common\model\Comment;
use app\common\model\Article as ArticleModel;
use app\common\model\Slider;
use app\common\model\UserZan;
use taoler\com\Message;
use app\common\lib\Msgres;
use app\common\lib\Uploads;
use taoser\SetArr;
use taoler\com\Api;
use Overtrue\Pinyin\Pinyin;

class Article extends BaseController
{
	protected $middleware = [ 
    	'logincheck' => ['except' 	=> ['cate','detail','download'] ],
    ];
	
	//文章分类
    public function cate()
	{
		//非法请求参数，抛出异常
		if(count(Request::param()) >3){
			// 抛出 HTTP 异常
                throw new \think\exception\HttpException(404, '请求异常');
		}
		//动态参数
		$ename = Request::param('ename') ?? 'all';
		$type = Request::param('type') ?? 'all';
		$page = Request::param('page') ? Request::param('page') : 1;
		$tpl = Db::name('cate')->where('ename',$ename)->value('detpl');
		//分页url
		$url = url('cate_page',['ename'=>$ename,'type'=>$type,'page'=>$page]);
		//返回最后/前面的字符串
		$path = substr($url,0,strrpos($url,"/"));
		
        //分类列表
        $article = new ArticleModel();
		$artList = $article->getCateList($ename,$type,$page);
		//	热议文章
		$artHot = $article->getArtHot(10);
		//广告
        $ad = new Slider();
        //分类图片
        $ad_cateImg = $ad->getSliderList(3);
        //分类钻展赞助
        $ad_comm = $ad->getSliderList(6);

		View::assign(['type'=>$type,'artList'=>$artList,'artHot'=>$artHot,'ad_cateImg'=>$ad_cateImg,'ad_comm'=>$ad_comm,'jspage'=>'jie','ename'=>$ename,'path'=>$path]);
		return View::fetch('article/'.$tpl.'/cate');
    }

	//文章详情页
    public function detail()
    {
		$id = input('id');
		$artStu = Db::name('article')->field('id')->where(['status'=>1,'delete_time'=>0])->find($id);
		if(is_null($artStu)){
			// 抛出 HTTP 异常
			throw new \think\exception\HttpException(404, '异常消息');
		}

		//输出内容
		$page = input('page') ? input('page') : 1;
        $article = new ArticleModel();
        $artDetail = $article->getArtDetail($id);
		//用户个人tag标签
		$userTags = $article->where(['user_id'=>$artDetail['user_id'],'status'=>1])->where('tags','<>','')->column('tags');
		//转换为字符串
		$tagStr = implode(",",$userTags);
		//转换为数组并去重
		$tagArr = array_unique(explode(",",$tagStr));
		$userTagCount = count($tagArr);

		//赞列表
		$userZanList = [];
		$userZan = UserZan::where(['article_id'=>$id,'type'=>1])->select();
		foreach($userZan as $v){
			$userZanList[] = ['userImg'=>$v->user->user_img,'name'=>$v->user->name];
		}
	
		// 设置内容的tag内链
		$artDetail['content'] = $this->setArtTagLink($artDetail['content']);
		
		// tag
		// $tags = [];
		// if(!is_null($artDetail['tags'])){
		//     $attr = explode(',', $artDetail['tags']);
    	// 	foreach($attr as $v){
    	// 		if ($v !='') {
    	// 			$tags[] = $v;
    	// 		}
    	// 	}
		// } else {
		//     $tags[] = $artDetail['title'];
		// }
		
		// $tag = new Tag();
		// $tags = $tag->getArtTag($artDetail['tags']);

		$pinyin = new Pinyin();
        $tags = [];
		if(!is_null($artDetail['tags'])){
		    $attr = explode(',', $artDetail['tags']);
    		foreach($attr as $v){
    			if ($v !='') {
    				$tags[] = ['tag'=>$v,'pinyin'=>$pinyin->permalink($v,''), 'url'=> (string) url('tag_list',['tag'=>$pinyin->permalink($v,'')])];
    			}
    		}
		}



		$tpl = Db::name('cate')->where('id', $artDetail['cate_id'])->value('detpl');
		$download = $artDetail['upzip'] ? download($artDetail['upzip'],'file') : '';

		//$count = $comments->total();
        //$artDetail->inc('pv')->update();

		//浏览pv
		Db::name('article')->where('id',$id)->inc('pv')->update();
		$pv = Db::name('article')->field('pv')->where('id',$id)->value('pv');

		//评论
		$comments = $this->getComments($id, $page);
		//最新评论时间
		$lrDate_time = Db::name('comment')->where('article_id', $id)->max('update_time',false) ?? time();
		//	热议文章
		$artHot = $article->getArtHot(10);
        //广告
        $ad = new Slider();
        //分类图片
        $ad_artImg = $ad->getSliderList(4);
        //分类钻展赞助
        $ad_comm = $ad->getSliderList(7);
		//push
		$push_js = Db::name('push_jscode')->where(['delete_time'=>0])->cache(true)->select();
		
		View::assign([
			'article'	=> $artDetail,
			'pv'		=> $pv,
			'artHot'	=> $artHot,
			'ad_art'	=> $ad_artImg,
			'ad_comm'	=> $ad_comm,
			'tags'		=> $tags,
			'page'		=> $page,
			'comments'	=> $comments,
			'push_js'	=> $push_js,
			'cid' 		=> $id,
			'lrDate_time' => $lrDate_time,
			'userZanList' => $userZanList,
			'userTagCount'=> $userTagCount,
			'jspage'	=> 'jie',
			$download,
		]);
	
		return View::fetch('article/'.$tpl.'/detail');
    }
	
	//评论内容
	public function getComments($id, $page)
	{
		$comment = new Comment;
		return $comment->getComment($id, $page);
	}
	
	//文章评论
	public function comment()
	{
		// 检验发帖是否开放
		if(config('taoler.config.is_reply') == 0 ) return json(['code'=>-1,'msg'=>'抱歉，系统维护中，暂时禁止评论！']);

		if (Request::isAjax()){
			//获取评论
			$data = Request::only(['content','article_id','user_id']);
			$sendId = $data['user_id'];
			$art = Db::name('article')->field('id,status,is_reply,delete_time')->find($data['article_id']);

			if($art['delete_time'] != 0 || $art['status'] != 1 || $art['is_reply'] != 1){
				return json(['code'=>-1, 'msg'=>'评论不可用状态']);
			}
			if(empty($data['content'])){
				return json(['code'=>-1, 'msg'=>'评论不能为空！']);
			}
			$superAdmin = Db::name('user')->where('id',$sendId)->value('auth');
			$data['status'] = $superAdmin ? 1 : Config::get('taoler.config.commnets_check');
			$msg = $data['status'] ? '留言成功' : '留言成功，请等待审核';
				
			//用户留言存入数据库
			if (Comment::create($data)) {
				//站内信
				$article = Db::name('article')->field('id,title,user_id,cate_id')->where('id',$data['article_id'])->find();
				// 获取分类ename
				$cate_ename = Db::name('cate')->where('id',$article['cate_id'])->value('ename');
				$title = $article['title'];
				//$link = (string) url('article_detail',['id'=>$data['article_id']]);
				$link = $this->getRouteUrl($data['article_id'], $cate_ename);

				//评论中回复@user comment
				$preg = "/@([^@\s]*)\s/";
				preg_match($preg,$data['content'],$username);
				if(isset($username[1])){
					$receveId = Db::name('user')->whereOr('nickname', $username[1])->whereOr('name', $username[1])->value('id'); 
				} else {
					$receveId = $article['user_id'];
				}
				$data = ['title'=>$title,'content'=>'评论通知','link'=>$link,'user_id'=>$sendId,'type'=>2]; //type=2为评论留言
				Message::sendMsg($sendId,$receveId,$data);
				if(Config::get('taoler.config.email_notice')) mailto($this->showUser(1)['email'],'评论审核通知','Hi亲爱的管理员:</br>用户'.$this->showUser($this->uid)['name'].'刚刚对 <b>'.$title.'</b> 发表了评论，请尽快处理。');
				$res = ['code'=>0, 'msg'=>$msg];
			} else {
				$res = ['code'=>-1, 'msg'=>'留言失败'];
			}
			return json($res);
		}
	}

    /**
     * 添加帖子文章
     * @return string|\think\Response|\think\response\Json|void
     */
    public function add()
    {
        if (Request::isAjax()) {
			// 检验发帖是否开放
			if(config('taoler.config.is_post') == 0 ) return json(['code'=>-1,'msg'=>'抱歉，系统维护中，暂时禁止发帖！']);

            $data = Request::only(['cate_id', 'title', 'title_color', 'user_id', 'tiny_content', 'content', 'upzip', 'tags', 'description', 'captcha']);
			
			// 验证码
			if(Config::get('taoler.config.post_captcha') == 1)
			{				
				if(!captcha_check($data['captcha'])){
				 return json(['code'=>-1,'msg'=> '验证码失败']);
				};
			}

			// 调用验证器
			$validate = new \app\common\validate\Article;
            $result = $validate->scene('Artadd')->check($data);
            if (true !== $result) {
                return Msgres::error($validate->getError());
            }
			
			// 获取内容图片音视频标识
			$iva= $this->hasIva($data['content']);
			$data = array_merge($data,$iva);
			// 获取分类ename
			$cate_ename = Db::name('cate')->where('id',$data['cate_id'])->value('ename');
		
            $article = new ArticleModel();
            $result = $article->add($data);
            if ($result['code'] == 1) {
				// 获取到的最新ID
				$aid = Db::name('article')->max('id');
				// 清除文章tag缓存
                Cache::tag('tagArtDetail')->clear();
				// 发提醒邮件
				if(Config::get('taoler.config.email_notice')) mailto($this->showUser(1)['email'],'发帖审核通知','Hi亲爱的管理员:</br>用户'.$this->showUser($this->uid)['name'].'刚刚发表了 <b>'.$data['title'].'</b> 新的帖子，请尽快处理。');

				$link = $this->getRouteUrl((int)$aid, $cate_ename);
				// 推送给百度收录接口
				$this->baiduPush($link);
				    
				$url = $result['data']['status'] ? $link : (string)url('index/');
				$res = Msgres::success($result['msg'], $url);
            } else {
                $res = Msgres::error('add_error');
            }
            return $res;
        }

		View::assign(['jspage'=>'jie']);
		$tpl = Db::name('cate')->where('ename', input('cate'))->value('detpl');
		$appName = $this->app->http->getName();
		$viewRoot = root_path() . config('view.view_dir_name') . DIRECTORY_SEPARATOR . $appName .  DIRECTORY_SEPARATOR;
		$view = 'article' . DIRECTORY_SEPARATOR . $tpl . DIRECTORY_SEPARATOR . 'add.html';
		$vfile = $viewRoot . $view;
		$addTpl = is_file($vfile) ? $vfile : 'add';

		return View::fetch($addTpl);
    }

    /**
     * 编辑文章
     * @param $id
     * @return string|\think\Response|\think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($id)
    {
		$article = ArticleModel::find($id);
		
		if(Request::isAjax()){
			$data = Request::only(['id','cate_id','title','title_color','user_id','content','upzip','tags','description','captcha']);
			// 验证码
			if(Config::get('taoler.config.post_captcha') == 1)
			{				
				if(!captcha_check($data['captcha'])){
				 return json(['code'=>-1,'msg'=> '验证码失败']);
				};
			}
			//调用验证器
			$validate = new \app\common\validate\Article(); 
			$res = $validate->scene('Artadd')->check($data);
			
			if(true !== $res){
                return Msgres::error($validate->getError());
			} else {
				//获取内容图片音视频标识
				$iva= $this->hasIva($data['content']);
				$data = array_merge($data,$iva);
				
				$result = $article->edit($data);
				if($result == 1) {
                    //删除原有缓存显示编辑后内容
                    Cache::delete('article_'.$id);
					$link = $this->getRouteUrl((int) $id, $article->cate->ename);
					// 推送给百度收录接口
					$this->baiduPush($link);
					$editRes = Msgres::success('edit_success',$link);
				} else {
                    $editRes = Msgres::error($result);
				}
				return $editRes;
			}
		}
		// 查询标签
		$tag = $article->tags;
		$tags = [];
		if(!is_null($tag)) {
		    $attr = explode(',',$tag);
    		foreach($attr as $key => $v){
    			if ($v !='') {
    				$tags[] = $v;
    			}
    		}

		}
			
        View::assign(['article'=>$article,'tags'=>$tags,'jspage'=>'jie']);
		// 编辑多模板支持
		$tpl = Db::name('cate')->where('id', $article['cate_id'])->value('detpl');
		$appName = $this->app->http->getName();
		$viewRoot = root_path() . config('view.view_dir_name') . DIRECTORY_SEPARATOR . $appName .  DIRECTORY_SEPARATOR;
		$view = 'article' . DIRECTORY_SEPARATOR . $tpl . DIRECTORY_SEPARATOR . 'edit.html';
		$vfile = $viewRoot . $view;
		$editTpl = is_file($vfile) ? $vfile : 'edit';

		return View::fetch($editTpl);
    }
	
	/**
	 * 删除
	 *
	 * @return void
	 */
    public function delete()
	{
		$article = ArticleModel::find(input('id'));
		$result = $article->together(['comments'])->delete();
		if($result) {
		    $res = Msgres::success('delete_success');
		} else {
			$res = Msgres::error('delete_error');
		}
		return $res;
	}

	/**
	 * 上传接口
	 *
	 * @return void
	 */
	public function uploads()
    {
        $type = Request::param('type');
        $uploads = new Uploads();
        switch ($type){
            case 'image':
                $upRes = $uploads->put('file','article_pic',1024,'image');
                break;
            case 'zip':
                $upRes = $uploads->put('file','article_zip',1024,'application|image');
                break;
            case 'video':
                $upRes = $uploads->put('file','article_video',102400,'video|audio');
                break;
            case 'audio':
                $upRes = $uploads->put('file','article_audio',102400,'audio');
                break;
            default:
                $upRes = $uploads->put('file','article_file',1024,'image');
                break;
        }
        return $upRes;
    }

    /**
	 * 附件下载
	 *
	 * @param [type] $id
	 * @return void
	 */
    public function download($id)
    {
        $zipdir = Db::name('article')->where('id',$id)->value('upzip');
        $zip = substr($zipdir,1);
		Db::name('article')->cache(true)->where('id',$id)->inc('downloads')->update();
		//删除缓存显示下载后数据
        Cache::delete('article_'.$id);
        return download($zip,'my');
    }

	/**
	 * 获取描述，过滤html
	 *
	 * @return void
	 */
	public function getDescription()
	{
		$data = Request::only(['content']);
		$description = getArtContent($data['content']);
		return json(['code'=>0,'data'=>$description]);
	}

	/**
	 * 标题调用百度关键词词条
	 *
	 * @return void
	 */
	public function getWordList()
	{
		$title = input('title');
		if(empty($title)) return json(['code'=>-1,'msg'=>'null']);
		$url = 'https://www.baidu.com/sugrec?prod=pc&from=pc_web&wd='.$title;
		//$result = Api::urlGet($url);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$datas = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($datas,true);
		if(isset($data['g'])) {
			return json(['code'=>0,'msg'=>'success','data'=>$data['g']]);
		} else {
			return json(['code'=>-1,'msg'=>'null']);
		}
		
	}

    /**
	 * 关键词
	 *
	 * @return void
	 */
    public function tags()
    {
        $data = Request::only(['tags','flag']);
		$tags = [];

		if($data['flag'] == 'on') {
			// 百度分词自动生成关键词
			if(!empty(config('taoler.baidu.client_id')) == true) {
				$url = 'https://aip.baidubce.com/rpc/2.0/nlp/v1/lexer?charset=UTF-8&access_token='.config('taoler.baidu.access_token');

				//headers数组内的格式
				$headers = array();
				$headers[] = "Content-Type:application/json";
				$body   = array(
							"text" => $data['tags']
					);
				$postBody    = json_encode($body);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);//设置请求头
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postBody);//设置请求体
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
				$datas = curl_exec($curl);
				if($datas == false) {
					echo '接口无法链接';
				} else {
					$res = stripos($datas,'error_code');
					// 接收返回的数据
					$dataItem = json_decode($datas);
					if($res == false) {
						// 数据正常
						$items = $dataItem->items;
						foreach($items as $item) {
							if($item->pos == 'n' && !in_array($item->item,$tags)){
								$tags[] = $item->item;
							}
						}
					} else {
						// 接口正常但获取数据失败，可能参数错误，重新获取token
						$url = 'https://aip.baidubce.com/oauth/2.0/token';
						$post_data['grant_type']     = config('taoler.baidu.grant_type');;
						$post_data['client_id']      = config('taoler.baidu.client_id');
						$post_data['client_secret']  = config('taoler.baidu.client_secret');
	
						$o = "";
						foreach ( $post_data as $k => $v ) 
						{
							$o.= "$k=" . urlencode( $v ). "&" ;
						}
						$post_data = substr($o,0,-1);
						$res = $this->request_post($url, $post_data);
						// 写入token
						SetArr::name('taoler')->edit([
							'baidu'=> [
								'access_token'	=> json_decode($res)->access_token,
							]
						]);
						echo 'api接口数据错误 - ';
						echo $dataItem->error_msg; 
					}
				}
			}
		} else {
			// 手动添加关键词
			// 中文一个或者多个空格转换为英文空格
			$str = preg_replace('/\s+/',' ',$data['tags']);
			$att = explode(' ', $str);
			foreach($att as $v){
				if ($v !='') {
					$tags[] = $v;
				}
			}
		}
		
        return json(['code'=>0,'data'=>$tags]);
    }

	//	api_post接口
	function request_post($url = '', $param = '') 
	{
        if (empty($url) || empty($param)) {
            return false;
        }
        
        $postUrl = $url;
        $curlPost = $param;
        $curl = curl_init();//初始化curl
        curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($curl);//运行curl
        curl_close($curl);
        return $data;
    }
	
	// 文章置顶、加精、评论状态
	public function jieset()
	{
		$data = Request::param();
		$article = ArticleModel::field('id,is_top,is_hot,is_reply')->find($data['id']);
		switch ($data['field']){
            case  'top':
                if($data['rank']==1){
                    $article->save(['is_top' => 1]);
                    $res = ['status'=>0,'msg'=>'置顶成功'];
                } else {
                    $article->save(['is_top' => 0]);
                    $res = ['status'=>0,'msg'=>'置顶已取消'];
                }
            break;
            case 'hot':
                if($data['rank']==1){
                    $article->save(['is_hot' => 1]);
                    $res = ['status'=>0,'msg'=>'加精成功'];
                } else {
                    $article->save(['is_hot' => 0]);
                    $res = ['status'=>0,'msg'=>'加精已取消'];
                }
            break;
            case 'reply':
                if($data['rank']==1){
                    $article->save(['is_reply' => 1]);
                    $res = ['status'=>0,'msg'=>'禁评成功'];
                } else {
                    $article->save(['is_reply' => 0]);
                    $res = ['status'=>0,'msg'=>'禁评已取消'];
                }
        }
        //删除本贴设置缓存显示编辑后内容
        Cache::delete('article_'.$data['id']);
		//清除文章tag缓存
		Cache::tag('tagArtDetail')->clear();
		return json($res);	
	}
	
	// 改变标题颜色
	public function titleColor()
	{
		$data = Request::param();
		$result = ArticleModel::update($data);
		if($result){
			//清除文章缓存
			Cache::tag(['tagArt','tagArtDetail'])->clear();
			$res = ['code'=> 0, 'msg'=>'标题颜色设置成功'];
		}else{
			$res = ['code'=> -1, 'msg'=>'标题颜色设置失败'];
		}
		return json($res);
	}
	
	/**
	 * 内容中是否有图片视频音频插入
	 *
	 * @param [type] $content
	 * @return boolean
	 */
	public function hasIva($content)
	{
		//判断是否插入图片
		$isHasImg = strpos($content,'img[');
		$data['has_img'] = is_int($isHasImg) ? 1 : 0;
		//判断是否插入视频
		$isHasVideo = strpos($content,'video(');
		$data['has_video'] = is_int($isHasVideo) ? 1 : 0;
		//判断是否插入音频
		$isHasAudio = strpos($content,'audio[');
		$data['has_audio'] = is_int($isHasAudio) ? 1 : 0;
		
		return $data;
	}

	//设置文章内容tag
	protected function setArtTagLink($content)
	{
		// tag链接数组
		$tag = Config::get('taglink');
		if(count($tag)) {
			foreach($tag as $key=>$value) {
				// 匹配所有
				//$content = str_replace("$key", 'a('.$value.')['.$key.']',$content);
				// 限定匹配数量 '/'.$key.'/'
				// 匹配不包含[和]的内容
				$pats = '/(?<!\[)'.$key.'(?!\])/';
				//preg_match($pats,$content,$arr);
				//halt($arr[0]);
				$content = preg_replace($pats,'a('.$value.')['.$key.']',$content,2);
			}
		}
		
		return $content;
	}

	/**
	 * baidu push api
	 *
	 * @param string $link
	 * @return void
	 */
	protected function baiduPush(string $link)
	{
		// baidu 接口
		$api = config('taoler.baidu.push_api');
		if(!empty($api)) {
			$url[] = $link;
			$ch = curl_init();
			$options =  array(
				CURLOPT_URL => $api,
				CURLOPT_POST => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POSTFIELDS => implode("\n", $url),
				CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
			);
			curl_setopt_array($ch, $options);
			curl_exec($ch);
			curl_close($ch);
		}
		
	}

	public function userZanArticle()
	{
		//
		if(Request::isPost()) {
			$data = Request::post();
			$data['user_id'] = $this->uid;
			$userZan = Db::name('user_zan')->where(['user_id'=>$this->uid,'article_id'=>$data['article_id']])->find();
			if($userZan){
				return json(['code'=> -1, 'msg'=>'您已赞过了哦']);
			}
			$res = Db::name('user_zan')->insert($data);
			if($res) {
				return json(['code'=> 0, 'msg'=>'点赞成功']);
			}
		}
		
	}
	
}