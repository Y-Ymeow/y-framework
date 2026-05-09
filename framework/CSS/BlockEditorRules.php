<?php

declare(strict_types=1);

namespace Framework\CSS;

class BlockEditorRules
{
    public static function getStyles(): string
    {
        return <<<'CSS'
.ux-block-editor {
    display: flex;
    flex-direction: column;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
    overflow: hidden;
}
.ux-block-editor-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}
.ux-block-editor-main {
    display: flex;
    flex: 1;
    overflow: hidden;
}
.ux-block-editor-canvas {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
    background: #f3f4f6;
}
.ux-block-editor-sidebar {
    width: 280px;
    border-left: 1px solid #e5e7eb;
    background: #fff;
    display: flex;
    flex-direction: column;
}
.ux-block-editor-sidebar-title {
    padding: 1rem;
    font-weight: 600;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}
.ux-block-editor-sidebar-content {
    padding: 1rem;
    flex: 1;
    overflow-y: auto;
}
.ux-block-item {
    position: relative;
    background: #fff;
    border: 2px solid transparent;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
    transition: all 0.2s;
}
.ux-block-item:hover {
    border-color: #d1d5db;
}
.ux-block-item.is-selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}
.ux-block-item-toolbar {
    position: absolute;
    top: -1.25rem;
    left: 0;
    right: 0;
    display: none;
    justify-content: space-between;
    align-items: center;
    padding: 0 0.5rem;
    z-index: 10;
}
.ux-block-item:hover .ux-block-item-toolbar,
.ux-block-item.is-selected .ux-block-item-toolbar {
    display: flex;
}
.ux-block-item-type {
    background: #3b82f6;
    color: #fff;
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem 0.25rem 0 0;
    text-transform: uppercase;
}
.ux-block-item-actions {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    display: flex;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.ux-block-item-action {
    background: none;
    border: none;
    padding: 0.25rem 0.5rem;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.15s;
}
.ux-block-item-action:hover {
    color: #111827;
    background: #f3f4f6;
}
.ux-block-item-action-danger:hover {
    color: #ef4444;
}
.ux-block-item-content {
    padding: 1rem;
    min-height: 1.5rem;
    outline: none;
}
.ux-block-item-content h1, 
.ux-block-item-content h2, 
.ux-block-item-content h3 {
    margin: 0;
}
.ux-block-image-placeholder {
    background: #f9fafb;
    border: 2px dashed #d1d5db;
    border-radius: 0.375rem;
    padding: 2rem;
    text-align: center;
    color: #9ca3af;
    cursor: pointer;
}
.ux-block-editor-empty {
    text-align: center;
    padding: 4rem 2rem;
    color: #9ca3af;
    background: #fff;
    border-radius: 0.5rem;
    border: 2px dashed #e5e7eb;
}
CSS;
    }
}
