<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests\Support;

use RuntimeException;

/**
 * A minimal, real (loopback) HTTP server for wire-level integration tests -- not a mocking
 * library. Mirrors the Java/Python/C# SDKs' use of a real socket server: requests travel
 * over a real socket so header/query/multipart-boundary correctness is actually exercised,
 * not just mock call arguments.
 *
 * Runs PHP's built-in web server (`php -S`) as a genuine child process, since PHP has no
 * in-process background HTTP server primitive comparable to Python's threading.HTTPServer.
 * Routes and recorded requests are exchanged with that child process via two small JSON
 * files (see router.php) rather than shared closures/memory.
 */
final class LoopbackServer
{
    /** @var resource */
    private $process;
    private readonly string $scriptFile;
    private readonly string $logFile;
    public readonly string $baseUrl;

    public function __construct()
    {
        $port = self::findFreePort();
        $this->baseUrl = "http://127.0.0.1:{$port}";
        $this->scriptFile = (string) tempnam(sys_get_temp_dir(), 'xingen-script-');
        $this->logFile = (string) tempnam(sys_get_temp_dir(), 'xingen-log-');
        file_put_contents($this->scriptFile, '{}');
        file_put_contents($this->logFile, '');

        $router = __DIR__ . '/router.php';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];
        $env = array_merge(getenv(), [
            'LOOPBACK_SCRIPT_FILE' => $this->scriptFile,
            'LOOPBACK_LOG_FILE' => $this->logFile,
        ]);

        // enable_post_data_reading=0 stops PHP from parsing multipart/form-data bodies into
        // $_POST/$_FILES and draining php://input in the process -- router.php needs the raw
        // bytes (boundary, Content-Disposition, etc.) to let multipart-encoding tests inspect
        // them, which php://input is otherwise emptied of for multipart requests specifically.
        $command = [PHP_BINARY, '-d', 'enable_post_data_reading=0', '-S', "127.0.0.1:{$port}", $router];
        $process = proc_open($command, $descriptors, $pipes, null, $env);
        if ($process === false) {
            throw new RuntimeException('Failed to start php -S loopback server');
        }
        $this->process = $process;

        $this->waitUntilReady($port);
    }

    /** Registers a single scripted response for a path, applied regardless of HTTP method. */
    public function route(string $path, int $status, string $body, ?array $headers = null): void
    {
        $this->writeRoute($path, ['status' => $status, 'body' => base64_encode($body), 'headers' => $headers]);
    }

    /** Registers a queue of scripted responses for a path, consumed one per request (the
     * last entry repeats once exhausted) -- used by polling tests that need the same path
     * to answer differently across successive calls.
     * @param list<array{status: int, body: string, headers?: array<string, string>}> $responses */
    public function routeSequence(string $path, array $responses): void
    {
        $encoded = array_map(
            static fn (array $r): array => [
                'status' => $r['status'],
                'body' => base64_encode($r['body']),
                'headers' => $r['headers'] ?? null,
            ],
            $responses,
        );
        $this->writeRoute($path, $encoded);
    }

    /** @param array<string, mixed>|list<array<string, mixed>> $entry */
    private function writeRoute(string $path, array $entry): void
    {
        $raw = file_get_contents($this->scriptFile);
        $script = ($raw !== false && $raw !== '') ? (json_decode($raw, true) ?? []) : [];
        $script[$path] = $entry;
        file_put_contents($this->scriptFile, json_encode($script), LOCK_EX);
    }

    /** Every request the server has observed so far, in order.
     * @return list<array{method: string, path: string, query: string, headers: array<string, string>, body: string}> */
    public function recordedRequests(): array
    {
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $requests = [];
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!is_array($entry)) {
                continue;
            }
            $entry['body'] = base64_decode((string) ($entry['body'] ?? ''));
            $requests[] = $entry;
        }

        return $requests;
    }

    /** @return list<array{method: string, path: string, query: string, headers: array<string, string>, body: string}> */
    public function recordedRequestsFor(string $path): array
    {
        return array_values(array_filter(
            $this->recordedRequests(),
            static fn (array $r): bool => $r['path'] === $path,
        ));
    }

    public function stop(): void
    {
        proc_terminate($this->process);
        proc_close($this->process);
        @unlink($this->scriptFile);
        @unlink($this->logFile);
    }

    private function waitUntilReady(int $port): void
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.05);
            if (is_resource($connection)) {
                fclose($connection);

                return;
            }
            usleep(20_000);
        }
        throw new RuntimeException('Loopback server did not become ready in time');
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new RuntimeException("Could not allocate a free port: {$errstr}");
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }
}
