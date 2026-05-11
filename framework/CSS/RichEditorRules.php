<?php

declare(strict_types=1);

namespace Framework\CSS;

class RichEditorRules
{
    public static function getStyles(): string
    {
        return <<<CSS
/* Rich Editor Styles */
.ux-rich-editor {
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius, 0.375rem);
    background: var(--ux-bg-primary, #fff);
    overflow: hidden;
}

.ux-rich-editor--minimal {
    border: none;
    background: transparent;
}

.ux-rich-editor--border {
    border: 1px solid var(--ux-border-color, #d1d5db);
}

/* Modal for RichEditor (Dedicated) */
.ux-rich-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: ux-fade-in 0.2s ease-out;
}

.ux-rich-modal-overlay.fade-out {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.ux-rich-modal {
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    width: 100%;
    max-width: 400px;
    overflow: hidden;
    animation: ux-slide-up 0.2s ease-out;
}

.ux-rich-modal-header {
    padding: 1rem;
    font-weight: 600;
    font-size: 1.125rem;
    border-bottom: 1px solid #f3f4f6;
    color: #111827;
}

.ux-rich-modal-body {
    padding: 1rem;
}

.ux-rich-modal-field {
    margin-bottom: 1rem;
}

.ux-rich-modal-field:last-child {
    margin-bottom: 0;
}

.ux-rich-modal-field label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
}

.ux-rich-modal-field input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    outline: none;
    transition: border-color 0.15s ease;
}

.ux-rich-modal-field input:focus {
    border-color: var(--ux-primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.ux-rich-modal-footer {
    padding: 0.75rem 1rem;
    background: #f9fafb;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.ux-rich-modal-btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ux-rich-modal-btn.cancel {
    background: #fff;
    border: 1px solid #d1d5db;
    color: #374151;
}

.ux-rich-modal-btn.cancel:hover {
    background: #f3f4f6;
}

.ux-rich-modal-btn.confirm {
    background: var(--ux-primary, #3b82f6);
    border: 1px solid var(--ux-primary, #3b82f6);
    color: #fff;
}

.ux-rich-modal-btn.confirm:hover {
    background: var(--ux-primary-dark, #2563eb);
}

@keyframes ux-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes ux-slide-up {
    from { transform: translateY(10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Toolbar */
.ux-rich-editor__toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.25rem;
    padding: 0.5rem;
    background: var(--ux-bg-secondary, #f3f4f6);
    border-bottom: 1px solid var(--ux-border-color, #d1d5db);
}

.ux-rich-editor--minimal .ux-rich-editor__toolbar {
    display: none;
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
}

.ux-rich-editor__btn:hover {
    background: var(--ux-bg-hover, #e5e7eb);
    color: var(--ux-text-primary, #1f2937);
}

.ux-rich-editor__btn:active {
    background: var(--ux-bg-active, #d1d5db);
    transform: translateY(1px);
}

.ux-rich-editor__btn svg {
    width: 1rem;
    height: 1rem;
}

.ux-rich-editor__separator {
    width: 1px;
    height: 1.5rem;
    margin: 0 0.25rem;
    background: var(--ux-border-color, #d1d5db);
}

/* Editor Area */
.ux-rich-editor__canvas {
    min-height: 200px;
    padding: 1.5rem;
    background: #fff;
    cursor: text;
}

.ux-rich-editor__block-wrapper {
    position: relative;
    padding: 2px 8px;
    border: 1px solid transparent;
    transition: all 0.2s;
}

.ux-rich-editor__block-wrapper.is-active {
    background: rgba(59, 130, 246, 0.04);
    border-left: 2px solid var(--ux-primary, #3b82f6);
}

[contenteditable='true']:empty:before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
    display: block; /* For Firefox */
}

.ux-rich-editor__block-actions {
    position: absolute;
    right: 0;
    top: -24px;
    display: flex;
    gap: 4px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 2px;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ux-rich-editor__block-actions button {
    padding: 2px 8px;
    font-size: 11px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 2px;
}

.ux-rich-editor__block-actions button:hover {
    background: #f3f4f6;
}

.ux-rich-editor__area {
    min-height: 10rem;
    max-height: 30rem;
    padding: 0.75rem;
    overflow-y: auto;
    line-height: 1.6;
    color: var(--ux-text-primary, #374151);
    outline: none;
}

.ux-rich-editor__area:empty::before {
    content: attr(data-placeholder);
    color: var(--ux-text-muted, #9ca3af);
    pointer-events: none;
}

.ux-rich-editor__area:focus {
    background: var(--ux-bg-primary, #fff);
}

/* Content Styles */
.ux-rich-editor__area p {
    margin: 0 0 0.75rem;
}

.ux-rich-editor__area p:last-child {
    margin-bottom: 0;
}

.ux-rich-editor__area h1,
.ux-rich-editor__area h2,
.ux-rich-editor__area h3,
.ux-rich-editor__area h4,
.ux-rich-editor__area h5,
.ux-rich-editor__area h6 {
    margin: 1rem 0 0.5rem;
    font-weight: 600;
    line-height: 1.3;
}

.ux-rich-editor__area h1 { font-size: 1.5rem; }
.ux-rich-editor__area h2 { font-size: 1.25rem; }
.ux-rich-editor__area h3 { font-size: 1.125rem; }
.ux-rich-editor__area h4 { font-size: 1rem; }
.ux-rich-editor__area h5 { font-size: 0.875rem; }
.ux-rich-editor__area h6 { font-size: 0.75rem; }

.ux-rich-editor__area blockquote {
    margin: 0.75rem 0;
    padding: 0.5rem 0.75rem;
    border-left: 3px solid var(--ux-border-color, #d1d5db);
    background: var(--ux-bg-secondary, #f3f4f6);
    color: var(--ux-text-secondary, #6b7280);
    font-style: italic;
}

.ux-rich-editor__area pre {
    margin: 0.75rem 0;
    padding: 0.75rem;
    background: var(--ux-bg-secondary, #f3f4f6);
    border-radius: var(--ux-radius-sm, 0.25rem);
    overflow-x: auto;
}

.ux-rich-editor__area code {
    padding: 0.125rem 0.375rem;
    background: var(--ux-bg-secondary, #f3f4f6);
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.875em;
}

.ux-rich-editor__area pre code {
    padding: 0;
    background: none;
}

.ux-rich-editor__area ul,
.ux-rich-editor__area ol {
    margin: 0.75rem 0;
    padding-left: 1.5rem;
}

.ux-rich-editor__area li {
    margin: 0.25rem 0;
}

.ux-rich-editor__area a {
    color: var(--ux-primary, #3b82f6);
    text-decoration: underline;
}

.ux-rich-editor__area a:hover {
    color: var(--ux-primary-dark, #2563eb);
}

.ux-rich-editor__area img {
    max-width: 100%;
    height: auto;
    border-radius: var(--ux-radius-sm, 0.25rem);
}

.ux-rich-editor__area strong,
.ux-rich-editor__area b {
    font-weight: 600;
}

.ux-rich-editor__area em,
.ux-rich-editor__area i {
    font-style: italic;
}

.ux-rich-editor__area u {
    text-decoration: underline;
}

.ux-rich-editor__area s,
.ux-rich-editor__area strike,
.ux-rich-editor__area del {
    text-decoration: line-through;
}

.ux-rich-editor__area br {
    display: block;
    content: "";
    margin: 0.5rem 0;
}

/* Extension Styles */
.mention-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    background: var(--ux-primary-light, #dbeafe);
    color: var(--ux-primary, #2563eb);
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-weight: 500;
    cursor: default;
}

.mention-tag::before {
    content: "@";
    margin-right: 0.125rem;
}

.placeholder-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    background: var(--ux-warning-light, #fef3c7);
    color: var(--ux-warning, #d97706);
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-weight: 500;
    cursor: default;
    user-select: none;
}

/* Focus States */
.ux-rich-editor:focus-within {
    border-color: var(--ux-primary, #3b82f6);
    box-shadow: 0 0 0 3px var(--ux-primary-light, rgba(59, 130, 246, 0.1));
}

.ux-rich-editor--minimal:focus-within {
    box-shadow: none;
}

/* Disabled State */
.ux-rich-editor__area[contenteditable="false"] {
    background: var(--ux-bg-disabled, #f3f4f6);
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 640px) {
    .ux-rich-editor__toolbar {
        padding: 0.375rem;
    }
    
    .ux-rich-editor__btn {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    .ux-rich-editor__area {
        min-height: 8rem;
        padding: 0.5rem;
    }
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
            '--ux-warning' => '#d97706',
            '--ux-warning-light' => '#fef3c7',
        ];
    }
}
