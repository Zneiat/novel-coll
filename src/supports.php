<?php

use QL\Dom\Elements;
const SITE_NAME = 'SITE_NAME'; // 网站名
const SEL_NAME = 'SEL_NAME'; // 小说名
const SEL_LIST_TITLE = 'SEL_LIST_TITLE'; // 列表 标题
const SEL_LIST_LINK = 'SEL_LIST_LINK'; // 列表 链接
const SEL_CONTENT = 'SEL_CONTENT'; // 内容
const CONTENT_HANDLE = 'CONTENT_HANDLE'; // 内容处理
const REQ_HEADERS = 'REQ_HEADERS'; // 请求 headers
const IS_GBK = 'IS_GBK'; // 是否为 GBK

return [
    'http://www.jjwxc.net/onebook.php*' => [
        SITE_NAME => '晋江文学城',
        SEL_NAME => '[itemprop="articleSection"]',
        SEL_LIST_TITLE => 'tr[itemtype="http://schema.org/Chapter"] [itemprop="url"]',
        SEL_LIST_LINK => 'tr[itemtype="http://schema.org/Chapter"] [itemprop="url"]',
        SEL_CONTENT => '.noveltext',
        CONTENT_HANDLE => function (Elements $elem) {
            $elem->find('div')->remove();
            $elem->find('hr')->remove();
            return br2nl($elem->html());
        },
        REQ_HEADERS => [],
        IS_GBK => true,
    ],
    'http://www.lwxiaoshuo.com/*/*/index.html' => [
        SITE_NAME => '乐文小说网',
        SEL_NAME => '.infot:nth-child(1) > h1',
        SEL_LIST_TITLE => '.bookinfo_td table .dccss a',
        SEL_LIST_LINK => '.bookinfo_td table .dccss a',
        SEL_CONTENT => '#content > p', // 不管源站 html 多不规范，标签名都要保持小写
        CONTENT_HANDLE => function (Elements $elem) {
            $html = $elem->html();
            return str_replace(['&nbsp;'], [''], br2nl($html));
        },
        REQ_HEADERS => [],
        IS_GBK => true,
    ]
];