<?php

require_once 'console.php';

function fileNameStripBadStr($fileName, $replace='')
{
    $bad = array_merge(array_map('chr', range(0, 31)), ["<", ">", ":", '"', "/", "\\", "|", "?", "*"]);
    
    return str_replace($bad, $replace, $fileName);
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
        return false;
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

function getUrlReg($urlHostRule)
{
    if (empty($urlHostRule)) return null;
    $handledStr = str_replace(['*', '/'], ['(.*?)', '\/'], addslashes($urlHostRule));
    return '/^' . $handledStr . '/is';
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
