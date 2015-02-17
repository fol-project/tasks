<?php
namespace Fol\Tasks;

use Robo\Config;
use Symfony\Component\Console\Output\ConsoleOutput;

class Runner extends \Robo\Runner
{
	public function execute($class = null, array $argv = null)
	{
		// Share the output object
		Config::setOutput(new ConsoleOutput());

		// Make sure the class is actually loaded.
		if (!class_exists($class))
		{
			$this->getOutput()->writeln
			(
				'<error>'.
					'Class "'.$class.'" needs to be loaded or '.
					'be able to be loaded before calling this runner!'.
				'</error>'
			);

            exit(1);
		}

		if (!$argv) {
			$argv = $_SERVER['argv'];
		}

		// Create and run the robo cli application
		$app = $this->createApplication($class);
		$app->run($this->prepareInput($argv));
	}
}
