<?php
namespace Fol\Tasks;

use Robo\Task\BaseTask;
use Robo\Contract\TaskInterface;

/**
 * Trait to render php pages and export the result
 */
trait Config
{
    protected function taskConfig()
    {
        return new ConfigTask();
    }
}

class ConfigTask extends BaseTask implements TaskInterface
{
    protected $configs = [];

    /**
     * Set config variables
     *
     * @param string|array $input     The filepath or an array with the config
     * @param string       $output    The filename where to export the config
     * @param array|null   $filter    Filter the values to asking for
     * @param boolean      $overwrite Ask even if the output value is defined
     */
    public function set($input, $output, $filter = null)
    {
        $this->configs[] = [$input, $output, $filter, false];

        return $this;
    }

    /**
     * Overwrite previous variables
     *
     * @param string|array $input  The filepath or an array with the config
     * @param string       $output The filename where to export the config
     * @param array|null   $filter Filter the values to asking for
     */
    public function overwrite($input, $output, $filter = null)
    {
        $this->configs[] = [$input, $output, $filter, true];

        return $this;
    }

    /**
     * Run the task
     */
    public function run()
    {
        foreach ($this->configs as $config) {
            list($input, $output, $filter, $overwrite) = $config;

            if (is_array($input)) {
                $variables = $input;
                $basename = '';
            } else {
                $variables = require $input;
                $basename = pathinfo($input, PATHINFO_FILENAME).'.';
            }

            if (is_file($output)) {
                $oldValues = require $output;
            } else {
                $oldValues = [];
            }

            $variables = array_replace_recursive($variables, $oldValues);

            foreach ($variables as $name => &$value) {
                $this->askConfig($basename, $name, $value, $overwrite, $oldValues, $filter);
            }

            $dir = pathinfo($output, PATHINFO_DIRNAME);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($output, "<?php\n\nreturn ".var_export($variables, true).';');
            $this->printTaskInfo("Config saved in {$output}");
        }
    }

    /**
     * Ask to the user for a specific config value
     *
     * @param string     $basename
     * @param string     $name
     * @param mixed      &$value
     * @param boolean    $overwrite
     * @param null|array $oldValues
     * @param null|array $filter
     */
    private function askConfig($basename, $name, &$value, $overwrite, array $oldValues = null, array $filter = null)
    {
        if (is_array($value)) {
            foreach ($value as $n => &$v) {
                $this->askConfig("{$basename}{$name}.", $n, $v, $overwrite, isset($oldValues[$name]) ? $oldValues[$name] : null, $filter);
            }
        } else {
            if ((!isset($oldValues[$name]) || $overwrite) && (empty($filter) || in_array($name, $filter))) {
                $value = $this->askDefault("Value for: {$basename}{$name}", $value);
            }
        }
    }
}
