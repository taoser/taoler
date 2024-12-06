<?php
/**
 * @Program: table.css 2023/4/15
 * @FilePath: app\common\taglib\Taoler.php
 * @Description: Taoler.php
 * @LastEditTime: 2023-04-15 11:09:54
 * @Author: Taoker <317927823@qq.com>
 * @Copyright (c) 2020~2023 https://www.aieok.com All rights reserved.
 */

namespace app\common\taglib;

use think\template\TagLib;

class Taoler extends TagLib
{
    protected $tags   =  [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'nav'       => ['attr' => '', 'close' => 1],
        'snav'      => ['attr' => '', 'close' => 1],
        'gnav'      => ['attr' => '', 'close' => 1],
        'if'        => ['condition', 'expression' => true, 'close' => 1],
        'list'      => ['attr' => 'type', 'close' => 1, 'expression' => true],

    ];

    public function tagNav($tag, $content): string
    {
        $id = $tag['id'] ?? 'nav';
        $parse = '{php}$__cate__ = \app\facade\Category::getNav();{/php}';
        $parse .= '{volist name="__cate__" id="'.$id.'"}';
        $parse .= $content;
        $parse .= '{/volist}';
        return $parse;
    }

    public function tagSnav($tag, $content): string
    {
        $id = $tag['id'] ?? 'snav';
        $parse = '{notempty name="nav.children"}';
        $parse .= '{volist name="nav.children" id="'.$id.'"}';
        $parse .= $content;
        $parse .= '{/volist}';
        $parse .= '{/notempty}';
        return $parse;
    }

    public function tagGnav($tag, $content): string
    {
        $id = $tag['id'] ?? 'gnav';
        $parse = '{notempty name="snav.children"}';
        $parse .= '{volist name="snav.children" id="'.$id.'"}';
        $parse .= $content;
        $parse .= '{/volist}';
        $parse .= '{/notempty}';
        return $parse;
    }

    public function tagIf($tag, $content): string
    {

        $condition = !empty($tag['expression']) ? $tag['expression'] : $tag['condition'];
        $condition = $this->parseCondition($condition);
        $parseStr  = '<?php if(' . $condition . '): ?>' . $content . '<?php endif; ?>';

        return $parseStr;

//        return '{if'.$tag.'}} '.$content.' {/if}';

    }

    public function tagList(array $tag, string $content): string
    {
        $type = isset($tag['type']) ? $tag['type'] : '';
        $parse = match($type){
            "top" => '<?php $__articles__ = \app\facade\Article::getTops(); ?> {volist name="__articles__" id="article"}' .$content. '{/volist}',
            "hot" => '<?php $__articles__ = \app\facade\Article::getHots(); ?> {volist name="__articles__" id="article"}' .$content. '{/volist}',
            "index" => '<?php $__articles__ = \app\facade\Article::getIndexs(); ?> {volist name="__articles__" id="article"}' .$content. '{/volist}',
            default => '{assign name="ename" value="$Request.param.ename" /}{assign name="page" value="$Request.param.page ?? 1" /}{assign name="type" value="$Request.param.type ?? \'all\'" /}<?php $__articles__ = \app\facade\Category::getArticlesByCategoryEname($ename, $page, $type)[\'data\']; ?> {volist name="__articles__" id="article"}' .$content. '{/volist}'
        };

        // $parse = '{assign name="id" value="$Request.param.id" /}';
        // $parse .= '<?php ';
        // $parse .= '$__articles__ = \app\facade\Article::getTops();';
        // $parse .= ' \?\>';
        // $parse .= '{volist name="__articles__" id="article"}';
        // $parse .= $content;
        // $parse .= '{/volist}';
        
        return $parse;

    }


}