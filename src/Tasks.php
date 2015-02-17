<?php
namespace Fol\Tasks;

use Robo\Tasks as BaseTasks;

class Tasks extends BaseTasks
{
    use EnvironmentVariables;
    use PageRender;
    use PhpRender;
}
