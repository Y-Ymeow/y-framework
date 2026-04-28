<?php

declare(strict_types=1);

namespace Admin\Pages;

use function t;
use function choice;
use Framework\Routing\Attribute\Get;
use Framework\Routing\Attribute\Post;
use Framework\Routing\Attribute\Route;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Text;
use Framework\View\Document\Document;
use Framework\View\Document\AssetRegistry;
use Framework\Queue\QueueManager;
use Framework\Queue\Job;

#[Route('/admin/demo/features')]
class DemoFeaturesPage
{
    #[Post('/push-job')]
    public function pushJob()
    {
        $job = Job::make(\App\Jobs\DemoJob::class, [
            'message' => 'Test job at ' . date('H:i:s'),
        ]);
        $result = QueueManager::driver()->push($job);

        return \Framework\Http\Response::json([
            'success' => $result,
            'job' => $job->id,
            'queue_size' => QueueManager::size(),
        ]);
    }

    #[Post('/set-locale')]
    public function setLocale()
    {
        $locale = request()->input('locale', 'en');
        app()->setLocale($locale);
        return \Framework\Http\Response::json(['success' => true, 'locale' => $locale]);
    }

    #[Post('/process-queue')]
    public function processQueue()
    {
        $result = \Framework\Queue\QueueManager::driver()->pop('default');
        return \Framework\Http\Response::json([
            'success' => $result !== null,
            'processed' => $result,
            'queue_size' => QueueManager::size(),
        ]);
    }

    #[Get('/intl-queue-schedule')]
    public function render()
    {
        Document::setTitle('Intl, Queue & Schedule Demo');
        AssetRegistry::getInstance()->ux();
        AssetRegistry::getInstance()->js(vite('resources/js/ux.js'));

        return \Framework\Http\Response::html(
            Container::make()
                ->class('min-h-screen bg-gray-50 p-8')
                ->children(
                    Text::h1('Intl, Queue & Schedule 测试')->class('text-3xl font-bold mb-8 text-gray-800'),
                    
                    Container::make()
                        ->class('mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4')
                        ->children(
                            Text::h2('测试说明')->class('text-lg font-semibold mb-2 text-blue-800'),
                            Text::p('1. 多语言翻译：切换语言查看翻译效果')->class('text-sm text-blue-700'),
                            Text::p('2. 队列系统：添加任务到队列')->class('text-sm text-blue-700'),
                            Text::p('3. 计划任务：查看计划任务配置')->class('text-sm text-blue-700'),
                        ),

                    Container::make()
                        ->class('grid grid-cols-3 gap-6')
                        ->children(
                            Container::make()
                                ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
                                ->children(
                                    Text::h2('多语言翻译')->class('text-xl font-semibold mb-4 text-gray-700'),
                                    
                                    Element::make('div')
                                        ->class('space-y-3 mb-4')
                                        ->children(
                                            Text::p(t('messages.welcome'))->class('text-gray-600'),
                                            Text::p('Login: ' . t('messages.login'))->class('text-gray-600'),
                                            Text::p('Items count: ' . choice('messages.items', 5))->class('text-gray-600'),
                                        ),

                                    Container::make()
                                        ->class('flex gap-2')
                                        ->children(
                                            Element::make('button')
                                                ->class('px-4 py-2 bg-blue-500 text-white rounded-lg')
                                                ->attr('type', 'button')
                                                ->liveAction('setLocale')
                                                ->liveParams(['locale' => 'en'])
                                                ->text('English'),
                                            
                                            Element::make('button')
                                                ->class('px-4 py-2 bg-red-500 text-white rounded-lg')
                                                ->attr('type', 'button')
                                                ->liveAction('setLocale')
                                                ->liveParams(['locale' => 'zh'])
                                                ->text('中文'),
                                        )
                                ),

                            Container::make()
                                ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
                                ->children(
                                    Text::h2('队列系统')->class('text-xl font-semibold mb-4 text-gray-700'),
                                    
                                    Element::make('div')
                                        ->class('mb-4 space-y-1')
                                        ->children(
                                            Text::p('驱动: ' . config('queue.default', 'sync'))->class('text-gray-600'),
                                            Text::p('队列大小: ' . QueueManager::size())->class('text-gray-600'),
                                        ),

                                    Container::make()
                                        ->class('flex gap-2 mb-3')
                                        ->children(
                                            Element::make('button')
                                                ->class('px-4 py-2 bg-green-500 text-white rounded-lg')
                                                ->attr('type', 'button')
                                                ->liveAction('pushJob')
                                                ->text('添加队列任务'),
                                            
                                            Element::make('button')
                                                ->class('px-4 py-2 bg-blue-500 text-white rounded-lg')
                                                ->attr('type', 'button')
                                                ->liveAction('processQueue')
                                                ->text('处理队列任务'),
                                        ),

                                    Element::make('p')
                                        ->class('text-xs text-gray-400')
                                        ->text('添加任务后，点击"处理队列任务"来执行'),
                                ),

                            Container::make()
                                ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
                                ->children(
                                    Text::h2('计划任务')->class('text-xl font-semibold mb-4 text-gray-700'),
                                    
                                    Element::make('div')
                                        ->class('space-y-2')
                                        ->children(
                                            Text::p('模式: ' . config('schedule.mode', 'route'))->class('text-gray-600'),
                                            Text::p('时区: ' . config('schedule.timezone', 'UTC'))->class('text-gray-600'),
                                        ),

                                    Container::make()
                                        ->class('mt-4')
                                        ->children(
                                            Text::p('路由模式访问')->class('text-sm text-blue-600'),
                                            Element::make('code')
                                                ->class('block bg-gray-100 p-2 rounded mt-1 text-sm')
                                                ->text('POST /_schedule/run?token=xxx'),
                                        )
                                )
                        )
                )
                ->render()
        );
    }
}
