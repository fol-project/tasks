<?php
namespace Fol\Tasks;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Iterator;
use Imagecow\Image;
use Robo\Result;
use Robo\Contract\TaskInterface;
use Robo\Task\BaseTask;

/**
 * Trait to generate pages with data and templates
 */
trait ImageManipulation
{
    protected function taskImageManipulation()
    {
        return new ImageManipulationTask();
    }
}

class ImageManipulationTask extends BaseTask implements TaskInterface
{
    protected $operations = [];
    protected $origin;
    protected $destination;

    /**
     * Set the origin path of the images
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
     * Set the destination path of the images
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
     * Add operations
     *
     * @see \Imagecow\Libs\Libinterface
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $this->operations[] = [$name, $arguments];

        return $this;
    }

    /**
     * Run the task
     */
    public function run()
    {
        $t = 0;

        foreach ($this->getImages() as $image) {
            $this->convert($image);
            ++$t;
        }

        $this->printTaskInfo("<fg=yellow>{$t}</fg=yellow> images manipulated and saved in <info>{$this->destination}</info>");

        return Result::success($this);
    }

    /**
     * Scan the data directory searching by images
     *
     * @return Iterator
     */
    protected function getImages()
    {
        $directory = new RecursiveDirectoryIterator($this->origin, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME);
        $iterator = new RecursiveIteratorIterator($directory);

        return new RegexIterator($iterator, '/\.(jpg|jpeg|gif|png)$/');
    }

    /**
     * Converts an image and generate the output file
     *
     * @param string $file
     */
    protected function convert($file)
    {
        $image = Image::createFromFile($file);

        foreach ($this->operations as $operation) {
            call_user_func_array([$image, $operation[0]], $operation[1]);
        }

        $destination_path = preg_replace('/^'.preg_quote($this->origin, '/').'/', $this->destination, $file);

        $dir = dirname($destination_path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $image->save($destination_path);
    }
}
