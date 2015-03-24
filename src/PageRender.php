<?php
namespace Fol\Tasks;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Iterator;

use League\Plates\Engine;
use Robo\Result;
use Robo\Contract\TaskInterface;
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

class PageRenderTask implements TaskInterface
{
    protected $templates;
    protected $origin;
    protected $destination;

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
     * Run the task
     */
    public function run()
    {
        foreach ($this->getPages() as $page) {
            $this->render($page);
        }

        return Result::success($this);
    }

    /**
     * Scan the data directory searching by data pages
     *
     * @return Iterator
     */
    protected function getPages()
    {
        $directory = new RecursiveDirectoryIterator($this->origin, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME);
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
        $data = static::getData($file);

        if (empty($data['template'])) {
            return;
        }

        $content = $this->templates->render($data['template'], $data);

        $dest = preg_replace('/\.'.pathinfo($file, PATHINFO_EXTENSION).'$/', '.html', $file);
        $dest = preg_replace('/^'.preg_quote($this->origin, '/').'/', $this->destination, $dest);

        $dir = dirname($dest);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($dest, $content);
    }

    /**
     * Extract and returns the data form a file
     *
     * @param string $path
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
