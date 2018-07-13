<?php
/**
 * Created by PhpStorm.
 * User: Zneiat
 * Date: 2018/7/12
 * Time: 下午 10:32
 */

use Colors\Color;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;
use QL\QueryList;

require_once(__DIR__ . '/bootstrap.php');

# 读取用户键盘输入
do {
    $listPageUrl = readInput('粘贴要采集的列表页 URL: ');
    if(!($supportKey = getSupportByUrl($listPageUrl))){
        _E('不支持采集这个 URL');
        continue;
    }
    $support = $_supports[$supportKey];
    _I("规则已选定，目标网站：{$support[SITE_NAME]}" . PHP_EOL);
    break;
} while (true);

# 统一请求配置
$reqOpts = [
    'decode_content' => 'gzip',
    'verify' => false,
    'headers'  => array_merge([
        'Accept-Encoding' => 'gzip, deflate',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36'
    ], $support[REQ_HEADERS]),
];

# 下载列表页
_I("正在获取列表页，URL=\"{$listPageUrl}\"");
$listHtml = getByUrl($listPageUrl, $reqOpts, $support[IS_GBK]);
_S("列表页已获取");
$listQl = (new QueryList())->html($listHtml);

# 抓取小说名
if (!empty(($novelName = fileNameStripBadStr($listQl->find($support[SEL_NAME])->text())))) {
    _S("小说名：{$novelName}");
} else {
    _E("小说名获取失败");
    die();
}

# 抓取 items
$data = $listQl->rules([
    '标题' => [$support[SEL_LIST_TITLE], 'text'],
    '链接' => [$support[SEL_LIST_LINK], 'href'],
])->query()->getData()->all();

# 处理 items
foreach ($data as $i => $item) {
    $data[$i]['链接'] = getUrlPathAndQuery($item['链接']);
}

# 列表展示 items
printCollList($data);

# 逐页下载
print(PHP_EOL . PHP_EOL);
_I("开始执行逐页下载");

# 逐页下载 创建请求
$baseUrl = getBaseUrl($listPageUrl);
$client = new Client(['base_uri' => $baseUrl]);
$requests = function () use ($client, $data)
{
    foreach ($data as $id => $item)
    {
        yield function () use ($client, $item)
        {
            global $reqOpts;
            return $client->getAsync($item['链接'], $reqOpts);
        };
    }
};

$errorIndexList = [];

# 逐页下载 处理响应
$onSuccess = function (ResponseInterface $response, $index)
{
    global $data;
    global $novelName;
    global $support;
    global $errorIndexList;
    $item = $data[$index];
    _I("[{$index}] 抓取内容, URL=\"{$item['链接']}\"");
    $body = $response->getBody();
    if ($support[IS_GBK]) $body = handleGbkPage($body);
    $contentQl = (new QueryList())->html($body);
    $contentElem = $contentQl->find($support[SEL_CONTENT]);
    $content = $support[CONTENT_HANDLE]($contentElem);
    if (!empty($content)) {
        _S("[{$index}] 内容抓取成功");
        contentSave($novelName, $index, $item['标题'], $content);
    } else {
        _E("[{$index}] 内容抓取失败");
        $errorIndexList[] = $index;
    }
    
    print(PHP_EOL);
};

$onError = function ($reason, $index)
{
    global $data;
    global $errorIndexList;
    $item = $data[$index];
    _E("[{$index}] 内容下载失败，URL=\"{$item['链接']}\"");
    $errorIndexList[] = $index;
    print(PHP_EOL);
};

$pool = new Pool($client, $requests(), [
    'concurrency' => 7, // 同时并发数
    'fulfilled'   => $onSuccess,
    'rejected'    => $onError,
]);

# 开始发送请求
$promise = $pool->promise();
$promise->wait();

# 程序结束
print(PHP_EOL);
printCollList($data, ['结果'], function ($index, $item) use ($errorIndexList) {
    if (!in_array($index, $errorIndexList)) {
        return [(new Color('成功'))->green()];
    } else {
        return [(new Color('失败'))->red()];
    }
}); // 采集情况

print(PHP_EOL);
