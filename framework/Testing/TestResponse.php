<?php

declare(strict_types=1);

namespace Framework\Testing;

use Framework\Http\Response\Response;

class TestResponse
{
    protected Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getOriginalResponse(): Response
    {
        return $this->response;
    }

    public function getContent(): string
    {
        return (string) $this->response->getContent();
    }

    public function getStatus(): int
    {
        return $this->response->getStatusCode();
    }

    public function assertStatus(int $status): self
    {
        \PHPUnit\Framework\Assert::assertEquals(
            $status,
            $this->getStatus(),
            "Expected status code {$status}, got {$this->getStatus()}"
        );
        return $this;
    }

    public function assertSuccessful(): self
    {
        $actual = $this->getStatus();
        \PHPUnit\Framework\Assert::assertTrue(
            $actual >= 200 && $actual < 300,
            "Expected successful status code (2xx), got {$actual}"
        );
        return $this;
    }

    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }

    public function assertForbidden(): self
    {
        return $this->assertStatus(403);
    }

    public function assertUnauthorized(): self
    {
        return $this->assertStatus(401);
    }

    public function assertRedirect(string $uri = null): self
    {
        $status = $this->getStatus();
        \PHPUnit\Framework\Assert::assertTrue(
            $status >= 300 && $status < 400,
            "Expected redirect status code (3xx), got {$status}"
        );

        if ($uri !== null) {
            \PHPUnit\Framework\Assert::assertSame(
                $uri,
                $this->response->getHeader('Location', ''),
                "Expected redirect to '{$uri}'"
            );
        }
        return $this;
    }

    public function assertSee(string $value): self
    {
        \PHPUnit\Framework\Assert::assertStringContainsString(
            $value,
            $this->getContent(),
            "Failed asserting that response contains '{$value}'"
        );
        return $this;
    }

    public function assertDontSee(string $value): self
    {
        \PHPUnit\Framework\Assert::assertStringNotContainsString(
            $value,
            $this->getContent(),
            "Failed asserting that response does NOT contain '{$value}'"
        );
        return $this;
    }

    public function assertSeeText(string $text): self
    {
        $content = strip_tags($this->getContent());
        \PHPUnit\Framework\Assert::assertStringContainsString(
            $text,
            $content,
            "Failed asserting that response text contains '{$text}'"
        );
        return $this;
    }

    public function assertSeeInOrder(array $values): self
    {
        $content = $this->getContent();
        $position = 0;
        foreach ($values as $value) {
            $pos = strpos($content, $value, $position);
            \PHPUnit\Framework\Assert::assertNotFalse(
                $pos,
                "Failed asserting that response contains '{$value}' in correct order"
            );
            $position = $pos + strlen($value);
        }
        return $this;
    }

    public function assertJson(array $data): self
    {
        $actual = json_decode($this->getContent(), true, 512, JSON_THROW_ON_ERROR);
        \PHPUnit\Framework\Assert::assertEquals(
            $data,
            $actual,
            'JSON response does not match expected data'
        );
        return $this;
    }

    public function assertJsonStructure(array $structure): self
    {
        $data = json_decode($this->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStructureMatches($structure, $data);
        return $this;
    }

    public function assertJsonFragment(array $fragment): self
    {
        $data = json_decode($this->getContent(), true, 512, JSON_THROW_ON_ERROR);
        \PHPUnit\Framework\Assert::assertTrue(
            $this->arrayHasSubsetRecursive($fragment, $data),
            'JSON response does not contain expected fragment'
        );
        return $this;
    }

    public function assertHeader(string $name, string $value = null): self
    {
        $actual = $this->response->getHeader($name, '');

        if ($value === null) {
            \PHPUnit\Framework\Assert::assertNotEmpty(
                $this->response->getHeader($name, ''),
                "Expected header '{$name}' to be present"
            );
        } else {
            \PHPUnit\Framework\Assert::assertSame(
                $value,
                $this->response->getHeader($name, ''),
                "Expected header '{$name}' to have value '{$value}', got '{$actual}'"
            );
        }
        return $this;
    }

    public function assertCookie(string $name, string $value = null): self
    {
        $setCookieHeader = $this->response->getHeader('Set-Cookie', '');
        $found = str_contains($setCookieHeader, "{$name}=");

        \PHPUnit\Framework\Assert::assertTrue($found, "Cookie '{$name}' not present in response");

        if ($value !== null && $found) {
            \PHPUnit\Framework\Assert::assertStringContainsString(
                "={$value}",
                $setCookieHeader,
                "Cookie '{$name}' has unexpected value"
            );
        }
        return $this;
    }

    private function assertStructureMatches(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            if (is_numeric($key)) {
                // Array item - check at least one matches
                $found = false;
                foreach ($actual as $item) {
                    if (is_array($item) && is_array($value)) {
                        try {
                            $this->assertStructureMatches($value, $item);
                            $found = true;
                            break;
                        } catch (\PHPUnit\Framework\AssertionFailedError) {
                            continue;
                        }
                    }
                }
                \PHPUnit\Framework\Assert::assertTrue($found, "JSON structure missing required array item pattern");
            } else {
                \PHPUnit\Framework\Assert::assertArrayHasKey($key, $actual, "JSON missing key: {$key}");
                if (is_array($value)) {
                    $this->assertStructureMatches($value, $actual[$key]);
                }
            }
        }
    }

    private function arrayHasSubsetRecursive(array $subset, array $array): bool
    {
        foreach ($subset as $key => $value) {
            if (!isset($array[$key])) {
                return false;
            }
            if (is_array($value) && !$this->arrayHasSubsetRecursive($value, $array[$key])) {
                return false;
            }
            if (!is_array($value) && $array[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
}
