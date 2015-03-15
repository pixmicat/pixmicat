<?php
namespace Pixmicat\Logger;

use Pixmicat\Aop\MethodInterceptor;

/**
 * 事件記錄器注入
 * 使用 MethodInterceptor 代理包裹物件方法，藉此注入 Logger。
 */
class LoggerInjector
{
    private $principalClass;
    /** @var MethodInterceptor */
    private $mi;

    public function __construct($principalClass, MethodInterceptor $mi)
    {
        $this->setPrincipalClass($principalClass);
        $this->setMethodInterceptor($mi);
    }

    private function setPrincipalClass($principalClass)
    {
        if (!\is_object($principalClass)) {
            throw new \InvalidArgumentException('PrincipalClass is not a valid object.');
        }
        $this->principalClass = $principalClass;
    }

    private function setMethodInterceptor(MethodInterceptor $mi)
    {
        $this->mi = $mi;
    }

    /**
     * 以 MethodInterceptor 注入記錄器
     *
     * @param  string $name 呼叫方法名稱
     * @param  array $args 呼叫方法參數
     * @return mixed       呼叫方法回傳值
     */
    public function __call($name, $args)
    {
        if (!\method_exists($this->principalClass, $name)) {
            return;
        }
        return $this->mi->invoke(array($this->principalClass, $name), $args);
    }
}
