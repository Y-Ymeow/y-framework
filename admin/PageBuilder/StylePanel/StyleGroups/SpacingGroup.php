<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel\StyleGroups;

use Admin\PageBuilder\StylePanel\StyleGroupInterface;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;

class SpacingGroup implements StyleGroupInterface
{
    private array $spacingScale = [
        '' => '默认', '0' => '0', '0.5' => '0.125rem', '1' => '0.25rem', '1.5' => '0.375rem',
        '2' => '0.5rem', '2.5' => '0.625rem', '3' => '0.75rem', '3.5' => '0.875rem',
        '4' => '1rem', '5' => '1.25rem', '6' => '1.5rem', '7' => '1.75rem',
        '8' => '2rem', '9' => '2.25rem', '10' => '2.5rem', '11' => '2.75rem',
        '12' => '3rem', '14' => '3.5rem', '16' => '4rem', '20' => '5rem',
        '24' => '6rem', '28' => '7rem', '32' => '8rem', '36' => '9rem',
        '40' => '10rem', '44' => '11rem', '48' => '12rem', '52' => '13rem',
        '56' => '14rem', '60' => '15rem', '64' => '16rem', '72' => '18rem',
        '80' => '20rem', '96' => '24rem',
    ];

    private array $gapScale = [
        '' => '默认', '0' => '0', '1' => '0.25rem', '2' => '0.5rem', '3' => '0.75rem',
        '4' => '1rem', '5' => '1.25rem', '6' => '1.5rem', '8' => '2rem',
        '10' => '2.5rem', '12' => '3rem', '16' => '4rem', '20' => '5rem',
        '24' => '6rem', '32' => '8rem',
    ];

    public function name(): string { return 'spacing'; }
    public function label(): string { return '间距'; }
    public function icon(): string { return 'arrows-expand'; }

    public function canHandle(string $baseClass): bool
    {
        if (preg_match('/^[mp](t|r|b|l|x|y)?-\d+(\.\d+)?$/', $baseClass)) return true;
        if (preg_match('/^[mp]-(auto|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^-m(t|r|b|l|x|y)?-\d+(\.\d+)?$/', $baseClass)) return true;
        if (preg_match('/^gap(-x|-y)?-\d+(\.\d+)?$/', $baseClass)) return true;
        if (preg_match('/^gap(-x|-y)?-\[.*\]$/', $baseClass)) return true;
        if (preg_match('/^space-[xy]-\d+$/', $baseClass)) return true;
        return false;
    }

    public function fields(FormBuilder $form, array $currentClasses): void
    {
        $p = $this->extractSpacingValue($currentClasses, 'p');
        $px = $this->extractSpacingValue($currentClasses, 'px');
        $py = $this->extractSpacingValue($currentClasses, 'py');
        $m = $this->extractSpacingValue($currentClasses, 'm');
        $mx = $this->extractSpacingValue($currentClasses, 'mx');
        $my = $this->extractSpacingValue($currentClasses, 'my');
        $gap = $this->extractGapValue($currentClasses);

        $form->schema([
            Grid::make(3)->schema([
                Select::make('style_p')->label('Padding')->options($this->spacingScale)->default($p),
                Select::make('style_px')->label('P 水平')->options($this->spacingScale)->default($px),
                Select::make('style_py')->label('P 垂直')->options($this->spacingScale)->default($py),
            ]),
            Grid::make(3)->schema([
                Select::make('style_m')->label('Margin')->options($this->spacingScale)->default($m),
                Select::make('style_mx')->label('M 水平')->options($this->spacingScale)->default($mx),
                Select::make('style_my')->label('M 垂直')->options($this->spacingScale)->default($my),
            ]),
            Grid::make(2)->schema([
                Select::make('style_gap')->label('Gap')->options($this->gapScale)->default($gap),
                Select::make('style_mx_auto')->label('水平居中')
                    ->options(['' => '否', 'mx-auto' => '是'])
                    ->default(in_array('mx-auto', $currentClasses) ? 'mx-auto' : ''),
            ]),
        ]);
    }

    public function extractClasses(array $data): array
    {
        $classes = [];
        $spacingKeys = ['style_p', 'style_px', 'style_py', 'style_pt', 'style_pr', 'style_pb', 'style_pl',
                        'style_m', 'style_mx', 'style_my', 'style_mt', 'style_mr', 'style_mb', 'style_ml'];

        foreach ($spacingKeys as $key) {
            if (!empty($data[$key])) {
                $cls = str_replace('style_', '', $key);
                $classes[] = $cls . '-' . $data[$key];
            }
        }

        if (!empty($data['style_gap'])) {
            $classes[] = 'gap-' . $data['style_gap'];
        }
        if (!empty($data['style_mx_auto'])) {
            $classes[] = 'mx-auto';
        }

        return $classes;
    }

    private function extractSpacingValue(array $classes, string $prefix): string
    {
        foreach ($classes as $cls) {
            if (preg_match('/^' . $prefix . '-(\d+(?:\.\d+)?)$/', $cls, $m)) {
                return $m[1];
            }
        }
        return '';
    }

    private function extractGapValue(array $classes): string
    {
        foreach ($classes as $cls) {
            if (preg_match('/^gap-(\d+(?:\.\d+)?)$/', $cls, $m)) {
                return $m[1];
            }
        }
        return '';
    }
}
