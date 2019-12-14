<?php

class Seed extends CI_Controller
{
	const DIR = APPPATH.'seeds';

	public function index() {
		$this->run();
	}

	public function run($seed = null) {
		try {
			if (!$seed_files = $this->allSeeds())
				throw new Exception("No seed found", 1);

			$start_time = microtime(true);

			if ($seed) {
				if (!in_array($file = "$seed.php", $seed_files))
					throw new Exception("$seed not found", 1);
					
				$this->runFile($file);
			}
			else {
				foreach ($seed_files as $key => $file) {
					$this->runFile($file);
				}
			}

			$elapsed_time = round(microtime(true) - $start_time, 3) * 1000;
			echo "Took $elapsed_time ms".PHP_EOL;
		} catch (Exception $e) {
			exit("Error: ".$e->getMessage().PHP_EOL);
		}
	}

	private function runFile($file) {
		echo "$file start".PHP_EOL;
		$code = require(self::DIR.DIRECTORY_SEPARATOR.$file);
		if (!$code || !is_callable($code))
			exit("Invalid seed".PHP_EOL);
			
		call_user_func($code, $this->db);
		echo "$file done".PHP_EOL;
	}

	public function create($name) {
		try {
			$path = self::DIR.DIRECTORY_SEPARATOR."$name.php";
			if (file_exists($path))
				throw new Exception("$path already exists", 1);

			file_put_contents($path,
				'<?php'.PHP_EOL.
					'return function($db) {'.PHP_EOL.
					"\t//code here".PHP_EOL.
					'};'
			);
			echo "$name.php created".PHP_EOL;
		} catch (Exception $e) {
			exit("Error: ".$e->getMessage().PHP_EOL);
		}
	}

	public function list() {
		if (!$list = $this->allSeeds())
			exit("No seed found".PHP_EOL);

		echo implode(PHP_EOL, $list).PHP_EOL;
	}

	private function allSeeds() {
		return array_values(array_diff(scandir(self::DIR), ['.', '..', 'index.html']));
	}

	private function checkRequirements() {
		try {
			if (!file_exists($path = self::DIR)) {
				mkdir($path);
				file_put_contents($path.DIRECTORY_SEPARATOR."index.html", '');
				echo "$path directory created".PHP_EOL;
			}
		} catch (Exception $e) {
			exit("Error: ".$e->getMessage().PHP_EOL);
		}
	}

	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->checkRequirements();
	}
}