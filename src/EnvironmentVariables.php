<?php
namespace Fol\Tasks;

use Robo\Task\BaseTask;
use Robo\Contract\TaskInterface;

/**
 * Trait to render php pages and export the result
 */
trait EnvironmentVariables
{
    protected function taskEnvironmentVariables()
    {
        return new EnvironmentVariablesTask();
    }
}

class EnvironmentVariablesTask extends BaseTask implements TaskInterface
{
    protected $force = false;
    protected $template = 'env.php';
    protected $output = 'env.local.php';

    /**
     * Set the template/output variables
     *
     * @param string $template
     * @param string $output
     */
    public function variables($template, $output)
    {
        $this->template = $template;
        $this->output = $output;

        return $this;
    }

    /**
     * @param boolean $force
     */
    public function force($force = true)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Run the task
     */
    public function run()
    {
        if (!is_file($this->output) || $this->force) {
            $variables = require $this->template;

            foreach ($variables as $name => &$value) {
                $value = $this->askDefault("Value for: {$name}", $value);
            }

            file_put_contents($this->output, "<?php\n\nreturn ".var_export($variables, true).';');
        }
    }
}
