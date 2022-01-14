<?php
 # Check for url parameter, and prevent file transfer
if (isset($_GET['url']) and preg_match('#^https?://#', $_GET['url']) === 1) {
	$url = $_GET['url'];
} else {
	header('HTTP/1.1 404 Not Found');
	exit;
}

# Check if the client already has the requested item
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) or
	isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
	header('HTTP/1.1 304 Not Modified');
	exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 12800);
curl_setopt($ch, CURLOPT_NOPROGRESS, false);
curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($DownloadSize, $Downloaded, $UploadSize, $Uploaded) { return ($Downloaded > 1024 * 4096) ? 1 : 0; } ); # max 4096kb
$response = curl_exec ($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

curl_close ($ch);

$header_blocks =  array_filter(preg_split('#\n\s*\n#Uis' , substr($response, 0, $header_size)));
$header_array = explode("\n", $header_blocks[array_key_last($header_blocks)]);

$body = substr($response, $header_size);

$headers = [];
foreach($header_array as $header_value) {
	$header_pieces = explode(': ', $header_value);
	if(count($header_pieces) == 2) {
		$headers[strtolower($header_pieces[0])] = trim($header_pieces[1]);
	}
}

if (array_key_exists('content-type', $headers)) {
	$ct = $headers['content-type'];
	if (preg_match('#image/png|image/.*icon|image/jpe?g|image/gif#', strtolower($ct)) !== 1) {
		header('HTTP/1.1 404 Not Found');
		exit;
	}
	header('Content-Type: ' . $ct);
} else {
	header('HTTP/1.1 404 Not Found');
	exit;
}

if (array_key_exists('content-length', $headers))
	header('Content-Length: ' . $headers['content-length']);
if (array_key_exists('Expires', $headers))
	header('Expires: ' . $headers['expires']);
if (array_key_exists('Cache-Control', $headers))
	header('Cache-Control: ' . $headers['cache-control']);
if (array_key_exists('Last-Modified', $headers))
	header('Last-Modified: ' . $headers['last-modified']);
echo $body;
exit;
?>
