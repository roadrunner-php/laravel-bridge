<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\SocketOptions;

class SocketOptions implements SocketOptionsInterface
{
    /**
     * Options prefix.
     */
    protected const OPTIONS_PREFIX = '--';

    /**
     * Available options.
     *
     * @var string[]
     */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param array<mixed> $raw_options
     */
    public function __construct(array $raw_options = [])
    {
        $this->options = $this->parseBooleanArgumentsList($raw_options);
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption(string $option_name): bool
    {
        return \array_key_exists($option_name, $this->options);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function getOption(string $option_name)
    {
        if ($this->hasOption($option_name)) {
            return $this->options[$option_name];
        }

        throw new \InvalidArgumentException("Option with name [$option_name] does not exists.");
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Parse passed into this method list of arguments, and returns an array with normalized boolean values.
     *
     * For example: ['--one', '--not-two', 'foo', 'blah bar'] will be converted to ['one' => true, 'two' => false].
     *
     * @param string[] $arguments Array of arguments
     *
     * @return string[]
     */
    protected function parseBooleanArgumentsList(array $arguments): array
    {
        $options_prefix_length = (int) \mb_strlen(static::OPTIONS_PREFIX);

        foreach ($arguments as $argument) {
            if (\is_string($argument) && \mb_strpos($argument, static::OPTIONS_PREFIX) === 0) {
                [$argument, $value] = \explode('=', \mb_substr($argument, $options_prefix_length));
                if (\trim($value) !== '') {
                    $result[$argument] = \trim($value);
                }
            }
        }

        return $result ?? [];
    }
}
