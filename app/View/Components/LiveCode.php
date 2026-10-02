<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;

class LiveCode extends Component
{
    public array $config;

    public array $descriptionParts;

    public function __construct(array $config, public int $headingLevel = 2)
    {
        if ($headingLevel < 2 || $headingLevel > 5) {
            throw new InvalidArgumentException('Level heading Live Coding harus antara 2 dan 5.');
        }

        $config += [
            'entry_file' => 'main.py',
            'title' => 'Live Coding',
            'description' => '',
            'checker' => '',
        ];

        if (! is_string($config['id'] ?? null) || ! preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $config['id'])) {
            throw new InvalidArgumentException('Live Coding membutuhkan id unik berupa huruf, angka, tanda - atau _.');
        }

        if (! is_array($config['files'] ?? null) || $config['files'] === []) {
            throw new InvalidArgumentException('Live Coding membutuhkan setidaknya satu file Python.');
        }

        foreach ($config['files'] as $name => $source) {
            if (! is_string($name) || ! preg_match('~^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.py$~D', $name) || ! is_string($source)) {
                throw new InvalidArgumentException('File harus berupa path relatif .py dengan isi string.');
            }
        }

        foreach (['entry_file', 'title', 'description', 'checker'] as $key) {
            if (! is_string($config[$key])) {
                throw new InvalidArgumentException("Konfigurasi {$key} harus berupa string.");
            }
        }

        if (! array_key_exists($config['entry_file'], $config['files'])) {
            throw new InvalidArgumentException('Entry file Live Coding tidak ditemukan dalam files.');
        }

        $this->config = $config;
        $parts = preg_split('/`([^`\r\n]+)`/u', $config['description'], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$config['description']];
        $this->descriptionParts = [];
        foreach ($parts as $index => $text) {
            $this->descriptionParts[] = ['tag' => $index % 2 === 1 ? 'code' : 'span', 'text' => $text];
        }
    }

    public function render(): View
    {
        return view('components.live-code');
    }
}
