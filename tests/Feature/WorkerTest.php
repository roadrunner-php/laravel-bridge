<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Feature;

use Illuminate\Support\Str;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @coversNothing
 */
class WorkerTest extends \Spiral\RoadRunnerLaravel\Tests\AbstractTestCase
{
    /**
     * Path to the RoadRunner binary file.
     */
    private const RR_BIN_PATH = 'rr';

    /**
     * Relay types.
     */
    private const
        RELAY_TYPE_PIPES = 'pipes',
        RELAY_TYPE_SOCKET = 'socket',
        RELAY_TYPE_TCP_PORT = 'tcp_port';

    /**
     * Asserts that RoadRunner binary file is available on current system.
     *
     * @return void
     */
    protected function assertRoadRunnerBinaryExists(): void
    {
        $process = new Process([self::RR_BIN_PATH, '--help']);

        $this->assertSame(0, $process->run(), 'RoadRunner binary file was not found: ' . $process->getOutput());

        $this->assertStringContainsString("serve", $process->getOutput());
        $this->assertStringContainsString("RoadRunner", $process->getOutput());
    }

    /**
     * Creates RoadRunner config file with pre-defined options. HTTP port is always 22622.
     *
     * @param string $where Path to the directory, where config file must be created.
     * @param string $relay_type
     *
     * @return string Path to the created config file.
     */
    protected function createConfigFile(string $where, string $relay_type): string
    {
        $random_hash      = Str::lower(Str::random(6));
        $worker_path      = \realpath(__DIR__ . '/../../bin/rr-worker');
        $static_dir       = \realpath(__DIR__ . '/../../vendor/laravel/laravel/public');
        $config_file_path = $where . DIRECTORY_SEPARATOR . $random_hash . '.yaml';
        $app_key          = env("APP_KEY", "base64:+hY09mlBag/d7Qhq2SjE/i2iUzZBS1dGObLqcHZU2Ac=");

        switch ($relay_type) {
            case self::RELAY_TYPE_SOCKET:
                $relay_dsn = 'unix://' . $where . DIRECTORY_SEPARATOR . $random_hash . '.sock';
                break;

            case self::RELAY_TYPE_PIPES:
                $relay_dsn = 'pipes';
                break;

            case self::RELAY_TYPE_TCP_PORT:
                $relay_dsn = 'tcp://localhost:6001';
                break;

            default:
                throw new \InvalidArgumentException("Unsupported relay type: " . $relay_type);
        }

        $this->assertFileExists($worker_path);

        $content /** @lang yaml */ = <<<EOF
server:
  command: "php {$worker_path} start --relay-dsn {$relay_dsn}"
  relay: "{$relay_dsn}"
  env:
    - APP_KEY: "{$app_key}"
  relay_timeout: 10s

http:
  address: 0.0.0.0:22622
  middleware: ["headers", "static", "gzip"]
  pool:
    num_workers: 2
    max_jobs: 4
    supervisor:
      exec_ttl: 5s
  headers:
    response:
      X-Powered-By: "RoadRunner"
  static:
    dir: "{$static_dir}"
    forbid: [".php"]

endure:
  log_level: warning # do NOT comment this lines - test will fails otherwise O_o
EOF;

        $wrote = (new Filesystem())->put($config_file_path, $content, true);

        $this->assertIsInt($wrote);
        $this->assertSame(Str::length($content), $wrote);

        return $config_file_path;
    }

    /**
     * Wait until server is started.
     *
     * @param Guzzle $guzzle
     * @param int    $limit
     */
    protected function waitUntilServerIsStarted(Guzzle $guzzle, int $limit = 100): void
    {
        for ($i = 0; $i < $limit; $i++) {
            try {
                $guzzle->send(new Request('HEAD', '/'));

                return;
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                \usleep(30_000);
            }
        }

        $this->fail("Server was not started");
    }

    /**
     * @return void
     */
    public function testServerStartingUsingDifferentRelayTypes(): void
    {
        $this->assertRoadRunnerBinaryExists();

        foreach ([self::RELAY_TYPE_SOCKET, self::RELAY_TYPE_PIPES, self::RELAY_TYPE_TCP_PORT] as $relay_type) {
            $rr_proc     = new Process([self::RR_BIN_PATH, 'serve', '-c', $this->createConfigFile(
                $this->createTemporaryDirectory(),
                $relay_type
            )]);
            $http_client = new \GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:22622']);

            try {
                // https://symfony.com/doc/current/components/process.html#running-processes-asynchronously
                $rr_proc->start();

                $this->waitUntilServerIsStarted($http_client);

                $response = $http_client->send(new Request('GET', '/'));
                $this->assertSame(200, $response->getStatusCode());
                $this->assertSame("RoadRunner", $response->getHeaderLine("X-Powered-By"));
                $this->assertStringContainsString("Laravel", $body = (string) $response->getBody());
                $this->assertStringContainsString("https://laravel.com/", $body);

                $response = $http_client->send(new Request('GET', '/robots.txt'));
                $this->assertSame(200, $response->getStatusCode());
                $this->assertStringContainsString("User-agent", (string) $response->getBody());
            } finally {
                $this->assertSame(0, $rr_proc->stop());
            }
        }
    }
}
