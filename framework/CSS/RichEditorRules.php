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

    public static function getBlockEditorStyles(): string
    {
        return <<<CSS
/* Block Editor Styles */
.ux-block-editor {
    position: relative;
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius, 0.375rem);
    background: var(--ux-bg-primary, #fff);
}

.ux-block-editor:focus-within {
    border-color: var(--ux-primary, #3b82f6);
    box-shadow: 0 0 0 3px var(--ux-primary-light, rgba(59, 130, 246, 0.1));
}

.ux-block-editor__canvas {
    min-height: 12rem;
    padding: 1rem;
}

.ux-block-editor__empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 8rem;
    color: var(--ux-text-muted, #9ca3af);
    font-size: 0.875rem;
}

.ux-block-editor__block {
    position: relative;
    margin-bottom: 0.5rem;
    border: 1px solid transparent;
    border-radius: var(--ux-radius-sm, 0.25rem);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.ux-block-editor__block:hover {
    border-color: var(--ux-border-color, #d1d5db);
}

.ux-block-editor__block--selected {
    border-color: var(--ux-primary, #3b82f6);
    box-shadow: 0 0 0 1px var(--ux-primary, #3b82f6);
}

.ux-block-editor__block-toolbar {
    display: none;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: var(--ux-bg-secondary, #f3f4f6);
    border-bottom: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius-sm, 0.25rem) var(--ux-radius-sm, 0.25rem) 0 0;
    font-size: 0.75rem;
}

.ux-block-editor__block--selected .ux-block-editor__block-toolbar {
    display: flex;
}

.ux-block-editor__block-type {
    font-weight: 600;
    color: var(--ux-text-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.6875rem;
    margin-right: auto;
}

.ux-block-editor__block-actions {
    display: flex;
    gap: 0.125rem;
}

.ux-block-editor__block-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    padding: 0;
    border: none;
    border-radius: 0.125rem;
    background: transparent;
    color: var(--ux-text-secondary, #6b7280);
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.15s ease;
}

.ux-block-editor__block-btn:hover {
    background: var(--ux-bg-hover, #e5e7eb);
    color: var(--ux-text-primary, #374151);
}

.ux-block-editor__block-content {
    padding: 0.5rem;
}

.ux-block-editor__editable {
    min-height: 2rem;
    padding: 0.25rem;
    outline: none;
    line-height: 1.6;
    color: var(--ux-text-primary, #374151);
}

.ux-block-editor__editable[data-empty="true"] {
    color: var(--ux-text-muted, #9ca3af);
}

.ux-block-editor__editable--heading {
    font-weight: 600;
    font-size: 1.25rem;
    line-height: 1.3;
}

.ux-block-editor__editable--quote {
    padding: 0.5rem 0.75rem;
    border-left: 3px solid var(--ux-border-color, #d1d5db);
    background: var(--ux-bg-secondary, #f3f4f6);
    font-style: italic;
    border-radius: 0 var(--ux-radius-sm, 0.25rem) var(--ux-radius-sm, 0.25rem) 0;
}

.ux-block-editor__editable--code {
    padding: 0.75rem;
    background: var(--ux-bg-secondary, #f3f4f6);
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.875em;
    white-space: pre-wrap;
    word-break: break-all;
}

.ux-block-editor__input {
    display: block;
    width: 100%;
    padding: 0.375rem 0.5rem;
    margin-bottom: 0.375rem;
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-size: 0.8125rem;
    outline: none;
    transition: border-color 0.15s ease;
}

.ux-block-editor__input:focus {
    border-color: var(--ux-primary, #3b82f6);
    box-shadow: 0 0 0 2px var(--ux-primary-light, rgba(59, 130, 246, 0.1));
}

.ux-block-editor__image-wrapper {
    text-align: center;
}

.ux-block-editor__image-preview {
    max-width: 100%;
    max-height: 20rem;
    border-radius: var(--ux-radius-sm, 0.25rem);
    margin-bottom: 0.5rem;
}

.ux-block-editor__divider {
    border: none;
    border-top: 2px solid var(--ux-border-color, #d1d5db);
    margin: 1rem 0;
}

.ux-block-editor__heading-level {
    display: inline-block;
    padding: 0.125rem 0.25rem;
    margin-bottom: 0.25rem;
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-size: 0.75rem;
    outline: none;
    background: var(--ux-bg-primary, #fff);
}

.ux-block-editor__list-toggle {
    margin-bottom: 0.375rem;
    font-size: 0.8125rem;
    color: var(--ux-text-secondary, #6b7280);
}

.ux-block-editor__list-toggle label {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
}

.ux-block-editor__list-items {
    padding-left: 1rem;
}

.ux-block-editor__list-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    margin-bottom: 0.25rem;
}

.ux-block-editor__list-item input {
    flex: 1;
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-size: 0.8125rem;
    outline: none;
}

.ux-block-editor__list-item input:focus {
    border-color: var(--ux-primary, #3b82f6);
}

.ux-block-editor__list-item-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    padding: 0;
    border: none;
    border-radius: 0.125rem;
    background: transparent;
    color: var(--ux-text-muted, #9ca3af);
    cursor: pointer;
    font-size: 0.625rem;
    transition: all 0.15s ease;
}

.ux-block-editor__list-item-remove:hover {
    background: #fee2e2;
    color: #dc2626;
}

.ux-block-editor__list-add {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    margin-top: 0.25rem;
    border: 1px dashed var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius-sm, 0.25rem);
    background: transparent;
    color: var(--ux-text-muted, #9ca3af);
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ux-block-editor__list-add:hover {
    border-color: var(--ux-primary, #3b82f6);
    color: var(--ux-primary, #3b82f6);
    background: var(--ux-primary-light, rgba(59, 130, 246, 0.05));
}

.ux-block-editor__field {
    margin-bottom: 0.5rem;
}

.ux-block-editor__field:last-child {
    margin-bottom: 0;
}

.ux-block-editor__field-label {
    display: block;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--ux-text-muted, #9ca3af);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.ux-block-editor__block-unknown {
    padding: 0.75rem;
    background: #fef2f2;
    color: #dc2626;
    border-radius: var(--ux-radius-sm, 0.25rem);
    font-size: 0.8125rem;
}

/* Inserter */
.ux-block-editor__inserter {
    position: relative;
    border-top: 1px solid var(--ux-border-color, #d1d5db);
}

.ux-block-editor__inserter-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.5rem;
    border: none;
    background: var(--ux-bg-secondary, #f3f4f6);
    color: var(--ux-text-secondary, #6b7280);
    cursor: pointer;
    transition: all 0.15s ease;
}

.ux-block-editor__inserter-toggle:hover {
    background: var(--ux-bg-hover, #e5e7eb);
    color: var(--ux-primary, #3b82f6);
}

.ux-block-editor__inserter-panel {
    display: none;
    position: absolute;
    bottom: 100%;
    left: 0;
    right: 0;
    background: var(--ux-bg-primary, #fff);
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius, 0.375rem) var(--ux-radius, 0.375rem) 0 0;
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
    max-height: 20rem;
    overflow-y: auto;
    z-index: 50;
}

.ux-block-editor__inserter-panel--open {
    display: block;
}

.ux-block-editor__inserter-section {
    padding: 0.75rem;
}

.ux-block-editor__inserter-section + .ux-block-editor__inserter-section {
    border-top: 1px solid var(--ux-border-color, #d1d5db);
}

.ux-block-editor__inserter-section-title {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--ux-text-muted, #9ca3af);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.ux-block-editor__inserter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr));
    gap: 0.375rem;
}

.ux-block-editor__inserter-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    border: 1px solid var(--ux-border-color, #d1d5db);
    border-radius: var(--ux-radius-sm, 0.25rem);
    background: var(--ux-bg-primary, #fff);
    cursor: pointer;
    transition: all 0.15s ease;
}

.ux-block-editor__inserter-item:hover {
    border-color: var(--ux-primary, #3b82f6);
    background: var(--ux-primary-light, rgba(59, 130, 246, 0.05));
}

.ux-block-editor__inserter-item-icon {
    font-size: 1.25rem;
    line-height: 1;
}

.ux-block-editor__inserter-item-label {
    font-size: 0.6875rem;
    color: var(--ux-text-secondary, #6b7280);
    text-align: center;
}

@media (max-width: 640px) {
    .ux-block-editor__canvas {
        padding: 0.5rem;
    }

    .ux-block-editor__inserter-grid {
        grid-template-columns: repeat(auto-fill, minmax(4.5rem, 1fr));
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
