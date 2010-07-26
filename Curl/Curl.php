<?php


namespace Curl;
use Nette;


// we'll need this
require_once dirname(__FILE__) . "/CurlResponse.php";



/**
 * An advanced Curl wrapper
 *
 * See the README for documentation/examples or http://php.net/curl for more information about the libcurl extension for PHP
 *
 * @package Curl
 * @author Sean Huber <shuber@huberry.com>
 * @author Filip Procházka <hosiplan@kdyby.org>
 */
class Curl extends Nette\Object
{
	/**#@+ Available types of requests */
	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	const HEAD = 'HEAD';
	const DOWNLOAD = 'DOWNLOAD';
	//const UPLOAD_FTP = 'UPLOAD_FTP';
	/**#@- */

	/**
	 * Used http method
	 *
	 * @var string
	 * @access protected
	 */
	protected $method;

	/**
	 * The file to read and write cookies to for requests
	 *
	 * @var string
	 * @access protected
	 */
	protected $cookieFile;


	/**
	 * The folder for saving downloaded files
	 *
	 * @var string
	 * @access protected
	 */
	protected $downloadFolder;


	/**
	 * The last downloaded file
	 *
	 * @var string
	 * @access protected
	 */
	protected $downloadPath;


	/**
	 * Determines whether or not the requests should follow redirects
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $followRedirects = True;


	/**
	 * Determines whether or not the requests has to return transfer
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $returnTransfer = True;


	/**
	 * An associative array of headers to send along with requests
	 *
	 * @var array
	 * @access protected
	 */
	protected $headers = array();


	/**
	 * An associative array of CURLOPT options to send along with requests
	 *
	 * @var array
	 * @access protected
	 */
	protected $options = array();


	/**
	 * An associative array of available proxy servers
	 *
	 * @var array
	 * @access protected
	 */
	protected $proxies = array();


	/**
	 * Variables defined on request
	 *
	 * @var string
	 * @access protected
	 */
	protected $vars;


	/**
	 * The referer header to send along with requests
	 *
	 * @var string
	 * @access protected
	 */
	protected $referer;


	/**
	 * The user agent to send along with requests
	 *
	 * @var string
	 * @access protected
	 */
	protected $userAgent;


	/**
	 * Available userAgents
	 *
	 * @var array
	 */
	public static $userAgents = array(
		'FireFox3' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0',
		'GoogleBot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
		'IE7' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
		'Netscape' => 'Mozilla/4.8 [en] (Windows NT 6.0; U)',
		'Opera' => 'Opera/9.25 (Windows NT 6.0; U; en)'
	);


	/**
	 * Stores an error string for the last request if one occurred
	 *
	 * @var string
	 * @access protected
	 */
	protected $error = '';


	/**
	 * Stores informations about last request
	 *
	 * @var string
	 * @access protected
	 */
	protected $info = '';


	/**
	 * Stores resource handle for the current CURL request
	 *
	 * @var resource
	 * @access protected
	 */
	protected $request;


	/**
	 * Stores url for the current CURL request
	 *
	 * @var url
	 * @access protected
	 */
	protected $url;


	/**
	 * Stores protocol name for file manipulation at server
	 *
	 * @var string
	 * @access protected
	 */
	protected $fileProtocol = 'file';


	/**
	 * Maximum number of request cycles after follow location
	 *
	 * @var int
	 */
	static $maxCycles = 15;



	/**
	 * Initializes a Curl object
	 *
	 * <strike>Sets the $cookieFile to "curl_cookie.txt" in the current directory</strike>
	 * Also sets the $userAgent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION.' (http://curl.kdyby.org/)' otherwise
	 *
	 * @param string $url
	 */
	public function __construct($url = Null)
	{
		if( is_string($url) AND strlen($url)>0 ){
			$this->setUrl($url);
		}

		// $this->cookieFile(dirname(__FILE__).DIRECTORY_SEPARATOR.'curl_cookie.txt');
		$this->setUserAgent(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP '.PHP_VERSION.' (http://curl.kdyby.org/)');

		$config = Nette\Environment::getConfig('curl');

		foreach( (array)$config AS $option => $value ){
			if( $option == 'cookieFile' ){
				$this->setCookieFile($value);

			} elseif( $option == 'downloadFolder' ){
				$this->setDownloadFolder($value);

			} elseif( $option == 'referer' ){
				$this->setReferer($value);

			} elseif( $option == 'userAgent' ){
				$this->setUserAgent($value);

			} elseif( $option == 'followRedirects' ){
				$this->setFollowRedirects($value);

			} elseif( $option == 'returnTransfer' ){
				$this->setReturnTransfer($value);

			} elseif( is_array($this->{$option}) ){
				foreach( (array)$value AS $key => $set ){
					$this->{$option}[$key] = $set;
				}

			} else {
				$this->{$option} = $value;
			}
		}
	}


	/**
	 * Sets option for request
	 *
	 * @param string $ip
	 * @param int $port
	 * @param string $username
	 * @param string $password
	 * @param int $timeout
	 * @return Curl
	 */
	public function addProxy($ip, $port = 3128, $username = Null, $password = Null, $timeout = 15)
	{
		$this->proxies[] = array(
			'ip' => $ip,
			'port' => $port,
			'user' => $username,
			'pass' => $password,
			'timeout' => $timeout
		);

		return $this;
	}


	/**
	 * Returns list of avalaible proxies
	 *
	 * @return string
	 */
	public function getProxies()
	{
		return $this->proxies;
	}


	/**
	 * Sets option for request
	 *
	 * @param string $option
	 * @param string $value
	 * @return Curl
	 */
	public function setOption($option, $value)
	{
		$this->options[strtoupper($option)] = $value;

		return $this;
	}


	/**
	 * Returns specific option value
	 *
	 * @param string $option
	 * @return string
	 */
	public function getOption($option)
	{
		return $this->options[strtoupper($option)];
	}


	/**
	 * Sets options for request
	 *
	 * @param array $options
	 * @return Curl
	 */
	public function setOptions(array $options)
	{
		foreach( $options AS $option => $value ){
			$this->setOption($option, $value);
		}

		return $this;
	}


	/**
	 * Returns all options
	 *
	 * @return string
	 */
	public function getOptions()
	{
		return $this->options;
	}


	/**
	 * Returns vars
	 *
	 * @return string
	 */
	public function getVars()
	{
		return $this->vars;
	}


	/**
	 * Sets header for request
	 *
	 * @param string $header
	 * @param string $value
	 * @return Curl
	 */
	public function setHeader($header, $value)
	{
		if( $header != Null AND $value != Null ){
			$this->headers[$header] = $value;
		}

		return $this;
	}


	/**
	 * Returns specific header value
	 *
	 * @param string $header
	 * @return string
	 */
	public function getHeader($header)
	{
		return $this->headers[$header];
	}


	/**
	 * Sets array of headers for request
	 *
	 * @param array $headers
	 * @return Curl
	 */
	public function setHeaders(array $headers)
	{
		foreach( $headers AS $header => $value ){
			$this->setHeader($header, $value);
		}

		return $this;
	}


	/**
	 * Returns all headers
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}


	/**
	 * Sets referer for request
	 *
	 * @param string $url
	 * @return Curl
	 */
	public function setReferer($url = Null)
	{
		$this->referer = $url;

		return $this;
	}


	/**
	 * Returns referer
	 *
	 * @return string
	 */
	public function getReferer()
	{
		return $this->referer;
	}


	/**
	 * Sets user agent for request
	 *
	 * @param string $userAgent
	 * @return Curl
	 */
	public function setUserAgent($userAgent = Null)
	{
		$userAgent = (string)$userAgent;
		$this->userAgent = (isset(self::$userAgents[$userAgent]) ? self::$userAgents[$userAgent] : $userAgent);

		return $this;
	}


	/**
	 * Returns user agent
	 *
	 * @return string
	 */
	public function getUserAgent()
	{
		return $this->userAgent;
	}


	/**
	 * Sets whether follow redirects or not from request
	 *
	 * @param bool $follow
	 * @return Curl
	 */
	public function setFollowRedirects($follow = True)
	{
		$this->followRedirects = ($follow ? True : False);

		return $this;
	}


	/**
	 * Returns whether follow redirects or not from request
	 *
	 * @return bool
	 */
	public function getFollowRedirects()
	{
		return $this->followRedirects;
	}


	/**
	 * Sets whether return result page or not
	 *
	 * @param bool $return
	 * @return Curl
	 */
	public function setReturnTransfer($return = True)
	{
		$this->returnTransfer = ($return ? True : False);

		return $this;
	}


	/**
	 * Returns whether return result page or not
	 *
	 * @return bool
	 */
	public function getReturnTransfer()
	{
		return $this->returnTransfer;
	}


	/**
	 * Sets URL for request
	 *
	 * @param string $url
	 * @return Curl
	 */
	public function setUrl($url = Null)
	{
		$this->url = $url;

		return $this;
	}


	/**
	 * Returns requested URL
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}


	/**
	 * Returns used http method
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}


	/**
	 * Returns path for last downloaded file
	 *
	 * @return string
	 */
	public function getDownloadPath()
	{
		return $this->downloadPath;
	}


	/**
	 * Returns used file protocol
	 *
	 * @return string
	 */
	public function getFileProtocol()
	{
		return $this->fileProtocol;
	}


	/**
	 * Sets cookie file for request
	 *
	 * @param string $cookieFile
	 * @throws CurlException
	 * @return Curl
	 */
	public function setCookieFile($cookieFile)
	{
		if( is_writable($cookieFile) ){
			$this->cookieFile = $cookieFile;

		} elseif( is_writable(dirname($cookieFile)) ){
			if( ($fp = @fopen($this->fileProtocol . '://' . $cookieFile, "wb")) === False ){
				throw new CurlException("Write error for file '{$cookieFile}'");
			}

			fwrite($fp,"");
			fclose($fp);

			$this->cookieFile = $cookieFile;

		} else {
			throw new CurlException("You have to create '" . $cookieFile . "' and make it writable!");
		}

		return $this;
	}


	/**
	 * Returns cookieFile
	 *
	 * @return string
	 */
	public function getCookieFile()
	{
		return $this->cookieFile;
	}


	/**
	 * Sets download folder for request
	 *
	 * @param string $downloadFolder
	 * @return Curl
	 */
	public function setDownloadFolder($downloadFolder)
	{
		if( is_string($downloadFolder) AND $downloadFolder === "" ){
			throw new CurlException("Invalid Argument \$downloadFolder");
		    
		} elseif( is_dir($downloadFolder) AND is_writable($downloadFolder) ){
			$this->downloadFolder = $downloadFolder;

		} else {
			throw new CurlException("You have to create download folder '".$downloadFolder."' and make it writable!");
		}

		return $this;
	}


	/**
	 * Returns downloadFolder
	 *
	 * @return string
	 */
	public function getDownloadFolder()
	{
		return $this->downloadFolder;
	}


	/**
	 * Sets if all certificates are trusted in default
	 *
	 * @param bool $verify
	 * @return Curl
	 */
	public function setCertificationVerify($verify = True)
	{
		$this->setOption('SSL_VERIFYPEER', ($verify ? True : False));

		return $this;
	}


	/**
	 * Adds path to trusted certificate and unsets directory with certificates if set
	 * WARNING: Overwrites the last given vertificate
	 *
	 * CURLOPT_SSL_VERIFYHOST:
	 *	0: Don’t check the common name (CN) attribute
	 *	1: Check that the common name attribute at least exists
	 *	2: Check that the common name exists and that it matches the host name of the server
	 *
	 * @param string $certificate
	 * @param int $verifyhost
	 * @return Curl
	 */
	public function setTrustedCertificate($certificate, $verifyhost=2)
	{
		if( !in_array($verifyhost, range(0,2)) ){
			throw new CurlException("Verifyhost must be in range from 0 to 2! '". $verifyhost ."' given");

		} elseif( file_exists($certificate) AND in_array($verifyhost, range(0,2)) ){
			unset($this->options['CAPATH']);

			$this->setOption('SSL_VERIFYPEER', True);
			$this->setOption('SSL_VERIFYHOST', $verifyhost); // 2=secure
			$this->setOption('CAINFO', $certificate);

		} else {
			throw new CurlException("Certificate ".$certificate." is not readable!");
		}

		return $this;
	}


	/**
	 * Adds path to directory which contains trusted certificate and unsets single certificate if set
	 * WARNING: Overwrites the last one
	 *
	 * CURLOPT_SSL_VERIFYHOST:
	 *	0: Don’t check the common name (CN) attribute
	 *	1: Check that the common name attribute at least exists
	 *	2: Check that the common name exists and that it matches the host name of the server
	 *
	 * @param string $directory
	 * @param string $verifyhost
	 * @return Curl
	 */
	public function setTrustedCertificatesDirectory($directory, $verifyhost=2)
	{
		if( !in_array($verifyhost, range(0,2)) ){
			throw new CurlException("Verifyhost must be in range from 0 to 2! '". $verifyhost ."' given");

		} elseif( is_dir($certificate) ){
			unset($this->options['CAINFO']);

			$this->setOption('SSL_VERIFYPEER', True);
			$this->setOption('SSL_VERIFYHOST', $verifyhost); // 2=secure
			$this->setOption('CAPATH', $directory);

		} else {
			throw new CurlException("Directory ".$directory." is not readable!");
		}

		return $this;
	}


	/**
	 * Returns path to trusted certificate or certificates directory
	 *
	 * @return string
	 */
	public function getTrustedCertificate()
	{
		return $this->getOption('CAPATH');
	}


	/**
	 * Returns the error string of the current request if one occurred
	 *
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}


	/**
	 * Makes a HTTP DELETE request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string    [optional] $url
	 * @param array $vars
	 * @return CurlResponse
	 */
	public function delete($url = Null, $vars = array())
	{
		if( !empty($this->url) ){
			$vars = $url;
			$url = $this->getUrl();
		}

		return $this->request(self::DELETE, $url, $vars);
	}


	/**
	 * Makes a HTTP GET request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string    [optional] $url
	 * @param array $vars
	 * @return CurlResponse
	 */
	public function get($url = Null, $vars = array())
	{
		if( !empty($this->url) ){
			$vars = $url;
			$url = $this->getUrl();
		}

		if( !empty($vars) ){
			$url .= (stripos($url, '?') !== false) ? '&' : '?';
			$url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
		}

		return $this->request(self::GET, $url);
	}


	/**
	 * Makes a HTTP HEAD request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string    [optional] $url
	 * @param array $vars
	 * @return CurlResponse
	 */
	public function head($url = Null, $vars = array())
	{
		if( !empty($this->url) ){
			$vars = $url;
			$url = $this->getUrl();
		}

		return $this->request(self::HEAD, $url, $vars);
	}


	/**
	 * Makes a HTTP POST request to the specified $url with an optional array or string of $vars
	 *
	 * @param string    [optional] $url
	 * @param array $vars
	 * @return CurlResponse
	 */
	public function post($url = Null, $vars = array())
	{
		if( !empty($this->url) ){
			$vars = $url;
			$url = $this->getUrl();
		}

		return $this->request(self::POST, $url, $vars);
	}


	/**
	 * Makes a HTTP PUT request to the specified $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string    [optional] $url
	 * @param array $vars
	 * @return CurlResponse
	 */
	public function put($url = Null, $vars = array())
	{
		if( !empty($this->url) ){
			$vars = $url;
			$url = $this->getUrl();
		}

		return $this->request(self::PUT, $url, $vars);
	}


	/**
	 * Downloads file from specified url and saves as fileName if isset or if not the name will be taken from url
	 *
	 * Returns a boolean value whatever a download was succesful and file was downloaded to $this->downloadFolder.$fileName
	 *
	 * @param string [optional] $url
	 * @param string $fileName
	 * @param array $vars
	 * @return CurlResponse
	 */
	public function download($url = Null, $fileName = Null, $vars = array())
	{
		if( !empty($this->url) ){
			$fileName = $url;
			$url = $this->getUrl();
		}

		if( !is_string($fileName) OR $fileName === '' ){
			$fileName = basename($url);
		}

		if( !is_dir($this->downloadFolder) ){
			throw new CurlException("You have to setup existing and writable folder using 'setDownloadFolder()'.");
		}

		$this->downloadPath = $this->downloadFolder . '/' . basename($fileName);
		//debug::dump(array($url, $this->downloadPath));

		if( ($fp = fopen($this->fileProtocol . '://' . $this->downloadPath, "wb")) === False ){
			throw new CurlException("Write error for file '{$this->downloadPath}'");
		}

		$this->setOption('FILE', $fp);
		$this->setOption('BINARYTRANSFER', True);

		try{
			$response = $this->request(self::DOWNLOAD, $url, $vars);
		} catch( CurlException $e ){
			throw new CurlException("Error during file download: ".$e->getMessage());
		}

		@fclose($fp);

		return $response;
	}


	/* *
	 * Uploads file
	 *
	 * Returns a bool value whether an upload was succesful
	 *
	 * @param string $file
	 * @param string $url
	 * @param string $username
	 * @param string $password
	 * @param int $timeout
	 * @return bool
	 */
// 	public function ftpUpload($file, $url, $username = Null, $password = Null, $timeout = 300)
// 	{
// 		$file_name = basename($file);
// 		$login = Null;
//
// 		if( is_string($username) AND $username !== '' AND is_string($password) AND $password !== '' ){
// 			$login = $username . ':' . $password . '@';
// 		}
//
// 		$dest = "ftp://" . $login . $url . '/' . $file_name;
//
// 		if( ($fp = @fopen($this->fileProtocol . '://' . $file, "rb")) === False ){
// 			throw new FileNotFoundException("Read error for file '{$file}'");
// 		}
//
// 		$this->setOption('URL', $dest);
//
// 		$this->setOption('TIMEOUT', $timeout);
// 		//curl_setopt($ch, CURLE_OPERATION_TIMEOUTED, 300);
// 		$this->setOption('INFILE', $fp);
// 		$this->setOption('INFILESIZE', filesize($src));
//
// 		$this->request(self::UPLOAD_FTP, $url, $vars);
//
// 		fclose($fp);
//
// 		return True;
// 	}


	/**
	 * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
	 *
	 * Returns a CurlResponse object if the request was successful, false otherwise
	 *
	 * @param string $method
	 * @param string $url
	 * @param array $vars
	 * @param int $cycles
	 * @return CurlResponse
	 */
	public function request($method, $url, $vars = array(), $cycles = 1)
	{
                if($cycles > self::$maxCycles){
                        throw new CurlException("Redirect loop");
		}

		$this->error = Null;
		$used_proxies = 0;

		if( is_array($vars) ){
			$this->vars = http_build_query($vars, '', '&');
		}

		if( !is_string($url) AND $url !== '' ){
			throw new CurlException("Invalid URL: " . $url);
		}

		do{
			$this->closeRequest();

			$this->request = curl_init();

			if( count($this->proxies) > $used_proxies ){
				//$this->setOption['HTTPPROXYTUNNEL'] = True;
				$this->setOption('PROXY', $this->proxies[$used_proxies]['ip'] . ':' . $this->proxies[$used_proxies]['port']);
				$this->setOption('PROXYPORT', $this->proxies[$used_proxies]['port']);
				//$this->setOption('PROXYTYPE', CURLPROXY_HTTP);
				$this->setOption('TIMEOUT', $this->proxies[$used_proxies]['timeout']);

				if( $this->proxies[$used_proxies]['user'] !== NUll AND $this->proxies[$used_proxies]['pass'] !== Null ){
					$this->setOption('PROXYUSERPWD', $this->proxies[$used_proxies]['user'] . ':' . $this->proxies[$used_proxies]['pass']);
				}

				$used_proxies++;

			} else {
				unset($this->options['PROXY'], $this->options['PROXYPORT'], $this->options['PROXYTYPE'], $this->options['PROXYUSERPWD']);
			} //debug::dump($this->options);

			$this->set_request_method($method);
			$this->set_request_options($url);
			$this->set_request_headers();

			$response = curl_exec($this->request);
			$this->error = curl_errno($this->request).' - '.curl_error($this->request);
			$this->info = curl_getinfo($this->request);

		} while( curl_errno($this->request) == 6 AND count($this->proxies) < $used_proxies );

		$this->closeRequest();

		if( $response ){
			$response = new CurlResponse($response, $this);

			$response_headers = $response->getHeaders();

                        if( isset($response_headers['Location']) AND $this->getFollowRedirects() ) {
				$url = new Nette\Web\Uri($response_headers['Location']);
				$lastUrl = new Nette\Web\Uri($this->info['url']);

				if( empty($url->scheme) ){ // scheme
					if( empty($lastUrl->scheme) ){
						throw new CurlException("Missign URL scheme!");
					}

					$url->scheme = $lastUrl->scheme;
				}

				if( empty($url->host) ){ // host
					if( empty($lastUrl->host) ){
						throw new CurlException("Missign URL host!");
					}

					$url->host = $lastUrl->host;
				}

				if( empty($url->path) ){ // path
					$url->path = $lastUrl->path;
				}

                                $response = $this->request($this->getMethod(), (string)$url, array(), ++$cycles);
                        }

		} else {
			if ($this->info['http_code'] == 400) {
				throw new CurlException('Bad request - ' . $response);

			} elseif ($this->info['http_code'] == 401) {
				throw new CurlException('Permission Denied - ' . $response);

			} else {
				throw new CurlException($this->error);
			}
		}

		return $response;
	}


	/**
	 * Closes the current request
	 *
	 * @access protected
	 */
	protected function closeRequest()
	{
		if( gettype($this->request) == 'resource' AND get_resource_type($this->request) == 'curl' ){
			curl_close($this->request);

		} else {
			$this->request = Null;
		}
	}


	/**
	 * Formats and adds custom headers to the current request
	 *
	 * @access protected
	 */
	protected function set_request_headers()
	{
		$headers = array();
		foreach( $this->getHeaders() as $key => $value ){
			$headers[] = (!is_int($key) ? $key.': ' : '').$value;
		}

		if( count($this->headers) > 0 ){
			curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
		}
	}


	/**
	 * Set the associated Curl options for a request method
	 *
	 * @param string $method
	 * @access protected
	 */
	protected function set_request_method($method)
	{
		$this->method = strtoupper($method);

		switch( $this->getMethod() ){
			case self::HEAD:
				curl_setopt($this->request, CURLOPT_NOBODY, True);
				break;
			case self::GET:
			case self::DOWNLOAD:
				curl_setopt($this->request, CURLOPT_HTTPGET, True);
				break;
			case self::POST:
				curl_setopt($this->request, CURLOPT_POST, True);
				break;
			case self::UPLOAD_FTP:
				curl_setopt($ch, CURLOPT_UPLOAD, True);
				break;
			default:
				curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $this->getMethod());
				break;
		}
	}


	/**
	 * Sets the CURLOPT options for the current request
	 *
	 * @param string $url
	 * @access protected
	 */
	protected function set_request_options($url)
	{
		curl_setopt($this->request, CURLOPT_URL, $url);

		if( !empty($this->vars) ){
			curl_setopt($this->request, CURLOPT_POSTFIELDS, $this->getVars());
		}

		// Set some default CURL options
		curl_setopt($this->request, CURLOPT_HEADER, true);
		curl_setopt($this->request, CURLOPT_USERAGENT, $this->getUserAgent());

		// we shouldn't trust to all certificates but we have to!
		if( !isset($this->options['SSL_VERIFYPEER']) ){
			curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, false);
		}

		if( $this->getReturnTransfer() ){
			curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
		}

		if( $this->getCookieFile() ){
			curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->getCookieFile());
			curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->getCookieFile());
		}

		// fix:Sairon http://forum.nette.org/cs/profile.php?id=1844
		if( $this->getFollowRedirects() AND strtolower(ini_get('safe_mode')) !== 'on' AND ini_get('open_basedir') == ""){
                        curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
                }

		if( $this->getReferer() ){
			curl_setopt($this->request, CURLOPT_REFERER, $this->getReferer());
		}

		// Set any custom CURL options
		foreach( $this->getOptions() as $option => $value ){
			curl_setopt($this->request, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
		}
	}


}





/**
 * Exception thrown by Curl wrapper
 *
 * @package Curl
 * @author Filip Procházka <hosiplan@kdyby.org>
 */

class CurlException extends \Exception { }

