<?php

class Migrate extends CI_Controller
{
	const MIGRATION_TABLE = 'migrations',
			MIGRATION_DIR = APPPATH.'migrations';
	private $migrationFiles = [],
			$prevMigration = null;

	public function index() {
		$this->run();
	}

	public function run($step = 0) {
		if ($this->prevMigration) {
			if (($last_migration_file = array_search($this->prevMigration, $this->migrationFiles)) !== false) {
				array_splice($this->migrationFiles, 0, $last_migration_file + 1);
			}
		}

		$this->runMigrations('up', $step);
	}

	public function rollback($step = 0) {
		$this->runMigrations('down', $step);
	}

	public function create($name, $table = null) {
		try {
			$path = self::MIGRATION_DIR.DIRECTORY_SEPARATOR.time()."_$name.php";
			if (file_exists($path))
				throw new Exception("$path already exists", 1);

			$up = "\t". ($table ? '$dbforge'.PHP_EOL."\t\t->add_field('id')".PHP_EOL."\t\t->add_field([])".PHP_EOL."\t\t->create_table('$table');" : '//code here');
			$down = "\t". ($table ? '$dbforge->drop_table(\''.$table.'\', true);' : '//code here');

			file_put_contents($path,
				'<?php'.PHP_EOL.
					'return ['.PHP_EOL.
						'"up" => function($dbforge, $db) {'.PHP_EOL.
							$up.PHP_EOL.
						'},'.PHP_EOL.
						'"down" => function($dbforge, $db) {'.PHP_EOL.
							$down.PHP_EOL.
						'}];'
			);
			echo "$name.php created\n";
		} catch (Exception $e) {
			exit("Error: ".$e->getMessage()."\n");
		}
	}

	private function runMigrations($direction, $step = 0) {
		try {
			$start_time = microtime(true);

			if ($direction == 'down') {
				if (!$prev_migrations = $this->db->order_by('migration', 'DESC')->get(self::MIGRATION_TABLE)->result())
					exit("No migration to rollback\n");

				foreach ($prev_migrations as $i => $migration) {
					if (file_exists($path = self::MIGRATION_DIR.DIRECTORY_SEPARATOR.($file = $migration->migration))) {
						echo "$file runs\n";
						$code = require($path);

						if (!isset($code[$direction]) || !is_callable($code[$direction]))
							throw new Exception("($file) Invalid migration", 1);
							
						call_user_func($code[$direction], $this->dbforge, $this->db);
						$this->db->delete(self::MIGRATION_TABLE, ['migration' => $migration->migration]);
						echo "$file rolled back\n";
					}

					if ($step && ($i + 1) >= $step)
						break;
				}
			}
			else {
				if (!$this->migrationFiles)
					exit("No migration file found\n");

				foreach ($this->migrationFiles as $i => $file) {
					echo "$file runs\n";
					$code = require(self::MIGRATION_DIR.DIRECTORY_SEPARATOR.$file);

					if (!isset($code[$direction]) || !is_callable($code[$direction]))
						throw new Exception("($file) Invalid migration", 1);;
						
					call_user_func($code[$direction], $this->dbforge, $this->db);
					$this->db->insert(self::MIGRATION_TABLE, ['migration' => $file]);
					echo "$file migrated\n";

					if ($step && ($i + 1) >= $step)
						break;
				}
			}

			$elapsed_time = round(microtime(true) - $start_time, 3) * 1000;
			echo "Took $elapsed_time ms\n";
		} catch (Exception $e) {
			exit("Error: ".$e->getMessage()."\n");
		}
	}

	private function checkRequirements() {
		try {
			if (!$this->db->table_exists($tbl = self::MIGRATION_TABLE)) {
				$this->dbforge
					->add_field([
						'migration' => [
							'type' => 'VARCHAR(256)'
						],
						'run_at' => [
							'type' => 'timestamp'
						]
					])
					->add_key('migration')
					->create_table($tbl);
			}
			else {
				if ($prev_migration = $this->db->order_by('migration', 'DESC')->get($tbl, 1)->result()) {
					$this->prevMigration = $prev_migration[0]->migration;
				}
			}

			if (!file_exists($path = self::MIGRATION_DIR)) {
				mkdir($path);
				file_put_contents($path.DIRECTORY_SEPARATOR."index.html", '');
				echo "$path directory created\n";
			}
			$this->migrationFiles = array_values(array_diff(scandir($path), ['.', '..', 'index.html']));
		} catch (Exception $e) {
			exit("Error: ".$e->getMessage()."\n");
		}
	}

	public function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->dbforge();
		$this->checkRequirements();
	}
}