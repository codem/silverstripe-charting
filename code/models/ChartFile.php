<?php
namespace Codem\Charts;
class ChartFile extends \File {
	private static $allowed_extensions = array(
		'csv',
	);

	public function validateWrite() {
		parent::validateWrite();
		try {
			$path = $this->getFullPath();
			$ext = $this->getExtension();
			if($ext != "csv") {
				//\SS_Log::log("Not valid extnsion {$ext}", \SS_Log::DEBUG);
				throw new \Exception("Uploaded file needs .csv extension");
			}

			if(class_exists('finfo')) {
				// in some instances, if Excel is installed on a machine uploading, the mimetype is application/vnd.ms-excel. Crazy.
				$mimetypes = array("text/csv", "text/plain", "text/x-csv", "application/vnd.ms-excel");
				$finfo = new \finfo(FILEINFO_MIME_TYPE);
				$type = $finfo->file($path);
				//\SS_Log::log("File is of type {$type}", \SS_Log::DEBUG);
				if(!in_array($type, $mimetypes)) {
					//\SS_Log::log("Not valid...", \SS_Log::DEBUG);
					throw new \Exception("Incorrect type - {$type}");
				}
			}
		} catch (\Exception $e) {
			//\SS_Log::log("Not valid..." . $e->getMessage(), \SS_Log::DEBUG);
			// remove the file on disk, if it exists
			//\SS_Log::log("File name is {$this->Name} path is {$path}", \SS_Log::DEBUG);
			if(file_exists($path) && is_writeable($path)) {
				unlink($path);
			}
			return new \ValidationException(
				"The uploaded file does not appear to be a valid CSV file",
				E_USER_WARNING
			);
		}

	}
}
