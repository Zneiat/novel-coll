<?php
/**
 * Created by PhpStorm.
 * User: Zneiat
 * Date: 2018/7/14
 * Time: 下午 2:28
 */

namespace Kernel;

class Action implements ActionInterface {
    public static $ACTION_NAME = '';
    
    protected $supportProvider; // 支援提供者
    protected $supportKey; // 支援的键
    protected $support; // 支援
    
    protected $startUrl; // 起始 URL
    protected $startUrlPromptText = '输入 URL: ';
    
    public function __construct(array $arg)
    {
        $this->importSupportProvider();
        $this->run();
    }
    
    public function run()
    {
        // 获取 "起始 URL"
        $this->startUrl = prompt($this->startUrlPromptText, [
            'required' => true,
            'validator' => function ($input) {
                return !!$this->getSupportKeyByUrl($input);
            },
            'error' => '不支持采集该 URL'
        ]);
        $this->setSupportByUrl($this->startUrl);
        _I('采集规则已根据 URL 自动选定' . PHP_EOL);
    }
    
    private function importSupportProvider()
    {
        $file = APP_ROOT . '/SupportProviders/' . basename(get_called_class()) . '.php';
        if (file_exists($file)) {
            $this->supportProvider = require $file;
        }
    }
    
    public function getSupportProvider()
    {
        return $this->supportProvider;
    }
    
    protected function getSupportKeyByUrl($url)
    {
        if (empty($url)) return false;
        if (!urlValidator($url)) return false;
        $supportKeys = array_keys($this->supportProvider);
        foreach ($supportKeys as $key) {
            if (preg_match(getUrlReg($key), $url)) {
                return $key;
            }
        }
        return false;
    }
    
    protected function setSupportByUrl($url)
    {
        if (($supportKey = $this->getSupportKeyByUrl($url)) !== false) {
            $this->supportKey = $supportKey;
            $this->support = $this->supportProvider[$supportKey];
        }
    }
}