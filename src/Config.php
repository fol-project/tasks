<?php
namespace Fol\Tasks;

use Fol\Config as FolConfig;
use Robo\Task\BaseTask;
use Robo\Contract\TaskInterface;

/**
 * Trait to render php pages and export the result
 */
trait Config
{
    /**
     * Init a config task
     *
     * @param FolConfig $config
     */
    protected function taskConfig(FolConfig $config)
    {
        return new ConfigTask($config);
    }
}

class ConfigTask extends BaseTask implements TaskInterface
{
    protected $configs = [];
    protected $config;
    protected $force = false;

    public function __construct(FolConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Set the config environment name
     *
     * @param null|string $name
     * 
     * @return $this
     */
    public function environment($name = null)
    {
        if ($name !== null) {
            $this->config->setEnvironment($name);
        }

        return $this;
    }

    /**
     * Overwrite the configuration
     *
     * @param boolean $force
     * 
     * @return $this
     */
    public function force($force = false)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Set config variables
     *
     * @param string $name     The configuration name
     * @param array  $defaults The an array with the default values
     * @param array  $filter   Filter the values to asking for
     * 
     * @return $this
     */
    public function set($name, array $defaults = array(), array $filter = array())
    {
        $this->configs[] = [$name, $defaults, $filter];

        return $this;
    }

    /**
     * Run the task
     */
    public function run()
    {
        foreach ($this->configs as $config) {
            list($name, $defaults, $filter) = $config;

            $oldVariables = $this->config->get($name) ?: [];
            $path = $this->config->getPathsFor($name)[0];
            $overwrite = !is_file($path) ? true : $this->force;

            if ($defaults) {
                $variables = array_intersect_key($oldVariables, $defaults) + $defaults;
            } else {
                $variables = $oldVariables;
            }

            foreach ($variables as $k => &$value) {
                $this->askConfig($name, $k, $value, (isset($oldVariables[$k]) ? $oldVariables[$k] : null), $filter, $overwrite);
            }

            $dir = pathinfo($path, PATHINFO_DIRNAME);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($path, "<?php\n\nreturn ".var_export($variables, true).';');
            $this->printTaskInfo("Config saved in {$path}");
        }
    }

    /**
     * Ask to the user for a specific config value
     *
     * @param string     $basename
     * @param string     $name
     * @param mixed      &$value
     * @param mixed      &$oldValue
     * @param null|array $filter
     * @param boolean    $force
     */
    private function askConfig($basename, $name, &$value, $oldValue, array $filter, $force)
    {
        if (is_array($value)) {
            foreach ($value as $n => &$v) {
                $this->askConfig("{$basename}.{$name}", $n, $v, isset($oldValue[$n]) ? $oldValue[$n] : null, $filter, $force);
            }

            return;
        }

        if (!empty($filter) && !in_array($name, $filter)) {
            return;
        }

        if ($force || !isset($oldValue)) {
            $value = $this->askDefault("Value for: {$basename}.{$name}", $value);
        }
    }
}
