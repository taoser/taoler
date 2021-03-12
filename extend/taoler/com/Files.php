<?php

namespace taoler\com;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class Files
{
    /**
     * 转换为/结尾的路径
     * @param $path string 文件夹路径
     * @return string
     */
    public static function getDirPath($path)
    {
        return substr($path,-1) == '/' ? $path : $path.'/';
    }

    /**
     * 获取目录下子目录名
     * @param $path string 目录
     * @return array
     */
	public static function getDirName($path)
	{
		if (is_dir($path)) {
			$arr = array();
			$data = scandir($path);
			foreach ($data as $value){
				if($value !='.' && $value != '..' && !stripos($value,".")){
				  $arr[] = strtolower($value);
				}
			  }
			 //return array_merge(array_diff($arr, array('install')));
			 return $arr;
		}
	}

    /**
     * 创建文件夹及子文件夹
     * @param $path
     * @return bool
     */
	public static function create_dirs($path)
	{
	  if (!is_dir($path))
	  {
		$directory_path = "";
		$directories = explode("/",$path);
		array_pop($directories);
	   
		foreach($directories as $directory)
		{
		  $directory_path .= $directory."/";
		  if (!is_dir($directory_path))
		  {
			mkdir($directory_path);
			chmod($directory_path, 0777);
		  }
		}
		return true;
	  }else {
          return false;
      }

	}

    /**
     * 删除文件夹及内容
     * @param string $dirPath
     * @param bool $nowDir 是否删除当前文件夹目录 true false
     * @return bool
     */
	public static function delDirAndFile(string $dirPath, $nowDir=false )
	{ 
		if ( $handle = opendir($dirPath) ) { 

			while ( false !== ( $item = readdir( $handle ) ) ) { 
				if ( $item != '.' && $item != '..' ) { 
					$path = $dirPath.$item;
					if (is_dir($path)) { 
						self::delDirAndFile($path.'/'); 
						rmdir($path.'/');
					} else { 
						unlink($path); 
					} 
				} 
			} 
			closedir( $handle );
            //删除当前文件夹
			if($nowDir == true){
				if(rmdir($dirPath)){
					return true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
		return true;
	}

    /**
     * 复制文件夹$source下的文件和子文件夹下的内容到$dest下 升级+备份代码
     * @param $source
     * @param $dest
     * @param array $ex 指定只复制$source下的目录,默认全复制
     * @return bool
     */
	public static function copyDirs($source, $dest, $ex=array())
	{
		if (!file_exists($dest)) mkdir($dest);
			if($handle = opendir($source)){
				while (($file = readdir($handle)) !== false) {
					if (( $file != '.' ) && ( $file != '..' )) {
						if (is_dir($source . $file) ) {
							//拷贝排除的文件夹
                            if(!in_array($file,$ex)){
                                self::copyDirs($source . $file.'/', $dest . $file.'/');
                            }
						} else {
						    //拷贝文件
							copy($source. $file, $dest . $file);
						}
					}
				}
				closedir($handle);
			} else {
			return false;
		}
		return true;	
	}

    /**
     * 检测目录并循环创建目录
     * @param $dir
     * @return bool
     */
    public static function mkdirs($dir)
    {
        if (!file_exists($dir)) {
            self::mkdirs(dirname($dir));
            mkdir($dir, 0777);
        }
        return true;
    }

    /**
     * 删除文件以及目录
     * @param $dir
     * @return bool
     */
    public static function delDir($dir) {
        //先删除目录下的文件：
//        var_dump(is_dir($dir));
//        if(!is_dir($dir)){
//            return true;
//        }
        $dh=opendir($dir);
        while ($file=readdir($dh)) {
            if($file!="." && $file!="..") {
                $fullpath=$dir."/".$file;
                if(!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    self::delDir($fullpath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹
        if(rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 复制文件到指定文件
     * @param $source
     * @param $dest
     * @return bool
     */
    public static function copyDir($source, $dest)
    {
        if (!is_dir($dest)) {
            self::mkdirs($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    self::mkdirs($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
        return true;
    }

    /**
     * 写入
     * @param $content
     * @param $filepath
     * @param $type $type 1 为生成控制器 2 模型
     * @throws \Exception
     */
    public static function filePutContents($content,$filepath,$type){
        if($type==1){
            $str = file_get_contents($filepath);
            $parten = '/\s\/\*+start\*+\/(.*)\/\*+end\*+\//iUs';
            preg_match_all($parten,$str,$all);
            $ext_content = '';
            if($all[0]){
                foreach($all[0] as $key=>$val){
                    $ext_content .= $val."\n\n";
                }
            }
            $content .= $ext_content."\n\n";
            $content .="}\n\n";
        }
        ob_start();
        echo $content;
        $_cache=ob_get_contents();
        ob_end_clean();
        if($_cache){
            $File = new \think\template\driver\File();
            $File->write($filepath, $_cache);
        }
    }

    /**
     * 获取文件夹大小
     * @param $dir 根文件夹路径
     * @return bool|int
     */
    public static function getDirSize($dir)
    {
        if(!is_dir($dir)){
            return false;
        }
        $handle = opendir($dir);
        $sizeResult = 0;
        while (false !== ($FolderOrFile = readdir($handle))) {
            if ($FolderOrFile != "." && $FolderOrFile != "..") {
                if (is_dir("$dir/$FolderOrFile")) {
                    $sizeResult += self::getDirSize("$dir/$FolderOrFile");
                } else {
                    $sizeResult += filesize("$dir/$FolderOrFile");
                }
            }
        }

        closedir($handle);
        return $sizeResult;
    }

    /**
     * 创建文件
     * @param $file
     * @param $content
     * @return bool
     */
    public static function createFile($file,$content)
    {

        $myfile = fopen($file, "w") or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
        return true;
    }

    /**
     * 基于数组创建目录
     * @param $files
     */
    public static function createDirOrFiles($files)
    {
        foreach ($files as $key => $value) {
            if (substr($value, -1) == '/') {
                mkdir($value);
            } else {
                file_put_contents($value, '');
            }
        }
    }


    /**
     * 判断文件或目录是否有写的权限
     * @param $file
     * @return bool
     */
    public static function isWritable($file)
    {
        if (DIRECTORY_SEPARATOR == '/' AND @ ini_get("safe_mode") == FALSE) {
            return is_writable($file);
        }
        if (!is_file($file) OR ($fp = @fopen($file, "r+")) === FALSE) {
            return FALSE;
        }
        fclose($fp);
        return TRUE;
    }

    /**
     * 写入日志
     * @param $path
     * @param $content
     * @return bool|int
     */
    public static function writeLog($path, $content)
    {
        self::mkdirs(dirname($path));
        return file_put_contents($path, "\r\n" . $content, FILE_APPEND);
    }
}