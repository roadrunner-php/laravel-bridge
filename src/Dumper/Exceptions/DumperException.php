<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Dumper\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Spiral\RoadRunnerLaravel\Dumper\Stack\StackInterface;

final class DumperException extends \RuntimeException
{
    /**
     * @var HtmlDumper
     */
    protected AbstractDumper $renderer;

    /**
     * @var StackInterface $stack
     */
    private StackInterface $stack;

    /**
     * DumperException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '',
                                int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
                                \Throwable $previous = null)
    {
        $this->renderer = new HtmlDumper;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param StackInterface $stack
     *
     * @return static
     */
    public static function withStack(StackInterface $stack): self
    {
        $exception        = new self();
        $exception->stack = $stack;

        return $exception;
    }

    /**
     * Report the exception.
     *
     * @link https://laravel.com/docs/6.x/errors#renderable-exceptions
     *
     * @return void
     */
    public function report(): void
    {
        // do nothing
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @link https://laravel.com/docs/6.x/errors#renderable-exceptions
     *
     * @param Request $request
     *
     * @return Response
     */
    public function render(Request $request): Response
    {
        $dumped = '';

        foreach ($this->stack->all() as $item) {
            $dumped = $this->renderer->dump($item, true) . \PHP_EOL . $dumped;
        }

        return new Response($this->generateView($dumped), $this->getCode());
    }

    /**
     * Generate HTML representation for the passed content.
     *
     * @param string $content
     *
     * @return string
     */
    protected function generateView(string $content): string
    {
        return <<<EOT
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta name="robots" content="noindex, nofollow"/>
        <style>html, body {background-color: #18171b} body{margin: 0 5% 0 5%}</style>
        <title>Dumper::dd()</title>
    </head>
    <body>
        ${content}
    </body>
</html>
EOT;
    }
}
