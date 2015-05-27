<?php
namespace Fol\Tasks;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Iterator;
use League\Plates\Engine;
use Robo\Result;
use Robo\Contract\TaskInterface;
use Robo\Task\BaseTask;
use Symfony\Component\Yaml\Yaml;

/**
 * Trait to generate pages with data and templates
 */
trait PageRender
{
    protected function taskPageRender()
    {
        return new PageRenderTask();
    }
}

class PageRenderTask extends BaseTask implements TaskInterface
{
    protected $templates;
    protected $callbacks = [];
    protected $origin;
    protected $destination;
    protected $suffixes = [];

    /**
     * Set the template engine
     *
     * @param string|Engine $templates
     *
     * @return $this
     */
    public function templates($templates)
    {
        if ($templates instanceof Engine) {
            $this->templates = $templates;
        } else {
            $this->templates = new Engine($templates);
        }

        $this->templates->registerFunction('getData', function ($file) {
            return static::getData("{$this->origin}/{$file}");
        });

        return $this;
    }

    /**
     * Register a function for the template
     *
     * @param string   $name
     * @param callable $fn
     *
     * @return $this
     */
    public function registerFunction($name, callable $fn)
    {
        $this->templates->registerFunction($name, $fn);

        return $this;
    }

    /**
     * Set the origin path of the pages
     *
     * @param string $origin
     *
     * @return $this
     */
    public function origin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * Set the destination path of the pages
     *
     * @param string $destination
     *
     * @return $this
     */
    public function destination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * Set a custom callback before render each page
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function beforeRender(callable $callback)
    {
        $this->callbacks['before'] = $callback;

        return $this;
    }

    /**
     * Set a custom callback after render each page
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function afterRender(callable $callback)
    {
        $this->callbacks['after'] = $callback;

        return $this;
    }

    /**
     * Set the suffix used to save the html pages
     *
     * @param string $suffix
     * @param string $regex  Regex used to know which files apply to
     *
     * @return $this
     */
    public function suffix($suffix, $regex = '/.*/')
    {
        $this->suffixes[] = [$suffix, $regex];

        return $this;
    }

    /**
     * Run the task
     */
    public function run()
    {
        $t = 0;

        foreach ($this->getPages() as $page) {
            $this->render((string) $page);
            ++$t;
        }

        $this->printTaskInfo("<fg=yellow>{$t}</fg=yellow> pages generated and saved in <info>{$this->destination}</info>");

        return Result::success($this);
    }

    /**
     * Scan the data directory searching by data pages
     *
     * @return Iterator
     */
    protected function getPages()
    {
        $directory = new RecursiveDirectoryIterator($this->origin, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);

        return new RegexIterator($iterator, '/\.(yml|yaml|json|php)$/');
    }

    /**
     * Render a page and generate the output file
     *
     * @param string $file The file containing the data
     */
    protected function render($file)
    {
        $data = static::getData((string) $file);
        $dest = $this->getDestinationPath((string) $file);

        if (isset($this->callbacks['before'])) {
            $this->callbacks['before']($file, $dest, $data);
        }

        if (empty($data['template'])) {
            return;
        }

        $content = $this->templates->render($data['template'], $data);
        $fulldest = $this->destination.$dest;

        $dir = dirname($fulldest);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($fulldest, $content);

        if (isset($this->callbacks['after'])) {
            $this->callbacks['after']($file, $dest, $content);
        }
    }

    /**
     * Returns the destination path for a page
     *
     * @param string $file
     *
     * @return string
     */
    protected function getDestinationPath($file)
    {
        $destination_path = preg_replace('/^'.preg_quote($this->origin, '/').'/', '', $file);

        foreach ($this->suffixes as $suffix) {
            if (preg_match($suffix[1], $destination_path)) {
                return preg_replace('/\.'.pathinfo($destination_path, PATHINFO_EXTENSION).'$/', $suffix[0], $destination_path);
            }
        }

        //default
        return preg_replace('/\.'.pathinfo($destination_path, PATHINFO_EXTENSION).'$/', '.html', $destination_path);
    }

    /**
     * Extract and returns the data form a file
     *
     * @param string $file
     *
     * @return null|array
     */
    public static function getData($file)
    {
        if (!is_file($file)) {
            throw new \Exception("The file {$file} does not exist");
        }

        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case 'yml':
            case 'yaml':
                return (array) Yaml::parse($file);

            case 'json':
                return json_decode(file_get_contents($file), true);

            case 'php':
                return include $file;
        }
    }
}
