[![PHP Version Require](http://poser.pugx.org/waffle-commons/log/require/php)](https://packagist.org/packages/waffle-commons/log)
[![PHP CI](https://github.com/waffle-commons/log/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/log/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/log/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/log)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/log/v)](https://packagist.org/packages/waffle-commons/log)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/log/v/unstable)](https://packagist.org/packages/waffle-commons/log)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/log.svg)](https://packagist.org/packages/waffle-commons/log)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/log)](https://github.com/waffle-commons/log/blob/main/LICENSE.md)

Waffle Log Component
====================

> **Release:** `v0.1.0-beta2` &nbsp;|&nbsp; [`CHANGELOG.md`](./CHANGELOG.md)
> **PSR Compliance:** PSR-3 (`Psr\Log\AbstractLogger`)

A strict, container-native logger that emits one JSON line per record onto a stream. Designed for Docker/Kubernetes deployments where `stdout`/`stderr` are the log sinks — no buffering, no per-process state, safe across FrankenPHP worker requests.

## 📦 Installation

```bash
composer require waffle-commons/log
```

## 🧱 Surface

| Class | Role |
| :--- | :--- |
| `Waffle\Commons\Log\StreamLogger` | PSR-3 `AbstractLogger` writing JSON-formatted records to any PHP stream (`php://stderr`, `php://stdout`, file paths). |
| `Waffle\Commons\Log\Channel\LogChannel` | Typed-constant container for canonical log-channel names (`APP`, `CORE`, `HTTP`, `SECURITY`, `AUDIT`). |

## 🚀 Usage

`StreamLogger` exposes its configuration via PHP 8.5 **asymmetric visibility** (`public private(set)`) and `readonly` promoted properties:

```php
use Waffle\Commons\Log\StreamLogger;
use Waffle\Commons\Log\Channel\LogChannel;

$logger = new StreamLogger(
    streamPath: 'php://stderr',
    channel: LogChannel::HTTP,
    permissions: 0o644,
);

$logger->info('Request handled', ['route' => '/users', 'status' => 200]);
```

The signature, verbatim from `src/StreamLogger.php`:

```php
public function __construct(
    private(set) readonly string $streamPath = 'php://stderr',
    private(set) readonly string $channel = LogChannel::APP,
    private(set) readonly int $permissions = 0o644,
) { /* opens the stream, chmods if it's a regular file */ }
```

## 🪵 Output format

Every record is a single JSON object emitted with a trailing `\n`, suitable for Docker's JSON-file driver and for `jq`/`grep` pipelines:

```json
{"time":"2026-05-16T10:42:01.123+00:00","level":"info","channel":"http","message":"Request handled","context":{"route":"/users","status":200}}
```

RFC 5424 / Monolog level constants (DEBUG=100 … EMERGENCY=600) are interpolated from `Psr\Log\LogLevel` — no custom level vocabulary.

## 🧷 Channels

`LogChannel` is a typed-constant container (intentionally not a backed enum, so it can be used as a property default):

```php
final class LogChannel
{
    public const string APP = 'app';
    public const string CORE = 'core';
    public const string HTTP = 'http';
    public const string SECURITY = 'sec';
    public const string AUDIT = 'audit';
}
```

## 🛡️ Worker-mode safety

- Stream is opened in the constructor and closed in `__destruct()`, preventing file-descriptor leaks across long-running workers.
- No per-call buffering; each `log()` call writes immediately.
- No static state. Multiple `StreamLogger` instances can co-exist (one per channel, for example).

## 🧭 Architectural boundary (`mago guard`)

An active dependency **perimeter** is enforced on every CI run by `vendor/bin/mago guard` (bundled into `composer mago`; zero baselines). The rules live in [`mago.toml`](./mago.toml) under `[guard.perimeter]` — a forbidden `use` statement fails the build, not a reviewer.

Production code under `Waffle\Commons\Log` may depend **only** on:

- `Waffle\Commons\Log\**` — itself
- `Waffle\Commons\Contracts\**` — the shared contracts package, the **only** Waffle dependency permitted
- `Psr\**` — PSR interfaces (PSR-3)
- `@global` + `Psl\**` — PHP core and the PHP Standard Library

Test code under `WaffleTests\Commons\Log` is unrestricted (`@all`). Structural rules are guarded too: interfaces must be named `*Interface`, `Exception\**` classes must end in `*Exception`, and any `Enum\**` namespace may hold only `enum` declarations.

Contract-first, component-agnostic by construction: components compose through `waffle-commons/contracts`, never directly through one another.

## 🧪 Testing

```bash
docker exec -w /waffle-commons/log waffle-dev composer tests
```

## 📄 License

MIT — see [LICENSE.md](./LICENSE.md).
