<?php

declare(strict_types=1);

namespace Framework\Component\Live\Concerns;

use Framework\Component\Live\Attribute\Locked;
use Framework\Component\Live\Attribute\Prop;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\Attribute\Session as SessionAttribute;
use Framework\Component\Live\Attribute\Cookie as CookieAttribute;
use Framework\Component\Live\Attribute\Persistent as PersistentAttribute;

/**
 * @mixin \Framework\Component\Live\LiveComponent
 */
trait HasProperties
{
    protected array $routeParams = [];
    protected array $propValues = [];
    private array $lockedChecksums = [];

    /**
     * 注入 Props（从父组件传值或路由参数）
     */
    private function injectProps(): void
    {
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $propName = $prop->getName();
            $attrs = $prop->getAttributes(Prop::class);

            if (empty($attrs)) {
                continue;
            }

            $attr = $attrs[0]->newInstance();

            // 优先级：make()传入 > routeParams(fromRoute) > default
            $value = null;
            $found = false;

            if (isset($this->propValues[$propName])) {
                $value = $this->propValues[$propName];
                $found = true;
            } elseif ($attr->fromRoute !== null && isset($this->routeParams[$attr->fromRoute])) {
                $value = $this->routeParams[$attr->fromRoute];
                $found = true;
            } elseif ($attr->fromRoute !== null && isset($this->routeParams[$propName])) {
                $value = $this->routeParams[$propName];
                $found = true;
            } elseif ($attr->default !== null) {
                $value = $attr->default;
                $found = true;
            }

            if ($found) {
                $prop->setValue($this, $value);
            } elseif ($attr->required) {
                throw new \RuntimeException("Prop [{$propName}] is required but not provided for " . static::class);
            }
        }
    }

    /**
     * 获取所有被标记为公开状态的属性
     */
    public function getPublicProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $data = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $stateAttrs = $prop->getAttributes(State::class);
            $propAttrs = $prop->getAttributes(Prop::class);
            $sessionAttrs = $prop->getAttributes(SessionAttribute::class);
            $cookieAttrs = $prop->getAttributes(CookieAttribute::class);
            $persistentAttrs = $prop->getAttributes(PersistentAttribute::class);

            if (empty($stateAttrs) && empty($propAttrs) && empty($sessionAttrs) && empty($cookieAttrs) && empty($persistentAttrs)) {
                continue;
            }

            $name = $prop->getName();
            $value = $prop->getValue($this);

            if (is_resource($value)) {
                continue;
            }

            $data[$name] = $value;
        }

        return $data;
    }

    /**
     * 获取所有允许作为状态的公开属性名
     */
    protected function allowedStateProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $props = [];
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            $stateAttrs = $prop->getAttributes(State::class);
            $propAttrs = $prop->getAttributes(Prop::class);
            $sessionAttrs = $prop->getAttributes(SessionAttribute::class);
            $cookieAttrs = $prop->getAttributes(CookieAttribute::class);
            $persistentAttrs = $prop->getAttributes(PersistentAttribute::class);
            if (!empty($stateAttrs) || !empty($propAttrs) || !empty($sessionAttrs) || !empty($cookieAttrs) || !empty($persistentAttrs)) {
                $props[] = $prop->getName();
            }
        }
        return $props;
    }

    /**
     * 获取前端可编辑的属性列表
     *
     * A property is frontend-editable if it is:
     * 1. Not annotated with #[Locked]
     * 2. Either annotated with #[State(frontendEditable: true)] (the default)
     *    OR is a #[Prop] / #[Session] / #[Cookie] / #[Persistent] that is
     *    explicitly marked as frontendEditable via #[State]
     *
     * #[Locked] takes precedence over any other annotation.
     */
    protected function frontendEditableProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $props = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            // #[Locked] overrides everything — property is immutable
            $lockedAttrs = $prop->getAttributes(Locked::class);
            if (!empty($lockedAttrs)) {
                continue;
            }

            $attrs = $prop->getAttributes(State::class);
            if (!empty($attrs)) {
                $attr = $attrs[0]->newInstance();
                if ($attr->frontendEditable) {
                    $props[] = $prop->getName();
                }
            }
        }

        return $props;
    }

    /**
     * 获取被 #[Locked] 标记的属性名列表
     */
    protected function getLockedProperties(): array
    {
        $ref = new \ReflectionClass($this);
        $props = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $lockedAttrs = $prop->getAttributes(Locked::class);
            if (!empty($lockedAttrs)) {
                $props[] = $prop->getName();
            }
        }

        return $props;
    }

    /**
     * 从前端数据填充公开属性，含 checksum 校验和 #[Locked] 检查
     */
    public function fillPublicProperties(array $data): void
    {
        if (isset($data['_raw']) && is_array($data['_raw'])) {
            $data = $data['_raw'];
        }

        $ref = new \ReflectionClass($this);
        $publicProps = $this->allowedStateProperties();
        $editableProps = $this->frontendEditableProperties();
        $lockedProps = $this->getLockedProperties();

        $cleanedData = [];
        foreach ($publicProps as $propName) {
            if (array_key_exists($propName, $data)) {
                // Reject if property is #[Locked] — immutable from frontend
                if (in_array($propName, $lockedProps, true)) {
                    if (\Framework\Foundation\Application::isDebug()) {
                        error_log("Rejected attempt to modify locked property [{$propName}] on " . static::class);
                    }
                    continue;
                }
                $cleanedData[$propName] = $data[$propName];
            }
        }

        // 分级校验：#[State(frontendEditable:true)] 允许前端直接修改，其他属性严格 checksum 校验
        if (!empty($this->lockedChecksums)) {
            foreach ($cleanedData as $propName => $value) {
                if (!in_array($propName, $editableProps, true)) {
                    if (isset($this->lockedChecksums[$propName])) {
                        $currentChecksum = $this->generateDataChecksum([$propName => $value]);
                        if (!hash_equals($this->lockedChecksums[$propName], $currentChecksum)) {
                            if (\Framework\Foundation\Application::isDebug()) {
                                error_log("Checksum mismatch for property [{$propName}].");
                            }
                            throw new \RuntimeException('Live public state integrity check failed. Data tampering detected.');
                        }
                    }
                }
            }
        }

        foreach ($cleanedData as $name => $value) {
            if (in_array($name, $publicProps, true)) {
                $prop = $ref->getProperty($name);
                if (!$prop->isStatic()) {
                    $prop->setValue($this, $this->castParam($value, $prop->getType()));
                }
            }
        }
    }

    /**
     * 获取组件所有公开属性的当前状态
     */
    public function getComponentState(): array
    {
        return $this->getPublicProperties();
    }

    /**
     * 获取前端数据
     */
    public function getDataForFrontend(): array
    {
        return $this->getPublicProperties();
    }

    protected function persistProperties(): void
    {
        $this->persistPropertiesInternal();
    }

    public function __updateProperty(array $params): void
    {
        $property = $params['property'] ?? null;
        $value = $params['value'] ?? null;

        if ($property === null) return;

        $editableProps = $this->frontendEditableProperties();
        $publicProps = $this->allowedStateProperties();
        $lockedProps = $this->getLockedProperties();

        if (!in_array($property, $publicProps, true)) return;

        // #[Locked] properties are immutable from the frontend
        if (in_array($property, $lockedProps, true)) {
            if (\Framework\Foundation\Application::isDebug()) {
                error_log("Rejected attempt to modify locked property [{$property}] on " . static::class);
            }
            return;
        }

        if (!empty($this->lockedChecksums) && !in_array($property, $editableProps, true)) {
            return;
        }

        $ref = new \ReflectionClass($this);
        if ($ref->hasProperty($property)) {
            $prop = $ref->getProperty($property);
            if (!$prop->isStatic()) {
                $prop->setValue($this, $this->castParam($value, $prop->getType()));
            }
        }
    }

    protected function persistPropertiesInternal(): void
    {
        $ref = new \ReflectionClass($this);
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            $persistentAttrs = $prop->getAttributes(PersistentAttribute::class);
            if (!empty($persistentAttrs)) {
                \Framework\Component\Live\Persistent\PersistentStateManager::syncPersistentProperty($this, $prop->getName());
            }
        }
    }
}