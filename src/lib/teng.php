<?php
/**
 * @name teng
 * @version 1.3
 * @description Sestavuje datovou strukturu a generuje šablony pro Template ENGine Teng
 */
class Teng
{
	/**
	 * Constructor
	 * @param array $data Výstupní data do šablon
	 * @param array $conf Objekt s konfigurací
	 * @param array.string $conf["templPath"] Relativní cesta k adresáři s šablonama (výchozí "templ/")
	 * @param array.string $conf["file"] Soubor s šablonou
	 * @param array.string $conf["content_type"] Content type pro daný soubor (výchozí "text/html")
	 * @param array.string $conf["encoding"] Kódování souboru s šablonou (výchozí "utf-8")
	 * @param array.string $conf["dict"] Soubor se slovníkem (výchozí "teng-cz.dict")
	 * @param array.string $conf["config"] Soubor s konfigurací pro Teng (výchozí "teng.conf")
	 */
	function __construct($data, $conf) {
		$this->data = $data;

		# Zjednodušíme si konfigurační proměnné
		foreach ($conf as $key => $value) $$key = $value;
		$templPath = ($templPath ? $templPath : "templ/");

		# Příprava konfiguračního objektu pro inicializaci Tengu,
		# případné načtení výchozích hodnot
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
	 * Projde rekurzivně datovou strukturu vícenásobných prvků a vytvoří z nich fragmenty
	 * @param array $data Vstupní data
	 * @param reference $frag Nadřazený fragment
	 */
	function createFrags($data, $frag) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {

				# Vyseparujeme zvlášť pole a ostatní hodnoty (řetězce a číselné hodnoty)
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

				# V případě, že daná iterace obsahuje nějaké řetězce
				if (count($scalars)) {
					$parentFrag = teng_add_fragment($frag, "data", $scalars);
				} else {
					$parentFrag = $frag;
				}

				# Zde zjišťujeme, zda-li daná iterace obsahuje v sobě další pole
				foreach ($arrays as $k => $l) {
					$isNotArray = false;
					foreach ($l as $m => $n) {
						if (is_array($n)) {
							$isNotArray = true;
							break;
						}
					}

					if (!$isNotArray) {
						if (FW::isAssoc($l)) {
							if ($k) teng_add_fragment($parentFrag, $k, $l);
						} else {
							foreach ($l as $la) teng_add_fragment($parentFrag, $k, array($k => $la));
						}
					} else {
						$subfrag = teng_add_fragment($parentFrag, $k, $l);
						$this->createFrags($l, $subfrag, false);
					}
				}
			}
			unset($scalars, $arrays);
		}
	}

	/**
	 * Projde rekurzivně datovou strukturu vícenásobných prvků a vytvoří z nich fragmenty
	 * @param array $data Vstupní data
	 * @param reference $frag Nadřazený fragment
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
	 * Vygeneruje výslednou šablonu
	 * @param string $file Cesta k souboru
	 * @param array $configObj Konfigurační objekt
	 */
	function generatePage($file, $configObj) {
		echo(teng_page_string($this->teng, $file, $this->root, $configObj));

		teng_release_data($this->root);
		teng_release($this->teng);
	}
}
?>
