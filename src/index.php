<?php

class Rule {
	public $requestType;
	public $path;
	public $args;
	public $isAuth;
	public $routes = [];
}

class Route {
	const TYPE_CODE = 'code';
	const TYPE_SQL = 'sql';
	const TYPE_STATIC = 'static';
	const TYPE_PHP = 'php';
	public $type;
	public $string;
}

class Parser {

	public function parse($str) {
		$rules = [];

		//lines are important to us
		$lines = explode('\n', $str);

		$currentNode = null;

		foreach($lines as $line) {
			if (isNewRule($line)) {
				$requestType = getRequestType($line);
				$args = getPath($line);
				$path = array_shift($args);
				if ($requestType) {
					if ($path) {
						$rule = new Rule();
						$rule->requestType = $requestType;
						$rule->args = $args;
						$rule->path = $path;
						$rules[] = $rule;
						$currentNode = $rule;
					} else {
						die("Bad request path: $path");
					}
				} else {
					die("Bad request type: $requestType");
				}
			} else {
				if (!is_null($currentNode)) {
					getCode($line);

				} else {
					die("Tried to add a sub rule while there was no rule active $line");
				}
			}
		}
	}

	private function getPath($line) {
		$path = "/";
		$vars = [];
		$path = array_filter(explode(' ', trim($line)), function($linePart) {
			return $linePart[0] == "/";
		});
		if (count($path) < 1) {
			return false;
		} else if (count($path) > 1) {
			die("More than one path found: $line");
		} else {
			$pathParts = explode("/", $path[0]);
			foreach($pathParts as $part) {
				if ($part[0] == "!" || $part[0] == ":") {
					$vars[] = $part;
				} else {
					if (!empty($vars)) {
						die("Found var when expecting path: $line");
					}
				}
			}
		}
		return array_merge([$path], $vars);
	}

	private function isAuthRequest($line) {
		return substr((trim($line)), 0, 4) == "AUTH";
	}

	private function getRequestType($line) {
		$exploded = explode(' ', trim($line));
		$type = array_shift($exploded);
		if (in_array($type, ["REQUEST", "GET", "POST", "PUT", "DELETE"])) {
			return $exploded[0];
		} if ($exploded[0] == "AUTH") {
			return getRequestType(implode(" ", $exploded));
		}
		return false;
	}

	private function isNewRule($line) {
		return $line[0] == "\t";
	}

}



function testParse() {
$testStatic = <<<DOC
REQUEST /
	static >> /static/index.html
DOC;
$p = new Parser();
var_dump($p->parse($testStatic));
}

testParse();
