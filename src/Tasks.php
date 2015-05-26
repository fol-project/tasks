<?php
namespace Fol\Tasks;

use Robo\Tasks as BaseTasks;

class Tasks extends BaseTasks
{
    use Config;
    use PageRender;
    use PhpRender;
    use GettextScanner;
    use ImageManipulation;
}
