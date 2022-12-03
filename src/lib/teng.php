<?php
/**
 * @name teng
 * @version 1.4
 * @description Creating data structure and generating templates for TEmplate ENgine "Teng" - https://teng.sourceforge.net
 */
class Teng
{
	/**
	 * Constructor
	 * @param array $data Output data for templates
	 * @param array $conf Config object
	 * @param array.string $conf["templPath"] Relevant path for templates directory (default "templ/")
	 * @param array.string $conf["file"] File with template
	 * @param array.string $conf["content_type"] File content type (default "text/html")
	 * @param array.string $conf["encoding"] File coding (default "utf-8")
	 * @param array.string $conf["dict"] File with dictionary (default "teng-cz.dict")
	 * @param array.string $conf["config"] File with configuration for Teng engine (default "teng.conf")
	 */
	function __construct($data, $conf) {
		$this->data = $data;

		foreach ($conf as $key => $value) $$key = $value;
		$templPath = ($templPath ? $templPath : "templ/");

		$configObj = array(
			"content_type" => ($content_type ? $content_type : "text/html"),
			"encoding" => ($encoding ? $encoding : "utf-8"),
			"dict" => $templPath.($dict ? $dict : "teng-cz.dict"),
			"config" => $templPath.($config ? $config : "teng.conf")
		);

		$this->rootFrags = false;
		$this->teng = teng_init();
		$this->root = "";
		$this->setTengDataRoot($data);
		$this->generatePage($templPath.$file, $configObj);
	}

	/**
	 * If this array is associative?
	 * @param array $arr Input array
	 */
	function isAssoc($arr) {
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	/**
	 * Go recursively thru data structure of multiple elements and creates fragments
	 * @param array $data Input data object
	 * @param reference $frag Parent fragment
	 */
	function createFrags($data, $frag) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {

				$scalars = array();
				$arrays = array();

				foreach ($value as $i => $j) {
					$type = gettype($j);
					if ($type == "string" || $type == "integer") {
						$scalars[$i] = $j;
					} elseif ($type == "array") {
						$arrays[$i] = $j;
					}
				}

				if (count($scalars)) {
					$parentFrag = teng_add_fragment($frag, "data", $scalars);
				} else {
					$parentFrag = $frag;
				}

				foreach ($arrays as $k => $l) {
					$isNotArray = false;
					foreach ($l as $m => $n) {
						if (is_array($n)) {
							$isNotArray = true;
							break;
						}
					}

					if (!$isNotArray) {
						if ($this->isAssoc($l)) {
							if ($k) teng_add_fragment($parentFrag, $k, $l);
						} else {
							foreach ($l as $la) teng_add_fragment($parentFrag, $k, array($k => $la));
						}
					} else {
						$subfrag = @teng_add_fragment($parentFrag, $k, $l);
						$this->createFrags($l, $subfrag);
					}
				}
			}
			unset($scalars, $arrays);
		}
	}

	/**
	 * Go through the recursion structure of multiple elements and creates fragments
	 * @param array $data Input data
	 * @param reference $frag Parent fragment
	 */
	function setTengDataRoot($data) {
		$rootData = array();
		$fragData = array();

		foreach ($data as $key => $value) {
			$type = gettype($value);
			if ($type == "string" || $type == "integer") {
				$rootData[$key] = $value;
			} elseif ($type == "array") {
				$fragData[$key] = $value;
			}
		}

		$this->root = teng_create_data_root($rootData);
		$this->createFrags($fragData, $this->root, true);
	}

	/**
	 * Generate template
	 * @param string $file File path
	 * @param array $configObj Config object
	 */
	function generatePage($file, $configObj) {
		echo(teng_page_string($this->teng, $file, $this->root, $configObj));

		teng_release_data($this->root);
		teng_release($this->teng);
	}
}
?>
