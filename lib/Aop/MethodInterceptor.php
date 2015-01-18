<?php
namespace Pixmicat\Aop;

/**
 * MethodInterceptor (AOP Around Advice)
 */
interface MethodInterceptor
{
    /**
     * 代理呼叫方法。
     *
     * @param  array  $callable 要被呼叫的方法
     * @param  array  $args     方法傳遞的參數
     * @return mixed            方法執行的結果
     */
    function invoke(array $callable, array $args);
}
