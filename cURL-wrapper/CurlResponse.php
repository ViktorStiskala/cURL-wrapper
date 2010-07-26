<?php

/**
 * Parses the response from a cURL request into an object containing
 * the response body and an associative array of headers
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
 * @author Filip Procházka <hosiplan@kdyby.org>
 */

final class CurlResponse
{

	/**#@+ regexp's for parsing */
	const HEADER_REGEXP = "#(.*?)\:\s(.*)#";
	const HEADERS_REGEXP = "#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims";
	const VERSION_AND_STATUS = "#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#";
	const FILE_CONTENT_START = "\r\n\r\n";
	/**#@- */

	/**
	 * The body of the response without the headers block
	 *
	 * @var string
	 */
	var $body = '';


	/**
	 * An associative array containing the response's headers
	 *
	 * @var array
	 */
	var $headers = array();


	/**
	 * Contains reference for curlRequest
	 *
	 * @var Curl
	 * @access protected
	 */
	protected $curlRequest;


	/**
	 * Contains resource for last downloaded file
	 *
	 * @var resource
	 * @access protected
	 */
	protected $downloadedFile;


	/**
	 * Accepts the result of a curl request as a string
	 *
	 * <code>
	 * $response = new CurlResponse(curl_exec($curl_handle));
	 * echo $response->body;
	 * echo $response->headers['Status'];
	 * </code>
	 *
	 * @param string $response
	 */
	public function __construct($response, &$curlRequest = Null)
	{
		$this->curlRequest = $curlRequest;

		if( $this->getRequest()->getMethod() === Curl::DOWNLOAD ){
			$this->parseFile();

		} else {
			# Extract headers from response
			preg_match_all(self::HEADERS_REGEXP, $response, $matches);
			$headers_string = array_pop($matches[0]);
			$headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

			# Remove headers from the response body
			$this->body = str_replace($headers_string, '', $response);

			$this->parseHeaders($headers);
		}
	}


	/**
	 * Parses headers from given list
	 *
	 * @param array $headers
	 */
	private function parseHeaders($headers)
	{
		# Extract the version and status from the first header
		$version_and_status = array_shift($headers);
		preg_match(self::VERSION_AND_STATUS, $version_and_status, $matches);
		if( count($matches) > 0 ){
			$this->headers['Http-Version'] = $matches[1];
			$this->headers['Status-Code'] = $matches[2];
			$this->headers['Status'] = $matches[2].' '.$matches[3];
		}

		# Convert headers into an associative array
		foreach ($headers as $header) {
			preg_match(self::HEADER_REGEXP, $header, $matches);
			$this->headers[$matches[1]] = $matches[2];
		}
	}


	/**
	 * Fix downloaded file
	 *
	 * @return CurlResponse  provides a fluent interface
	 */
	public function parseFile()
	{
		if( $this->getRequest()->getMethod() === Curl::DOWNLOAD ){
			$path_p = $this->getRequest()->getDownloadPath();
			@fclose($this->getRequest()->getOption('FILE'));

			if( ($fp = fopen($this->getRequest()->getFileProtocol() . '://' . $path_p, "rb")) === False ){
				throw new CurlException("Fopen error for file '{$path_p}'");
			}

			$rows = array();
			do{
				if( feof($fp) ){
					break;
				}
				$rows[] = fgets($fp);

				preg_match_all(self::HEADERS_REGEXP, implode($rows), $matches);

			} while( count($matches[0])==0 );

			if( isset($matches[0][0]) ){
				$headers_string = array_pop($matches[0]);
				$headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));
				$this->parseHeaders($headers);

				fseek($fp, strlen($headers_string));
// 				$this->curlRequest->getFileProtocol();

				$path_t = $this->getRequest()->getDownloadPath() . '.tmp';

				if( ($ft = fopen($this->getRequest()->getFileProtocol() . '://' . $path_t, "wb")) === False ){
					throw new CurlException("Write error for file '{$path_t}' ");
				}

				while( !feof($fp) ){
					$row = fgets($fp, 4096);
					fwrite($ft, $row);
				}

				fclose($fp);
				fclose($ft);

				if( !@unlink($this->curlRequest->getFileProtocol() . '://' . $path_p) ){
					throw new CurlException("Error while deleting file {$path_p} ");
				}

				if( !@rename($this->curlRequest->getFileProtocol() . '://' . $path_t, $this->getRequest()->getFileProtocol() . '://' . $path_p) ){
					throw new CurlException("Error while renaming file '{$path_t}' to '".basename($path_p)."'. ");
				}

				@chmod($path_p, 0755);

			}
		}

		return $this;
	}

	/**
	 * Returns the response body
	 *
	 * <code>
	 * $curl = new Curl;
	 * $response = $curl->get('google.com');
	 * echo $response;  # => echo $response->body;
	 * </code>
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->body;
	}


	/**
	 * Returns the response body
	 */
	public function getBody()
	{
		return $this->body;
	}


	/**
	 * Alias for getBody
	 */
	public function getResponse()
	{
		return $this->body;
	}


	/**
	 * Returns the response headers
	 */
	public function getHeaders()
	{
		return $this->headers;
	}


	/**
	 * Returns specified header
	 */
	public function getHeader($header)
	{
		if( isset($this->headers[$header]) ){
			return $this->headers[$header];

		} else {
			return Null;
		}
	}


	/**
	 * Returns resource to downloaded file
	 *
	 * @return resource
	 */
	public function openFile()
	{
		$path = $this->curlRequest->getDownloadPath();
		if( ($this->downloadedFile = fopen($this->getRequest()->getFileProtocol() . '://' . $path, "r")) === False ){
			throw new CurlException("Read error for file '{$path}'");
		}

		return $this->downloadedFile;
	}


	/**
	 * Returns resource to downloaded file
	 *
	 * @return resource file stream
	 */
	public function closeFile()
	{
		return @fclose($this->downloadedFile);
	}


	/**
	 * Returns the Curl request object
	 *
	 * @return Curl
	 */
	public function getRequest()
	{
		return $this->curlRequest;
	}


}

