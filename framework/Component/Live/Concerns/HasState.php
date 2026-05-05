<?php

declare(strict_types=1);

namespace Framework\Component\Live\Concerns;

use Framework\Component\Live\Attribute\Session as SessionAttribute;
use Framework\Component\Live\Attribute\Cookie as CookieAttribute;
use Framework\Component\Live\Attribute\Persistent as PersistentAttribute;
use Framework\Component\Live\Persistent\PersistentStateManager;
use Framework\Http\Session\Session;

/**
 * @mixin \Framework\Component\Live\LiveComponent
 */
trait HasState
{
    private ?string $stateChecksum = null;

    /**
     * 序列化组件状态为带签名的安全字符串
     */
    public function serializeState(): string
    {
        $data = [];
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties() as $prop) {
            if ($prop->isStatic()) continue;

            $internalProps = ['componentId', 'operations', 'refreshFragments', 'manualUpdates', 'actionCache', 'liveActions', 'mountCalled', 'stateChecksum', 'propValues', 'routeParams', 'validationErrors', 'lockedChecksums', 'computedCache'];
            if (in_array($prop->getName(), $internalProps)) continue;

            if ($prop->isPublic()) continue;

            $value = $prop->getValue($this);

            if ($this->isSerializable($value)) {
                $data[$prop->getName()] = $value;
            }
        }

        $publicData = $this->getPublicProperties();
        $data['__checksum'] = $this->generateDataChecksum($publicData);

        $editableProps = $this->frontendEditableProperties();
        $lockedChecksums = [];
        foreach ($publicData as $propName => $value) {
            if (!in_array($propName, $editableProps, true)) {
                $lockedChecksums[$propName] = $this->generateDataChecksum([$propName => $value]);
            }
        }
        if (!empty($lockedChecksums)) {
            $data['__locked_checksums'] = $lockedChecksums;
        }

        $serialized = serialize($data);
        $compressed = function_exists('gzcompress') ? gzcompress($serialized) : $serialized;

        $sig = hash_hmac('sha256', static::class . 'state' . $compressed, $this->liveSigningKey(), true);

        return base64_encode($sig . $compressed);
    }

    /**
     * 反序列化并恢复组件状态
     */
    public function deserializeState(string $state): void
    {
        $decoded = base64_decode($state, true);
        if (!$decoded || strlen($decoded) < 32) return;

        $sig = substr($decoded, 0, 32);
        $compressed = substr($decoded, 32);

        $expectedSig = hash_hmac('sha256', static::class . 'state' . $compressed, $this->liveSigningKey(), true);
        if ($sig !== $expectedSig) {
            throw new \RuntimeException('Live component state signature verification failed. Possible tampering detected.');
        }

        $serialized = function_exists('gzuncompress') ? gzuncompress($compressed) : $compressed;
        $data = unserialize($serialized);

        if ($data === false && $serialized !== 'b:0;') {
            throw new \RuntimeException('Live component state deserialization failed.');
        }

        if (isset($data['__checksum'])) {
            $this->stateChecksum = $data['__checksum'];
            unset($data['__checksum']);
        }

        if (isset($data['__locked_checksums'])) {
            $this->lockedChecksums = $data['__locked_checksums'];
            unset($data['__locked_checksums']);
        }

        // 恢复非公开属性
        foreach ($data as $key => $value) {
            $prop = new \ReflectionProperty($this, $key);
            if (!$prop->isStatic() && !$prop->isPublic()) {
                $prop->setValue($this, $value);
            }
        }

        // 恢复公开属性：Session / Cookie / Persistent 驱动
        $this->restoreDrivenProperties();

        // 恢复 _raw 原始数据（前端提交的公开属性值）
        if (isset($data['_raw']) && is_array($data['_raw'])) {
            foreach ($data['_raw'] as $name => $value) {
                $ref = new \ReflectionProperty($this, $name);
                if (!$ref->isStatic() && $ref->isPublic()) {
                    $ref->setValue($this, $value);
                }
            }
        }

        $this->hydrate();
    }

    /**
     * 恢复 Session/Cookie/Persistent 驱动的公开属性
     */
    private function restoreDrivenProperties(): void
    {
        foreach ($this->allowedStateProperties() as $propName) {
            if (!property_exists($this, $propName)) {
                continue;
            }
            $ref = new \ReflectionProperty($this, $propName);
            if ($ref->isStatic()) {
                continue;
            }

            $sessionAttrs = $ref->getAttributes(SessionAttribute::class);
            $cookieAttrs = $ref->getAttributes(CookieAttribute::class);
            $persistentAttrs = $ref->getAttributes(PersistentAttribute::class);

            if (!empty($sessionAttrs)) {
                $session = new Session();
                $sessionKey = 'live_component_' . static::class . '_' . $propName;
                $stored = $session->get($sessionKey);
                if ($stored !== null) {
                    $ref->setValue($this, $stored['value']);
                } else {
                    $value = $ref->getValue($this);
                    $session->set($sessionKey, [
                        'value' => $value,
                        'time' => time(),
                    ]);
                }
            } elseif (!empty($cookieAttrs)) {
                $cookieName = 'live_component_' . static::class . '_' . $propName;
                if (isset($_COOKIE[$cookieName])) {
                    $value = json_decode($_COOKIE[$cookieName], true);
                    $ref->setValue($this, $value);
                } else {
                    $value = $ref->getValue($this);
                    $attr = $cookieAttrs[0]->newInstance();
                    $expire = time() + ($attr->minutes * 60);
                    setcookie($cookieName, json_encode($value), $expire, '/');
                }
            } elseif (!empty($persistentAttrs)) {
                PersistentStateManager::restorePersistentProperty($this, $propName);
            }
        }
    }

    private function generateDataChecksum(array $data): string
    {
        $this->recursiveNormalize($data);
        return md5(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function recursiveNormalize(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveNormalize($value);
            } else {
                if ($value !== null) {
                    $value = (string)$value;
                }
            }
        }
    }

    private function isSerializable(mixed $value): bool
    {
        if (is_scalar($value) || is_array($value) || is_null($value)) return true;
        if ($value instanceof \UnitEnum) return true;
        if ($value instanceof \DateTimeInterface) return true;
        return false;
    }

    protected function liveSigningKey(): string
    {
        $sessionToken = app()->make(Session::class)->getId() ?: 'guest';
        $appKey = config('app.key', 'default-key');
        return hash_hmac('sha256', $sessionToken, $appKey);
    }

    /**
     * 获取公开属性驱动元信息（Session/Cookie）
     */
    protected function getStateMeta(): array
    {
        $ref = new \ReflectionClass($this);
        $meta = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $sessionAttrs = $prop->getAttributes(SessionAttribute::class);
            if (!empty($sessionAttrs)) {
                $attr = $sessionAttrs[0]->newInstance();
                $meta[$prop->getName()] = [
                    'driver' => 'session',
                    'key' => $attr->key ?? $prop->getName(),
                ];
            }

            $cookieAttrs = $prop->getAttributes(CookieAttribute::class);
            if (!empty($cookieAttrs)) {
                $attr = $cookieAttrs[0]->newInstance();
                $meta[$prop->getName()] = [
                    'driver' => 'cookie',
                    'key' => $attr->key ?? $prop->getName(),
                    'minutes' => $attr->minutes,
                ];
            }
        }

        return $meta;
    }
}

