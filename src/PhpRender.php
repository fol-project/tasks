<?php
namespace Fol\Tasks;

use Robo\Result;
use Robo\Contract\TaskInterface;

/**
 * Trait to render php pages and export the result
 */
trait PhpRender
{
    protected function taskPhpRender()
    {
        return new PhpRenderTask();
    }
}

class PhpRenderTask implements TaskInterface
{
    protected $files = [];

    /**
     * Set an input/output php render
     *
     * @param string $input
     * @param string $output
     */
    public function render($input, $output)
    {
        $this->files[] = [$input, $output];

        return $this;
    }

    /**
     * Run the task
     */
    public function run()
    {
        foreach ($this->files as $file) {
            list($input, $output) = $file;

            ob_start();
            include $input;

            file_put_contents($output, ob_get_clean());
        }
    }
}
