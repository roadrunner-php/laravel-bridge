<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Dumper;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class Middleware
{
    /**
     * @var Stack\StackInterface
     */
    protected Stack\StackInterface $stack;

    /**
     * @var HtmlDumper
     */
    protected HtmlDumper $renderer;

    /**
     * Middleware constructor.
     *
     * @param Stack\StackInterface $stack
     */
    public function __construct(Stack\StackInterface $stack)
    {
        $this->stack    = $stack;
        $this->renderer = new HtmlDumper;
    }

    /**
     * Modify response after the request is handled by the application.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof Response && $this->stack->count() > 0) {
            $dumped = '';

            foreach ($this->stack->all() as $item) {
                $dumped = $this->renderer->dump($item, true) . \PHP_EOL . $dumped;
            }

            $response->setContent($dumped . $response->getContent());
        }

        return $response;
    }
}
