<?php

use Colors\Color;

function welcome()
{
    $tb = new Console_Table(CONSOLE_TABLE_ALIGN_CENTER, CONSOLE_TABLE_BORDER_ASCII, 20);
    $tb->addRow(['Novel Coll']);
    $tb->addRow(['']);
    $tb->addRow(['By QWQAQ.com']);
    print($tb->getTable());
}

function _D($msg)
{
    print($msg . PHP_EOL);
}

function _I($msg)
{
    $c = new Color();
    print($c('[消息]' . $msg)->light_blue() . PHP_EOL);
}

function _S($msg)
{
    $c = new Color();
    print($c('[成功]' . $msg)->light_green() . PHP_EOL);
}

function _W($msg)
{
    $c = new Color();
    print($c('[警告]' . $msg)->light_yellow() . PHP_EOL);
}

function _E($msg)
{
    $c = new Color();
    print($c('[错误]' . $msg)->light_red() . PHP_EOL);
}

function readInput($promptText = '')
{
    fwrite(STDOUT, $promptText);
    
    return trim(fgets(STDIN));
}

function fileNameStripBadStr($fileName)
{
    $bad = array_merge(array_map('chr', range(0, 31)), ["<", ">", ":", '"', "/", "\\", "|", "?", "*"]);
    
    return str_replace($bad, "", $fileName);
}

function checkFilePath($filePath)
{
    if (!empty(dirname($filePath)) && !file_exists(dirname($filePath))) {
        @mkdir(dirname($filePath), 0777, true);
    }
}

function getByUrl($url, $reqOpts, $isGBK)
{
    $client = new \GuzzleHttp\Client();
    try {
        $res = $client->request('GET', $url, $reqOpts);
    } catch (GuzzleHttp\Exception\GuzzleException $exception) {
        _E('请求失败：' . $exception->getMessage());
        die();
    }
    
    $body = $res->getBody();
    if ($isGBK) $body = handleGbkPage($body);
    
    return $body;
}

function handleGbkPage($html)
{
    $html = mb_convert_encoding($html, 'UTF-8', 'GBK');
    $html = preg_replace('/charset=(gb2312|gbk)/is', 'charset=utf-8', $html); // 必须将 <meta/> 中 charset=* 替换为 utf-8，不然 phpQuery 不能解析标签
    
    return $html;
}

function br2nl($text)
{
    return preg_replace('/<br\\s*?\/??>/i', PHP_EOL, $text);
}

function contentSave($novelName, $index, $title, $content)
{
    global $_config;
    $saveBasePath = $_config['saveBasePath'];
    if ($_config['allInOneFile'] === false) {
        $fileName = $saveBasePath . '/' . $novelName . '/' . fileNameStripBadStr("[{$index}] {$title}.txt");
        checkFilePath($fileName);
        file_put_contents($fileName, $content);
        _S("已保存 FILE=\"{$fileName}\"");
    } else {
        $fileName = $saveBasePath . '/' . fileNameStripBadStr("{$novelName}.txt");
        checkFilePath($fileName);
        $content = "[{$index}] {$title}" . PHP_EOL . PHP_EOL . $content . PHP_EOL . PHP_EOL . PHP_EOL;
        file_put_contents($fileName, $content, FILE_APPEND);
        _S("已保存 FILE=\"{$fileName}\"");
    }
}

function getUrlReg($urlHostRule)
{
    if (empty($urlHostRule)) return null;
    $handledStr = str_replace(['*', '/'], ['(.*?)', '\/'], addslashes($urlHostRule));
    return '/^' . $handledStr . '/is';
}

function getSupportByUrl($url) {
    global $_supports;
    if (empty($url)) return false;
    if (!urlValidator($url)) return false;
    $supportKeys = array_keys($_supports);
    foreach ($supportKeys as $key) {
        if (preg_match(getUrlReg($key), $url))
            return $key;
    }
    return false;
}

function urlValidator($value, $httpType = 'https|http')
{
    if (is_string($value) && strlen($value) < 2000) {
        if (preg_match('/^(' . $httpType . '):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i', $value)) {
            return true;
        }
    }
    
    return false;
}

function printCollList($data, array $afterColNames = [], callable $afterTextHandle = null)
{
    $tbl = new Console_Table();
    $tbl->setHeaders(array_merge(['INDEX', '标题', '链接'], $afterColNames));
    foreach ($data as $index => $item) {
        $after = [];
        if (!is_null($afterTextHandle)) $after = $afterTextHandle($index, $item);
        $tbl->addRow(array_merge([$index, $item['标题'], $item['链接']], $after));
    }
    print($tbl->getTable());
}

function getBaseUrl($url)
{
    if (empty($url)) return '';
    $url = parse_url($url);
    return $url['scheme']."://".$url['host'];
}

function getUrlPathAndQuery($url)
{
    if (empty($url)) return '';
    $url = parse_url($url);
    return $url['path'] . (!empty($url['query']) ? '?'.$url['query'] : '');
}
