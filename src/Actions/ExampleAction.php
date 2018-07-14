<?php
namespace Actions;

use Kernel\Action;
use QL\QueryList;

class ExampleAction extends Action {
    public static $ACTION_NAME = '示例采集器';
    protected $startUrlPromptText = '输入关于百度的 URL: ';
    
    private $name;
    // ...
    
    public function run() {
        parent::run();
        
        $url = $this->startUrl;
        $path = APP_CONF['saveBasePath'];
        $sel = $this->support[SEL_NAME];
        // ...
        
        $name = (new QueryList())->get($url)->find($sel)->text();
        if (!empty($name))
            _S('数据已采集');
        else
            _E('数据未采集');
        
        $this->name = $name;
        var_dump($this->name);
        
        // ...
        
        checkFilePath($path);
        _I("数据将保存到：{$path}");
    }
}