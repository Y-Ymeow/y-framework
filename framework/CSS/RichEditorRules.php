<?php

declare(strict_types=1);

namespace Framework\CSS;

class RichEditorRules
{
    public static function getStyles(): string
    {
        return <<<CSS
/* Minimalist Stream Rich Editor Styles */
.ux-rich-editor {
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius, 0.375rem);
    background: var(--ux-bg-primary, #fff);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.ux-rich-editor:focus-within {
    border-color: var(--ux-primary, #3b82f6);
    box-shadow: 0 0 0 3px var(--ux-primary-light, rgba(59, 130, 246, 0.1));
}

/* Toolbar */
.ux-rich-editor__toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.25rem;
    padding: 0.5rem;
    background: var(--ux-bg-secondary, #f9fafb);
    border-bottom: 1px solid var(--ux-border-color, #e5e7eb);
    position: sticky;
    top: 0;
    z-index: 10;
}

.ux-rich-editor__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    padding: 0;
    border: none;
    border-radius: var(--ux-radius-sm, 0.25rem);
    background: transparent;
    color: var(--ux-text-primary, #374151);
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 1.1rem;
}

.ux-rich-editor__btn:hover {
    background: var(--ux-bg-hover, #e5e7eb);
    color: var(--ux-text-primary, #111827);
}

.ux-rich-editor__btn:active {
    background: var(--ux-bg-active, #d1d5db);
}

.ux-rich-editor__separator {
    width: 1px;
    height: 1.25rem;
    margin: 0 0.375rem;
    background: var(--ux-border-color, #d1d5db);
}

/* Editor Area (Canvas) */
.ux-rich-editor__area {
    flex: 1;
    padding: 1.5rem 2rem;
    overflow-y: auto;
    line-height: 1.7;
    color: var(--ux-text-primary, #1f2937);
    outline: none;
    min-height: 300px;
    cursor: text;
}

/* Document Typography */
.ux-rich-editor__area p {
    margin: 0 0 1rem;
}

.ux-rich-editor__area h1,
.ux-rich-editor__area h2,
.ux-rich-editor__area h3,
.ux-rich-editor__area h4 {
    margin: 1.5rem 0 1rem;
    font-weight: 700;
    line-height: 1.3;
    color: #111827;
}

.ux-rich-editor__area h1 { font-size: 2rem; }
.ux-rich-editor__area h2 { font-size: 1.5rem; }
.ux-rich-editor__area h3 { font-size: 1.25rem; }

.ux-rich-editor__area blockquote {
    margin: 1.5rem 0;
    padding: 0.5rem 1rem;
    border-left: 4px solid #e5e7eb;
    background: #f9fafb;
    color: #4b5563;
    font-style: italic;
}

.ux-rich-editor__area ul,
.ux-rich-editor__area ol {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.ux-rich-editor__area li {
    margin: 0.5rem 0;
}

.ux-rich-editor__area a {
    color: var(--ux-primary, #3b82f6);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.ux-rich-editor__area img {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
    margin: 1rem 0;
}

/* Placeholder */
.ux-rich-editor__area:empty:before {
    content: attr(data-placeholder);
    color: var(--ux-text-muted, #9ca3af);
    pointer-events: none;
}

/* Form Helpers */
.ux-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.ux-form-help {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.375rem;
}

.text-danger {
    color: #ef4444;
    margin-left: 0.25rem;
}
CSS;
    }

    public static function getVariables(): array
    {
        return [
            '--ux-border-color' => '#d1d5db',
            '--ux-radius' => '0.375rem',
            '--ux-radius-sm' => '0.25rem',
            '--ux-bg-primary' => '#ffffff',
            '--ux-bg-secondary' => '#f3f4f6',
            '--ux-bg-hover' => '#e5e7eb',
            '--ux-bg-active' => '#d1d5db',
            '--ux-bg-disabled' => '#f3f4f6',
            '--ux-text-primary' => '#374151',
            '--ux-text-secondary' => '#6b7280',
            '--ux-text-muted' => '#9ca3af',
            '--ux-primary' => '#3b82f6',
            '--ux-primary-dark' => '#2563eb',
            '--ux-primary-light' => '#dbeafe',
        ];
    }
}
