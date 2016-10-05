<?php
namespace Codem\Charts;
class ChartFile extends \File {
	private static $allowed_extensions = array(
		'csv',
	);

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$ext = $this->getExtension();
		if($ext != "csv") {
			throw new ValidationException("Uploaded file needs .csv extension");
		}

		$path = $this->getFullPath();
		if(class_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type = $finfo->file($finfo, $path);
			var_dump($type);
			exit;
		}
		return TRUE;
	}
}
