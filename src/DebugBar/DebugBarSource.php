<?php

declare(strict_types=1);

namespace Framework\DebugBar;

class DebugBarSource
{
    public static function renderCSS(string $position): string 
    {
        // 样式现在大部分由 UX 组件和内联样式处理，这里只保留必要的
        return '';
    }

    public static function renderJs(string $initData): string
    {
        return <<<JS
(function(){
    var initData=$initData;
    var debugKey=initData.debug_key||'';

    // 劫持 fetch
    var origFetch=window.fetch;
    window.fetch=function(){
        var url=arguments[0];
        var opts=arguments[1]||{};
        if(debugKey && debugKey !== ''){
            opts.headers=opts.headers||{};
            opts.headers['x-debug-key']=debugKey;
        }
        return origFetch.apply(this,arguments).then(function(response){
            // AJAX 请求完成后，通知 DebugBar 组件更新
            // 这里简单延迟一下，确保后端已经处理完并写入了 Storage
            setTimeout(function(){
                window.dispatchEvent(new CustomEvent('live:emit', { 
                    detail: { event: 'debugbar:update' } 
                }));
            }, 100);
            return response;
        });
    };

    // 手动引导 Y.boot 以确保动态注入的组件被识别
    if (window.Y) {
        window.Y.boot();
    } else {
        document.addEventListener('y:ready', function() {
            window.Y.boot();
        });
    }

    // 劫持 XMLHttpRequest
    var origXHR=window.XMLHttpRequest;
    var newXHR=function(){
        var xhr=new origXHR();
        var origOpen=xhr.open;
        xhr.open=function(m,u){
            if(debugKey && debugKey !== ''){
                xhr.addEventListener('beforesend', function(){
                     xhr.setRequestHeader('x-debug-key',debugKey);
                });
            }
            return origOpen.apply(this,arguments);
        };
        // 覆盖原生 send 以确保能设置 header
        var origSend = xhr.send;
        xhr.send = function() {
            if(debugKey && debugKey !== ''){
                this.setRequestHeader('x-debug-key',debugKey);
            }
            return origSend.apply(this, arguments);
        };
        xhr.addEventListener('load',function(){
            setTimeout(function(){
                window.dispatchEvent(new CustomEvent('live:emit', { 
                    detail: { event: 'debugbar:update' } 
                }));
            }, 100);
        });
        return xhr;
    };
    window.XMLHttpRequest=newXHR;
})();
JS;
    }
}
