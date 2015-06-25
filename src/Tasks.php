<?php
namespace Fol\Tasks;

use Robo\Tasks as BaseTasks;

class Tasks extends BaseTasks
{
    use PageRender;
    use PhpRender;
    use GettextScanner;
    use ImageManipulation;
}
