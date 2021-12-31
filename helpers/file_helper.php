<?php 
//download zip file
function createZip($files,$filename){
	$zip = new ZipArchive();
	$zip->open($filename,ZipArchive::CREATE);
	if ($zip) {
		for ($i=0; $i < count($files); $i++) { 
			$extension = getFileExtension($files[$i]);
			$zip->addFile($files[$i],($i+1).'.'.$extension);
		}
		$zip->close();
		return true;
	}
	return false;
}

function downloadZip($files,$userid='')
{
	$filename='temp'.$userid.'.zip';
	if(createZip($files,$filename)){
		$content = file_get_contents($filename);
		unlink($filename);
		sendDownload($content,'application/zip','assignmentSubmission.zip');
	}
	else{
		echo "could not zip file. please try again";
	}
}
 ?>
