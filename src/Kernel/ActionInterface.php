<?php
/**
 * Created by PhpStorm.
 * User: Zneiat
 * Date: 2018/7/14
 * Time: 下午 2:36
 */

namespace Kernel;


interface ActionInterface
{
    public function __construct(array $arg);
    public function run();
}