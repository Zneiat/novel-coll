<?php
/**
 * Created by PhpStorm.
 * User: Zneiat
 * Date: 2018/7/14
 * Time: 下午 2:30
 */

namespace Actions;

use Colors\Color;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Kernel\Action;
use Psr\Http\Message\ResponseInterface;
use QL\QueryList;

class NovelAction extends Action {
    public static $ACTION_NAME = '小说采集器';
    
    private $novelName; // 小说名
    private $items = []; // 列表中得到的所有项目
    private $itemsCount;
    private $contentContainer = []; // 内容搜集容器
    private $errors = []; // 错误
    
    protected $startUrlPromptText = '输入小说列表页 URL: ';
    private $saveBasePath;
    private $reqOpts;
    
    public function run() {
        parent::run();
        
        # 配置
        $listPageUrl = $this->startUrl; // 小说列表页 URL
        $this->saveBasePath = APP_CONF['saveBasePath'];
        $this->reqOpts = [
            'decode_content' => 'gzip',
            'verify' => false,
            'headers'  => array_merge([
                'Accept-Encoding' => 'gzip, deflate',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36'
            ], $this->support[REQ_HEADERS]),
        ];
    
        # 下载列表页
        _I("正在获取列表页，URL='{$listPageUrl}'");
        if (!($listHtml = getByUrl($listPageUrl, $this->reqOpts, $this->support[IS_GBK]))) return;
        $listQl = (new QueryList())->html($listHtml);
    
        # 抓取小说名
        if (!empty(($this->novelName = fileNameStripBadStr($listQl->find($this->support[SEL_NAME])->text())))) {
            _S("列表页已获取，小说名：{$this->novelName}");
        } else {
            _E("小说名获取失败");
            return;
        }
    
        # 抓取 items
        $this->items = $listQl->rules([
            '标题' => [$this->support[SEL_LIST_TITLE], 'text'],
            '链接' => [$this->support[SEL_LIST_LINK], 'href'],
        ])->query()->getData()->all();
        
        # 处理 items
        foreach ($this->items as $i => $item) {
            $this->items[$i]['链接'] = getUrlPathAndQuery($item['链接']);
        }
    
        # 列表展示 items
        $this->itemsCount = count($this->items);
        $this->printCollList();
    
        # 逐页下载
        print(PHP_EOL . PHP_EOL);
        _I("开始执行逐页下载，TOTAL={$this->itemsCount}");
    
        # 逐页下载 创建请求
        $baseUrl = getBaseUrl($listPageUrl);
        $client = new Client(['base_uri' => $baseUrl]);
        $requests = function () use ($client)
        {
            foreach ($this->items as $id => $item)
            {
                yield function () use ($client, $item)
                {
                    return $client->getAsync($item['链接'], $this->reqOpts);
                };
            }
        };
    
        # 逐页下载 处理响应
        $onSuccess = function (ResponseInterface $response, $index) use ($baseUrl)
        {
            $item = $this->items[$index];
            $body = $response->getBody();
            if ($this->support[IS_GBK]) $body = handleGbkPage($body);
            $contentQl = (new QueryList())->html($body);
            $contentElem = $contentQl->find($this->support[SEL_CONTENT]);
            $content = $this->support[CONTENT_HANDLE]($contentElem);
            $fullUrl = "{$baseUrl}/{$item['链接']}";
            if (!empty($content)) {
                _S("[#".($index+1)."] 内容抓取成功，URL='{$fullUrl}'");
                $this->contentPut($index, $content);
            } else {
                _E('[#'.($index+1).'] '.($et="内容抓取失败，REASON='数据为空'，URL='{$fullUrl}'"));
                $this->errors[$index] = $et;
            }
        };
    
        $onError = function ($reason, $index) use ($baseUrl)
        {
            $item = $this->items[$index];
            $fullUrl = "{$baseUrl}/{$item['链接']}";
            _E('[#'.($index+1).'] '.($et="内容下载失败，REASON={$reason}'，URL='{$fullUrl}'"));
            $this->errors[$index] = $et;
        };
    
        $pool = new Pool($client, $requests(), [
            'concurrency' => 7, // 同时并发数
            'fulfilled'   => $onSuccess,
            'rejected'    => $onError,
        ]);
    
        # 开始发送请求
        $promise = $pool->promise();
        $promise->wait();
    
        # 输出采集情况
        print(PHP_EOL);
        $this->printCollList(['结果'], function ($index, $item) {
            if (!isset($this->errors[$index])) {
                return [(new Color('成功'))->light_green];
            } else {
                return [(new Color('失败'))->light_red];
            }
        });
    
        # 保存文件
        $this->contentSave();
    
        # 程序结束
        print(PHP_EOL);
    }
    
    private function contentPut($index, $content)
    {
        $this->contentContainer[$index] .= $content;
    }
    
    private function contentSave()
    {
        ksort($this->contentContainer);
        if (APP_CONF['NovelAction']['allInOneFile'] === false) {
            $dirPath = "{$this->saveBasePath}/{$this->novelName}";
            foreach ($this->contentContainer as $i => $content) {
                $number = $i + 1;
                $fileName = $dirPath . '/' . fileNameStripBadStr("[{$number}] {$this->items[$i]['标题']}.txt");
                checkFilePath($fileName);
                file_put_contents($fileName, $content);
            }
            _S("已保存 DIR='".realpath($dirPath)."'");
        } else {
            $contents = '';
            $fileName = $this->saveBasePath . '/' . fileNameStripBadStr("{$this->novelName}.txt");
            foreach ($this->contentContainer as $i => $content) {
                $number = $i + 1;
                checkFilePath($fileName);
                $contents .= "[{$number}] {$this->items[$i]['标题']}" . PHP_EOL . PHP_EOL . $content . PHP_EOL . PHP_EOL . PHP_EOL;
            }
            checkFilePath($fileName);
            file_put_contents($fileName, $contents);
            _S("已保存 FILE='".realpath($fileName)."'");
        }
    }
    
    private function printCollList(array $afterColNames = [], callable $afterTextHandle = null)
    {
        $tbl = new \Console_Table();
        $tbl->setHeaders(array_merge(['#', '标题', '链接'], $afterColNames));
        foreach ($this->items as $index => $item) {
            $after = [];
            if (!is_null($afterTextHandle)) $after = $afterTextHandle($index, $item);
            $tbl->addRow(array_merge([$index + 1, $item['标题'], $item['链接']], $after));
        }
        print($tbl->getTable());
    }
}