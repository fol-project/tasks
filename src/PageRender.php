<?php
namespace Fol\Tasks;

use League\Plates\Engine;
use Robo\Result;
use Robo\Contract\TaskInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Trait to generate pages with data and templates
 */
trait PageRender
{
    public function taskPageRender()
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
     * @return array
     */
    protected function getPages()
    {
        $cwd = getcwd();
        chdir($this->origin);
        $files = glob('{*.yml,*.yaml,*.json,*.php}', GLOB_BRACE);
        chdir($cwd);

        return $files;
    }

    /**
     * Render a page and generate the output file
     * 
     * @param string $file The file containing the data
     */
    protected function render($file)
    {
        $data = $this->getData($file);

        if (empty($data['template'])) {
            return;
        }

        $content = $this->templates->render($data['template'], $data);

        $dest = preg_replace('/\.'.pathinfo($file, PATHINFO_EXTENSION).'$/', '.html', $file);
        $dest = "{$this->destination}/{$dest}";

        file_put_contents($dest, $content);
    }

    /**
     * Extract and returns the data form a file
     *
     * @return null|array
     */
    protected function getData($path)
    {
        $file = "{$this->origin}/{$path}";

        if (!is_file($file)) {
            throw new \Exception("The file {$file} does not exist");
        }

        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'yml':
            case 'yaml':
                return Yaml::parse($file);

            case 'json':
                return json_decode(file_get_contents($file), true);

            case 'php':
                return include $file;
        }
    }
}
