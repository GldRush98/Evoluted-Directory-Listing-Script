<?php
session_start();
class DirectoryListing {
	/*
	=====================================================================================================
	Evoluted Directory Listing Script - Version 5
	www.evoluted.net / info@evoluted.net
	=====================================================================================================

	SYSTEM REQUIREMENTS
	=====================================================================================================
	This script requires PHP version 7.1 along with the GD library if you wish to use 
	the thumbnail/image preview functionality. For (optional) unzip functionality, you'll 
	need the ZipArchive php extension.

	HOW TO USE
	=====================================================================================================
	1) Unzip the provided files.
	2) Upload the index.php file to the directory you wish to use the script on
	3) Browse to the directory to see the script in action
	4) Optionally change any of the settings below

	CONFIGURATION
	=====================================================================================================
	You may edit any of the variables in this section to alter how the directory listing script will
	function. Please read the notes above each variable for details on what they change.
	*/

	// The top level directory where this script is located, or alternatively one of it's sub-directories
	public $startDirectory = '.';

	// An optional title to show in the address bar and at the top of your page (set to null to leave blank)
	public $pageTitle = 'Evoluted Directory Listing Script';

	// The URL of this script. Optionally set if your server is unable to detect the paths of files
	public $includeUrl = false;

	// If you've enabled the includeUrl parameter above, enter the full url to the directory the index.php file
	// is located in here, followed by a forward slash.
	public $directoryUrl = 'http://yoursite.com/main-directory-name-here/';

	// Set to true to list all sub-directories and allow them to be browsed
	public $showSubDirectories = true;

	// Set to true to open all file links in a new browser tab
	public $openLinksInNewTab = true;

	// Set to true to show thumbnail previews of any images
	public $showThumbnails = true;

	// Set to true to allow new directories to be created.
	public $enableDirectoryCreation = true;

	// Set to true to allow file uploads (NOTE: you should set a password if you enable this!)
	public $enableUploads = true;

	// Enable multi-file uploads (NOTE: This makes use of javascript libraries hosted by Google so an internet connection is required.)
	public $enableMultiFileUploads = true;

	// Set to true to overwrite files on the server if they have the same name as a file being uploaded
	public $overwriteOnUpload = false;

	// Set to true to enable file deletion options
	public $enableFileDeletion = true;

	// Set to true to enable directory deletion options (only available when the directory is empty)
	public $enableDirectoryDeletion = true;

	// List of all mime types that can be uploaded. Full list of mime types: http://www.iana.org/assignments/media-types/media-types.xhtml
	public $allowedUploadMimeTypes = [
		'image/jpeg',
		'image/gif',
		'image/png',
		'image/bmp',
		'audio/mpeg',
		'audio/mp3',
		'audio/mp4',
		'audio/x-aac',
		'audio/x-aiff',
		'audio/x-ms-wma',
		'audio/midi',
		'audio/ogg',
		'video/ogg',
		'video/webm',
		'video/quicktime',
		'video/x-msvideo',
		'video/x-flv',
		'video/h261',
		'video/h263',
		'video/h264',
		'video/jpeg',
		'text/plain',
		'text/html',
		'text/css',
		'text/csv',
		'text/calendar',
		'application/pdf',
		'application/x-pdf',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // MS Word (modern)
		'application/msword',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // MS Excel (modern)
		'application/zip',
		'application/x-tar'
	];

	// Set to true to unzip any zip files that are uploaded (note - will overwrite files of the same name!)
	public $enableUnzipping = true;

	// If you've enabled unzipping, you can optionally delete the original zip file after its uploaded by setting this to true.
	public $deleteZipAfterUploading = false;

	// The Evoluted Directory Listing Script uses Bootstrap. By setting this value to true, a nicer theme will be loaded remotely.
	// Setting this to false will make the directory listing script use the default bootstrap style, loaded locally.
	public $enableTheme = true;

	// Set to true to require a password be entered before being able to use the script
	public $passwordProtect = false;

	// The password to require to use this script (only used if $passwordProtect is set to true)
	public $password = 'password';

	// Optional. Allow restricted access only to whitelisted IP addresses
	public $enableIpWhitelist = false;

	// List of IP's to allow access to the script (only used if $enableIpWhitelist is true)
	public $ipWhitelist = [
		'127.0.0.1'
	];

	// File extensions to block from showing in the directory listing
	public $ignoredFileExtensions = [
		'php',
		'ini',
	];

	// File names to block from showing in the directory listing
	public $ignoredFileNames = [
		'.htaccess',
		'.DS_Store',
		'Thumbs.db',
	];

	// Directories to block from showing in the directory listing - Important: This only blocks directories from the root file listing. If the directories are subdirectories you want blocked, put them in the $ignoredFileNames array above!
	public $ignoredDirectories = [

	];

	// Files that begin with a dot are usually hidden files. Set this to false if you wish to show these hiden files.
	public $ignoreDotFiles = true;

	// Works the same way as $ignoreDotFiles but with directories.
	public $ignoreDotDirectories = true;
	
	// Sets whether the filter form is shown to the user
	public $enableFilterForm = true;

	/*
	====================================================================================================
	You shouldn't need to edit anything below this line unless you wish to add functionality to the
	script. You should only edit this area if you know what you are doing!
	====================================================================================================
	*/
	private $__previewMimeTypes = [
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/bmp'
	];

	private $__currentDirectory = null;

	private $__fileList = [];

	private $__directoryList = [];

	private $__debug = true;

	public $sortBy = 'name';

	public $filterBy = null;

	public $sortableFields = [
		'name',
		'size',
		'modified'
	];

	private $__sortOrder = 'asc';

	public function __construct()
	{
		define('DS', '/');
	}

	/**
	 * Runs checks and displays files
	 *
	 * @return array
	 */
	public function run(): array
	{
		if ($this->enableIpWhitelist) {
			$this->__ipWhitelistCheck();
		}

		$this->__currentDirectory = $this->startDirectory;

		// Sorting
		if (isset($_GET['order']) && in_array($_GET['order'], $this->sortableFields)) {
			$this->sortBy = $_GET['order'];
		}

		if (isset($_GET['sort']) && ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc')) {
			$this->__sortOrder = $_GET['sort'];
		}

		if (isset($_GET['dir'])) {
			if (isset($_GET['delete']) && $this->enableDirectoryDeletion) {
				$this->deleteDirectory();
			}
			$this->__currentDirectory = $_GET['dir'];

			return $this->__display();
		} elseif (isset($_GET['preview'])) {
			$this->__generatePreview($_GET['preview']);
		} else {
			return $this->__display();
		}
	}

	/**
	 * Checks the password and sets session to logged in if valid
	 */
	public function login(): void
	{
		$password = htmlspecialchars($_POST['password']);

		if ($password === $this->password) {
			$_SESSION['evdir_loggedin'] = true;
			unset($_SESSION['evdir_loginfail']);
		} else {
			$_SESSION['evdir_loginfail'] = true;
			unset($_SESSION['evdir_loggedin']);
		}
	}

	/**
	 * Checks if file uploads are allowed and sends all files to be uploaded
	 *
	 * @return array
	 */
	public function upload(): array
	{
		$files = $this->__formatUploadArray($_FILES['upload']);

		if (!$this->enableUploads) {
			return false;
		}
		if (!$this->enableMultiFileUploads) {
			$files = $files[0];
		}
		$status = array_map(function ($file) {
			return $this->__processUpload($file);
		}, $files);

		return $status;
	}

	/**
	 * Reformats the array to make each file an index
	 *
	 * @param  array  $files
	 * @return array
	 */
	private function __formatUploadArray(array $files): array
	{
		$fileAry = [];
		$fileCount = count($files['name']);
		$fileKeys = array_keys($files);

		for ($i = 0; $i < $fileCount; $i++) {
			foreach ($fileKeys as $key) {
				$fileAry[$i][$key] = $files[$key][$i];
			}
		}

		return $fileAry;
	}

	/**
	 * Processes the file and saves it
	 *
	 * @param  array  $file
	 * @return int
	 */
	private function __processUpload(array $file): int
	{
		if (isset($_GET['dir'])) {
			$this->__currentDirectory = $_GET['dir'];
		}

		if (!$this->__currentDirectory) {
			$filePath = realpath($this->startDirectory);
		} else {
			$this->__currentDirectory = str_replace('..', '', $this->__currentDirectory);
			$this->__currentDirectory = ltrim($this->__currentDirectory, "/");
			$filePath = realpath($this->__currentDirectory);
		}

		$filePath = $filePath . DS . $file['name'];

		if (empty($file)) {
			return false;
		}

		if (!$this->overwriteOnUpload && file_exists($filePath)) {
			return 2;
		}

		if (!in_array(mime_content_type($file['tmp_name']), $this->allowedUploadMimeTypes)) {
			return 3;
		}

		move_uploaded_file($file['tmp_name'], $filePath);

		if (mime_content_type($filePath) == 'application/zip' && $this->enableUnzipping && class_exists('ZipArchive')) {

			$zip = new ZipArchive;
			$result = $zip->open($filePath);
			$zip->extractTo(realpath($this->__currentDirectory));
			$zip->close();

			if ($this->deleteZipAfterUploading) {
				// Delete the zip file
				unlink($filePath);
			}
		}

		return true;
	}

	/**
	 * Gets the file path and deletes it
	 *
	 * @return bool
	 */
	public function deleteFile(): bool
	{
		if (!isset($_GET['deleteFile'])) {
			return true;
		}
		$file = $_GET['deleteFile'];

		// Clean file path
		$file = str_replace('..', '', $file);
		$file = ltrim($file, "/");

		// Work out full file path
		$filePath = __DIR__ . $this->__currentDirectory . '/' . $file;

		if (file_exists($filePath) && is_file($filePath)) {
			return unlink($filePath);
		}

		return false;
	}

	/**
	 * Gets the directory path and deletes everything inside of it
	 */
	public function deleteDirectory()
	{
		if (!isset($_GET['dir'])) {
			return false;
		}

		$dir = $_GET['dir'];
		// Clean dir path
		$dir = str_replace('..', '', $dir);
		$dir = ltrim($dir, "/");

		// Work out full directory path
		$dirPath = __DIR__ . '/' . $dir;

		if (file_exists($dirPath) && is_dir($dirPath)) {

			$iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

			foreach ($files as $file) {
				if ($file->isDir()) {
					rmdir($file->getRealPath());
				} else {
					unlink($file->getRealPath());
				}
			}

			return rmdir($dir);
		}
	}

	/**
	 * Tidies directory name to be valid and creates it
	 *
	 * @return bool
	 */
	public function createDirectory(): bool
	{
		if (!$this->enableDirectoryCreation) {
			return false;
		}

		$directoryName = $_POST['directory'];

		// Convert spaces
		$directoryName = str_replace(' ', '_', $directoryName);

		// Clean up formatting
		$directoryName = preg_replace('/[^\w-_]/', '', $directoryName);

		if (isset($_GET['dir'])) {
			$this->__currentDirectory = $_GET['dir'];
		}

		if (!$this->__currentDirectory) {
			$filePath = realpath($this->startDirectory);
		} else {
			$this->__currentDirectory = str_replace('..', '', $this->__currentDirectory);
			$filePath = realpath($this->__currentDirectory);
		}

		$filePath .= DS . strtolower($directoryName);

		if (file_exists($filePath)) {
			return false;
		}

		return mkdir($filePath, 0755);
	}

	/**
	 * Sets the filterBy variable to the filter
	 */
	public function filterBy(): void
	{
		$filter = $_GET['filter'];
		$this->filterBy = $filter;
	}

	/**
	 * Sorts the list from the information from the url
	 *
	 * @param  string $sort
	 * @return string
	 */
	public function sortUrl(string $sort): string
	{
		// Get current URL parts
		$urlParts = parse_url($_SERVER['REQUEST_URI']);

		$url = '';

		if (isset($urlParts['scheme'])) {
			$url = $urlParts['scheme'] . '://';
		}

		if (isset($urlParts['host'])) {
			$url .= $urlParts['host'];
		}

		if (isset($urlParts['path'])) {
			$url .= $urlParts['path'];
		}

		// Extract query string
		if (isset($urlParts['query'])) {
			$queryString = $urlParts['query'];

			parse_str($queryString, $queryParts);

			// work out if we're already sorting by the current heading
			if (isset($queryParts['order']) && $queryParts['order'] === $sort) {
				// Yes we are, just switch the sort option!
				if (isset($queryParts['sort'])) {
					if ($queryParts['sort'] == 'asc') {
						$queryParts['sort'] = 'desc';
					} else {
						$queryParts['sort'] = 'asc';
					}
				}
			} else {
				$queryParts['order'] = $sort;
				$queryParts['sort'] = 'asc';
			}

			// Now convert back to a string
			$queryString = http_build_query($queryParts);

			$url .= '?' . $queryString;
		} else {
			$order = 'asc';
			if ($sort === $this->sortBy) {
				$order = 'desc';
			}
			$queryString = 'order=' . $sort . '&sort=' . $order;
			$url .= '?' . $queryString;
		}

		return $url;
	}

	/**
	 * Changes class based ascending or descending
	 *
	 * @param  string $sort
	 * @return string
	 */
	public function sortClass(string $sort): string
	{
		$class = $sort . '_';

		if ($this->sortBy === $sort) {
			$class .= $this->__sortOrder === 'desc' ?  'desc sort_desc' : 'asc sort_asc';
		} else {
			$class = '';
		}

		return $class;
	}

	/**
	 * If IP isn't in whitelist redirects to forbidden
	 */
	private function __ipWhitelistCheck(): void
	{
		// Get the users ip
		$userIp = $_SERVER['REMOTE_ADDR'];

		if (!in_array($userIp, $this->ipWhitelist)) {
			header('HTTP/1.0 403 Forbidden');
			die('Your IP address (' . $userIp . ') is not authorized to access this file.');
		}
	}

	/**
	 * Loads the current directory
	 *
	 * @return array
	 */
	private function __display(): array
	{
		if ($this->__currentDirectory !== '.' && !$this->__endsWith($this->__currentDirectory, DS)) {
			$this->__currentDirectory = $this->__currentDirectory . DS;
		}

		return $this->__loadDirectory($this->__currentDirectory);
	}

	/**
	 * Gets all the files in a directory and display them
	 *
	 * @param  string $path
	 * @return array
	 */
	private function __loadDirectory(string $path): array
	{
		$files = $this->__scanDir($path);
		if (!empty($files)) {
			// Strip excludes files, directories and filetypes
			$files = $this->__cleanFileList($files);
			foreach ($files as $file) {
				if (!empty($this->filterBy) && strpos(strtolower($file), strtolower($this->filterBy)) === false) {
					continue;
				}
				$filePath = realpath($this->__currentDirectory . DS . $file);

				if ($this->__isDirectory($filePath)) {

					if ($this->includeUrl) {
						$dirUrl = $this->directoryUrl;
					} else {
						$urlParts = parse_url($_SERVER['REQUEST_URI']);

						$dirUrl = '';

						if (isset($urlParts['scheme'])) {
							$dirUrl = $urlParts['scheme'] . '://';
						}

						if (isset($urlParts['host'])) {
							$dirUrl .= $urlParts['host'];
						}

						if (isset($urlParts['path'])) {
							$dirUrl .= $urlParts['path'];
						}
					}

					if ($this->__currentDirectory !== '' && $this->__currentDirectory !== '.') {
						$dirUrl .= '?dir=' . rawurlencode($this->__currentDirectory) . rawurlencode($file);
					} else {
						$dirUrl .= '?dir=' . rawurlencode($file);
					}

					$this->__directoryList[$file] = [
						'name' => rawurldecode($file),
						'path' => $filePath,
						'type' => 'dir',
						'url' => $dirUrl
					];
				} else {
					$this->__fileList[$file] = $this->__getFileType($filePath, $this->__currentDirectory . DS . $file);
				}
			}
		}

		if (!$this->showSubDirectories) {
			$this->__directoryList = null;
		}

		$data = [
			'currentPath' => $this->__currentDirectory,
			'directoryTree' => $this->__getDirectoryTree(),
			'files' => $this->__setSorting($this->__fileList),
			'directories' => $this->__directoryList,
			'requirePassword' => $this->passwordProtect,
			'enableUploads' => $this->enableUploads
		];

		return $data;
	}

	/**
	 * Sorts the $data based on sortBy and sortOrder
	 *
	 * @param  array  $data
	 * @return array  $data  sorted data
	 */
	private function __setSorting(array $data): array
	{
		// Sort the files
		if ($this->sortBy === 'name') {
			usort($data, function ($a, $b) {
				return strnatcasecmp($a['name'], $b['name']);
			});
		} elseif ($this->sortBy === 'size') {
			usort($data, function ($a, $b) {
				return strnatcasecmp($a['size_bytes'], $b['size_bytes']);
			});
		} elseif ($this->sortBy === 'modified') {
			usort($data, function ($a, $b) {
				return strnatcasecmp($a['modified'], $b['modified']);
			});
		}

		if ($this->__sortOrder === 'desc') {
			$data = array_reverse($data);
		}
		return $data;
	}

	/**
	 * Scans over the directory and only returns if valid files
	 *
	 * @param  string      $dir
	 * @return bool|array
	 */
	private function __scanDir(string $dir)
	{
		// Prevent browsing up the directory path.
		if (strstr($dir, '../')) {
			return false;
		}

		if ($dir === '/') {
			$dir = $this->startDirectory;
			$this->__currentDirectory = $dir;
		}

		$strippedDir = str_replace('/', '', $dir);

		$dir = ltrim($dir, "/");

		// Prevent listing blacklisted directories
		if (in_array($strippedDir, $this->ignoredDirectories)) {
			return false;
		}

		if (!file_exists($dir) || !is_dir($dir)) {
			return false;
		}

		//return scandir($dir);
		//Better human ordering - NPO 8/27/2020
		$listing = scandir($dir);
		sort($listing, SORT_NATURAL | SORT_FLAG_CASE);
		return $listing;
	}

	/**
	 * Removes invalid directories from the list of files
	 *
	 * @param  array  $files  All the files
	 * @return array  $files
	 */
	private function __cleanFileList(array $files): array
	{
		$this->ignoredDirectories[] = '.';
		$this->ignoredDirectories[] = '..';
		foreach ($files as $key => $file) {

			// Remove unwanted directories
			if ($this->__isDirectory(realpath($file)) && in_array($file, $this->ignoredDirectories)) {
				unset($files[$key]);
			}

			// Remove dot directories (if enables)
			if ($this->ignoreDotDirectories && substr($file, 0, 1) === '.') {
				unset($files[$key]);
			}

			// Remove unwanted files
			if (!$this->__isDirectory(realpath($file)) && in_array($file, $this->ignoredFileNames)) {
				unset($files[$key]);
			}
			// Remove unwanted file extensions
			if (!$this->__isDirectory(realpath($file))) {

				$info = pathinfo(mb_convert_encoding($file, 'UTF-8', 'UTF-8'));

				if (isset($info['extension'])) {
					$extension = $info['extension'];

					if (in_array($extension, $this->ignoredFileExtensions)) {
						unset($files[$key]);
					}
				}

				// If dot files want ignoring, do that next
				if ($this->ignoreDotFiles && substr($file, 0, 1) === '.') {
					unset($files[$key]);
				}
			}
		}
		return $files;
	}

	/**
	 * Checks if the file is a directory
	 *
	 * @param  string  $file File name
	 * @return bool
	 */
	private function __isDirectory(string $file): bool
	{
		if ($file === $this->__currentDirectory . DS . '.' || $file === $this->__currentDirectory . DS . '..') {
			return true;
		}
		$file = mb_convert_encoding($file, 'UTF-8', 'UTF-8');

		if (filetype($file) === 'dir') {
			return true;
		}

		return false;
	}

	/**
	 * Returns the formatted array of file data used for the directory listing.
	 *
	 * @param   string|null        $filePath      Full path to the file
	 * @param   array|string|null  $relativePath  Array of data for the file
	 * @return  array
	 */
	private function __getFileType(?string $filePath, $relativePath = null): array
	{
		$fi = new finfo(FILEINFO_MIME_TYPE);

		if (!file_exists($filePath)) {
			return false;
		}

		$type = $fi->file($filePath);

		$filePathInfo = pathinfo($filePath);

		$fileSize = filesize($filePath);

		$fileModified = filemtime($filePath);

		$filePreview = false;

		// Check if the file type supports previews
		if ($this->__supportsPreviews($type) && $this->showThumbnails) {
			$filePreview = true;
		}

		return [
			'name' => $filePathInfo['basename'],
			'extension' => (isset($filePathInfo['extension']) ? $filePathInfo['extension'] : null),
			'dir' => $filePathInfo['dirname'],
			'path' => $filePath,
			'relativePath' => $relativePath,
			'size' => $this->__formatSize($fileSize),
			'size_bytes' => $fileSize,
			'modified' => $fileModified,
			'type' => 'file',
			'mime' => $type,
			'url' => $this->__getUrl($filePathInfo['basename']),
			'preview' => $filePreview,
			'target' => ($this->openLinksInNewTab ? '_blank' : '_parent')
		];
	}

	/**
	 * Checks if the mimetype is allowed a preview
	 *
	 * @param  string  $type The mimeype of the file
	 * @return bool
	 */
	private function __supportsPreviews(string $type): bool
	{
		if (in_array($type, $this->__previewMimeTypes)) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the url to the file.
	 *
	 * @param  string $file filename
	 * @return string
	 */
	private function __getUrl(string $file): string
	{
		if ($this->includeUrl) {
			$dirUrl = $this->directoryUrl;
		} else {
			$dirUrl = $_SERVER['REQUEST_URI'];

			$urlParts = parse_url($_SERVER['REQUEST_URI']);

			$dirUrl = '';

			if (isset($urlParts['scheme'])) {
				$dirUrl = $urlParts['scheme'] . '://';
			}

			if (isset($urlParts['host'])) {
				$dirUrl .= $urlParts['host'];
			}

			if (isset($urlParts['path'])) {
				$dirUrl .= $urlParts['path'];
			}
		}

		if ($this->__currentDirectory !== '.') {
			$dirUrl = $dirUrl . $this->__currentDirectory;
		}
		return $dirUrl . rawurlencode($file);
	}

	/**
	 * Gets the tree for the currect directory
	 */
	private function __getDirectoryTree(): array
	{
		$dirString = $this->__currentDirectory;
		$directoryTree = [];

		$directoryTree['./'] = 'Index';

		if (substr_count($dirString, '/') >= 0) {
			$items = explode("/", $dirString);
			$items = array_filter($items);
			$path = '';
			foreach ($items as $item) {
				if ($item === '.' || $item === '..') {
					continue;
				}
				$path .= rawurlencode($item) . '/';
				$directoryTree[$path] = $item;
			}
		}
		$directoryTree = array_filter($directoryTree);

		return $directoryTree;
	}

	/**
	 * Checks if the needle is at the end of the haystack
	 *
	 * @param  string $haystack
	 * @param  string $needle
	 * @return bool
	 */
	private function __endsWith(string $haystack,string $needle): bool
	{
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
	}

	/**
	 * Checks mimetype and generates a corresponding preview
	 *
	 * @param string $filePath
	 */
	private function __generatePreview(string $filePath): void
	{
		$file = $this->__getFileType($filePath);

		if ($file['mime'] === 'image/jpeg') {
			$image = imagecreatefromjpeg($file['path']);
		} elseif ($file['mime'] === 'image/png') {
			$image = imagecreatefrompng($file['path']);
		} elseif ($file['mime'] === 'image/gif') {
			$image = imagecreatefromgif($file['path']);
		} else {
			die();
		}

		$oldX = imageSX($image);
		$oldY = imageSY($image);

		$newW = 250;
		$newH = 250;

		if ($oldX > $oldY) {
			$thumbW = $newW;
			$thumbH = $oldY * ($newH / $oldX);
		}
		if ($oldX < $oldY) {
			$thumbW = $oldX * ($newW / $oldY);
			$thumbH = $newH;
		}
		if ($oldX == $oldY) {
			$thumbW = $newW;
			$thumbH = $newW;
		}

		header('Content-Type: ' . $file['mime']);

		$newImg = ImageCreateTrueColor($thumbW, $thumbH);

		imagecopyresampled($newImg, $image, 0, 0, 0, 0, $thumbW, $thumbH, $oldX, $oldY);

		if ($file['mime'] === 'image/jpeg') {
			imagejpeg($newImg);
		} elseif ($file['mime'] === 'image/png') {
			imagepng($newImg);
		} elseif ($file['mime'] === 'image/gif') {
			imagegif($newImg);
		}
		imagedestroy($newImg);
		die();
	}

	/**
	 * Converts bytes to human readable
	 *
	 * @param  int    $bytes
	 * @return string
	 */
	private function __formatSize(int $bytes): string
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, 2) . ' ' . $units[$pow];
	}

}

$listing = new DirectoryListing();

$successMsg = null;
$errorMsg = null;

if (isset($_POST['password'])) {
	$listing->login();

	if (isset($_SESSION['evdir_loginfail'])) {
		$errorMsg = 'Login Failed! Please check you entered the correct password an try again.';
		unset($_SESSION['evdir_loginfail']);
	}

} elseif (isset($_FILES['upload'])) {
	$uploadStatus = $listing->upload();
	if ($uploadStatus === 1) {
		$successMsg = 'Your file was successfully uploaded!';
	} elseif ($uploadStatus === 2) {
		$errorMsg = 'Your file could not be uploaded. A file with that name already exists.';
	} elseif ($uploadStatus === 3) {
		$errorMsg = 'Your file could not be uploaded as the file type is blocked.';
	}
} elseif (isset($_POST['directory'])) {
	if ($listing->createDirectory()) {
		$successMsg = 'Directory Created!';
	} else {
		$errorMsg = 'There was a problem creating your directory.';
	}
} elseif (isset($_GET['deleteFile']) && $listing->enableFileDeletion) {
	if ($listing->deleteFile()) {
		$successMsg = 'The file was successfully deleted!';
	} else {
		$errorMsg = 'The selected file could not be deleted. Please check your file permissions and try again.';
	}
} elseif (isset($_GET['dir']) && isset($_GET['delete']) && $listing->enableDirectoryDeletion) {
	if ($listing->deleteDirectory()) {
		$successMsg = 'The directory was successfully deleted!';
		unset($_GET['dir']);
	} else {
		$errorMsg = 'The selected directory could not be deleted. Please check your file permissions and try again.';
	}
} elseif (isset($_GET['filter'])) {
	$listing->filterBy();
}

$data = $listing->run();

function pr($data, $die = false)
{
	echo '<pre>';
	print_r($data);
	echo '</pre>';

	if ($die) {
		die();
	}
}
?>

<html>
<head>
	<title>Directory Listing of <?= $data['currentPath'] . (!empty($listing->pageTitle) ? ' (' . $listing->pageTitle . ')' : null); ?></title>
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; minimum-scale=1.0; user-scalable=no; target-densityDpi=device-dpi" />
	<meta charset="UTF-8">
	<style>
html {
	font-family: sans-serif;
	-ms-text-size-adjust: 100%;
	-webkit-text-size-adjust: 100%
}

body {
	margin: 0
}

article,
aside,
details,
figcaption,
figure,
footer,
header,
hgroup,
main,
menu,
nav,
section,
summary {
	display: block
}

audio,
canvas,
progress,
video {
	display: inline-block;
	vertical-align: baseline
}

audio:not([controls]) {
	display: none;
	height: 0
}

[hidden],
template {
	display: none
}

a {
	background-color: transparent
}

a:active,
a:hover {
	outline: 0
}

abbr[title] {
	border-bottom: 1px dotted
}

b,
strong {
	font-weight: bold
}

dfn {
	font-style: italic
}

h1 {
	font-size: 2em;
	margin: 0.67em 0
}

mark {
	background: #ff0;
	color: #000
}

small {
	font-size: 80%
}

sub,
sup {
	font-size: 75%;
	line-height: 0;
	position: relative;
	vertical-align: baseline
}

sup {
	top: -0.5em
}

sub {
	bottom: -0.25em
}

img {
	border: 0
}

svg:not(:root) {
	overflow: hidden
}

figure {
	margin: 1em 40px
}

hr {
	-webkit-box-sizing: content-box;
	-moz-box-sizing: content-box;
	box-sizing: content-box;
	height: 0
}

pre {
	overflow: auto
}

code,
kbd,
pre,
samp {
	font-family: monospace, monospace;
	font-size: 1em
}

button,
input,
optgroup,
select,
textarea {
	color: inherit;
	font: inherit;
	margin: 0
}

button {
	overflow: visible
}

button,
select {
	text-transform: none
}

button,
html input[type="button"],
input[type="reset"],
input[type="submit"] {
	-webkit-appearance: button;
	cursor: pointer
}

button[disabled],
html input[disabled] {
	cursor: default
}

button::-moz-focus-inner,
input::-moz-focus-inner {
	border: 0;
	padding: 0
}

input {
	line-height: normal
}

input[type="checkbox"],
input[type="radio"] {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
	padding: 0
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
	height: auto
}

input[type="search"] {
	-webkit-appearance: textfield;
	-webkit-box-sizing: content-box;
	-moz-box-sizing: content-box;
	box-sizing: content-box
}

input[type="search"]::-webkit-search-cancel-button,
input[type="search"]::-webkit-search-decoration {
	-webkit-appearance: none
}

fieldset {
	border: 1px solid #c0c0c0;
	margin: 0 2px;
	padding: 0.35em 0.625em 0.75em
}

legend {
	border: 0;
	padding: 0
}

textarea {
	overflow: auto
}

optgroup {
	font-weight: bold
}

table {
	border-collapse: collapse;
	border-spacing: 0
}

td,
th {
	padding: 0
}

* {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box
}

*:before,
*:after {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box
}

html {
	font-size: 10px;
	-webkit-tap-highlight-color: rgba(0, 0, 0, 0)
}

body {
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-size: 14px;
	line-height: 1.42857143;
	color: #333;
	background-color: #fff
}

input,
button,
select,
textarea {
	font-family: inherit;
	font-size: inherit;
	line-height: inherit
}

a {
	color: #337ab7;
	text-decoration: none
}

a:hover,
a:focus {
	color: #23527c;
	text-decoration: underline
}

a:focus {
	outline: thin dotted;
	outline: 5px auto -webkit-focus-ring-color;
	outline-offset: -2px
}

figure {
	margin: 0
}

img {
	vertical-align: middle
}

.img-responsive,
.thumbnail>img,
.thumbnail a>img {
	display: block;
	max-width: 100%;
	height: auto
}

.img-rounded {
	border-radius: 6px
}

.img-thumbnail {
	padding: 4px;
	line-height: 1.42857143;
	background-color: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	-webkit-transition: all .2s ease-in-out;
	-o-transition: all .2s ease-in-out;
	transition: all .2s ease-in-out;
	display: inline-block;
	max-width: 100%;
	height: auto
}

.img-circle {
	border-radius: 50%
}

hr {
	margin-top: 20px;
	margin-bottom: 20px;
	border: 0;
	border-top: 1px solid #eee
}

.sr-only {
	position: absolute;
	width: 1px;
	height: 1px;
	margin: -1px;
	padding: 0;
	overflow: hidden;
	clip: rect(0, 0, 0, 0);
	border: 0
}

.sr-only-focusable:active,
.sr-only-focusable:focus {
	position: static;
	width: auto;
	height: auto;
	margin: 0;
	overflow: visible;
	clip: auto
}

[role="button"] {
	cursor: pointer
}

h1,
h2,
h3,
h4,
h5,
h6,
.h1,
.h2,
.h3,
.h4,
.h5,
.h6 {
	font-family: inherit;
	font-weight: 500;
	line-height: 1.1;
	color: inherit
}

h1 small,
h2 small,
h3 small,
h4 small,
h5 small,
h6 small,
.h1 small,
.h2 small,
.h3 small,
.h4 small,
.h5 small,
.h6 small,
h1 .small,
h2 .small,
h3 .small,
h4 .small,
h5 .small,
h6 .small,
.h1 .small,
.h2 .small,
.h3 .small,
.h4 .small,
.h5 .small,
.h6 .small {
	font-weight: normal;
	line-height: 1;
	color: #777
}

h1,
.h1,
h2,
.h2,
h3,
.h3 {
	margin-top: 20px;
	margin-bottom: 10px
}

h1 small,
.h1 small,
h2 small,
.h2 small,
h3 small,
.h3 small,
h1 .small,
.h1 .small,
h2 .small,
.h2 .small,
h3 .small,
.h3 .small {
	font-size: 65%
}

h4,
.h4,
h5,
.h5,
h6,
.h6 {
	margin-top: 10px;
	margin-bottom: 10px
}

h4 small,
.h4 small,
h5 small,
.h5 small,
h6 small,
.h6 small,
h4 .small,
.h4 .small,
h5 .small,
.h5 .small,
h6 .small,
.h6 .small {
	font-size: 75%
}

h1,
.h1 {
	font-size: 36px
}

h2,
.h2 {
	font-size: 30px
}

h3,
.h3 {
	font-size: 24px
}

h4,
.h4 {
	font-size: 18px
}

h5,
.h5 {
	font-size: 14px
}

h6,
.h6 {
	font-size: 12px
}

p {
	margin: 0 0 10px
}

.lead {
	margin-bottom: 20px;
	font-size: 16px;
	font-weight: 300;
	line-height: 1.4
}

@media (min-width:768px) {
	.lead {
		font-size: 21px
	}
}

small,
.small {
	font-size: 85%
}

mark,
.mark {
	background-color: #fcf8e3;
	padding: .2em
}

.text-left {
	text-align: left
}

.text-right {
	text-align: right
}

.text-center {
	text-align: center
}

.text-justify {
	text-align: justify
}

.text-nowrap {
	white-space: nowrap
}

.text-lowercase {
	text-transform: lowercase
}

.text-uppercase {
	text-transform: uppercase
}

.text-capitalize {
	text-transform: capitalize
}

.text-muted {
	color: #777
}

.text-primary {
	color: #337ab7
}

a.text-primary:hover,
a.text-primary:focus {
	color: #286090
}

.text-success {
	color: #3c763d
}

a.text-success:hover,
a.text-success:focus {
	color: #2b542c
}

.text-info {
	color: #31708f
}

a.text-info:hover,
a.text-info:focus {
	color: #245269
}

.text-warning {
	color: #8a6d3b
}

a.text-warning:hover,
a.text-warning:focus {
	color: #66512c
}

.text-danger {
	color: #a94442
}

a.text-danger:hover,
a.text-danger:focus {
	color: #843534
}

.bg-primary {
	color: #fff;
	background-color: #337ab7
}

a.bg-primary:hover,
a.bg-primary:focus {
	background-color: #286090
}

.bg-success {
	background-color: #dff0d8
}

a.bg-success:hover,
a.bg-success:focus {
	background-color: #c1e2b3
}

.bg-info {
	background-color: #d9edf7
}

a.bg-info:hover,
a.bg-info:focus {
	background-color: #afd9ee
}

.bg-warning {
	background-color: #fcf8e3
}

a.bg-warning:hover,
a.bg-warning:focus {
	background-color: #f7ecb5
}

.bg-danger {
	background-color: #f2dede
}

a.bg-danger:hover,
a.bg-danger:focus {
	background-color: #e4b9b9
}

.page-header {
	padding-bottom: 9px;
	margin: 40px 0 20px;
	border-bottom: 1px solid #eee
}

ul,
ol {
	margin-top: 0;
	margin-bottom: 10px
}

ul ul,
ol ul,
ul ol,
ol ol {
	margin-bottom: 0
}

.list-unstyled {
	padding-left: 0;
	list-style: none
}

.list-inline {
	padding-left: 0;
	list-style: none;
	margin-left: -5px
}

.list-inline>li {
	display: inline-block;
	padding-left: 5px;
	padding-right: 5px
}

dl {
	margin-top: 0;
	margin-bottom: 20px
}

dt,
dd {
	line-height: 1.42857143
}

dt {
	font-weight: bold
}

dd {
	margin-left: 0
}

@media (min-width:768px) {
	.dl-horizontal dt {
		float: left;
		width: 160px;
		clear: left;
		text-align: right;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap
	}
	.dl-horizontal dd {
		margin-left: 180px
	}
}

abbr[title],
abbr[data-original-title] {
	cursor: help;
	border-bottom: 1px dotted #777
}

.initialism {
	font-size: 90%;
	text-transform: uppercase
}

blockquote {
	padding: 10px 20px;
	margin: 0 0 20px;
	font-size: 17.5px;
	border-left: 5px solid #eee
}

blockquote p:last-child,
blockquote ul:last-child,
blockquote ol:last-child {
	margin-bottom: 0
}

blockquote footer,
blockquote small,
blockquote .small {
	display: block;
	font-size: 80%;
	line-height: 1.42857143;
	color: #777
}

blockquote footer:before,
blockquote small:before,
blockquote .small:before {
	content: '\2014 \00A0'
}

.blockquote-reverse,
blockquote.pull-right {
	padding-right: 15px;
	padding-left: 0;
	border-right: 5px solid #eee;
	border-left: 0;
	text-align: right
}

.blockquote-reverse footer:before,
blockquote.pull-right footer:before,
.blockquote-reverse small:before,
blockquote.pull-right small:before,
.blockquote-reverse .small:before,
blockquote.pull-right .small:before {
	content: ''
}

.blockquote-reverse footer:after,
blockquote.pull-right footer:after,
.blockquote-reverse small:after,
blockquote.pull-right small:after,
.blockquote-reverse .small:after,
blockquote.pull-right .small:after {
	content: '\00A0 \2014'
}

address {
	margin-bottom: 20px;
	font-style: normal;
	line-height: 1.42857143
}

.container {
	margin-right: auto;
	margin-left: auto;
	padding-left: 15px;
	padding-right: 15px
}

@media (min-width:768px) {
	.container {
		width: 750px
	}
}

@media (min-width:992px) {
	.container {
		width: 970px
	}
}

@media (min-width:1200px) {
	.container {
		width: 1170px
	}
}

.container-fluid {
	margin-right: auto;
	margin-left: auto;
	padding-left: 15px;
	padding-right: 15px
}

.row {
	margin-left: -15px;
	margin-right: -15px
}

.col-xs-1,
.col-sm-1,
.col-md-1,
.col-lg-1,
.col-xs-2,
.col-sm-2,
.col-md-2,
.col-lg-2,
.col-xs-3,
.col-sm-3,
.col-md-3,
.col-lg-3,
.col-xs-4,
.col-sm-4,
.col-md-4,
.col-lg-4,
.col-xs-5,
.col-sm-5,
.col-md-5,
.col-lg-5,
.col-xs-6,
.col-sm-6,
.col-md-6,
.col-lg-6,
.col-xs-7,
.col-sm-7,
.col-md-7,
.col-lg-7,
.col-xs-8,
.col-sm-8,
.col-md-8,
.col-lg-8,
.col-xs-9,
.col-sm-9,
.col-md-9,
.col-lg-9,
.col-xs-10,
.col-sm-10,
.col-md-10,
.col-lg-10,
.col-xs-11,
.col-sm-11,
.col-md-11,
.col-lg-11,
.col-xs-12,
.col-sm-12,
.col-md-12,
.col-lg-12 {
	position: relative;
	min-height: 1px;
	padding-left: 15px;
	padding-right: 15px
}

.col-xs-1,
.col-xs-2,
.col-xs-3,
.col-xs-4,
.col-xs-5,
.col-xs-6,
.col-xs-7,
.col-xs-8,
.col-xs-9,
.col-xs-10,
.col-xs-11,
.col-xs-12 {
	float: left
}

.col-xs-12 {
	width: 100%
}

.col-xs-11 {
	width: 91.66666667%
}

.col-xs-10 {
	width: 83.33333333%
}

.col-xs-9 {
	width: 75%
}

.col-xs-8 {
	width: 66.66666667%
}

.col-xs-7 {
	width: 58.33333333%
}

.col-xs-6 {
	width: 50%
}

.col-xs-5 {
	width: 41.66666667%
}

.col-xs-4 {
	width: 33.33333333%
}

.col-xs-3 {
	width: 25%
}

.col-xs-2 {
	width: 16.66666667%
}

.col-xs-1 {
	width: 8.33333333%
}

.col-xs-pull-12 {
	right: 100%
}

.col-xs-pull-11 {
	right: 91.66666667%
}

.col-xs-pull-10 {
	right: 83.33333333%
}

.col-xs-pull-9 {
	right: 75%
}

.col-xs-pull-8 {
	right: 66.66666667%
}

.col-xs-pull-7 {
	right: 58.33333333%
}

.col-xs-pull-6 {
	right: 50%
}

.col-xs-pull-5 {
	right: 41.66666667%
}

.col-xs-pull-4 {
	right: 33.33333333%
}

.col-xs-pull-3 {
	right: 25%
}

.col-xs-pull-2 {
	right: 16.66666667%
}

.col-xs-pull-1 {
	right: 8.33333333%
}

.col-xs-pull-0 {
	right: auto
}

.col-xs-push-12 {
	left: 100%
}

.col-xs-push-11 {
	left: 91.66666667%
}

.col-xs-push-10 {
	left: 83.33333333%
}

.col-xs-push-9 {
	left: 75%
}

.col-xs-push-8 {
	left: 66.66666667%
}

.col-xs-push-7 {
	left: 58.33333333%
}

.col-xs-push-6 {
	left: 50%
}

.col-xs-push-5 {
	left: 41.66666667%
}

.col-xs-push-4 {
	left: 33.33333333%
}

.col-xs-push-3 {
	left: 25%
}

.col-xs-push-2 {
	left: 16.66666667%
}

.col-xs-push-1 {
	left: 8.33333333%
}

.col-xs-push-0 {
	left: auto
}

.col-xs-offset-12 {
	margin-left: 100%
}

.col-xs-offset-11 {
	margin-left: 91.66666667%
}

.col-xs-offset-10 {
	margin-left: 83.33333333%
}

.col-xs-offset-9 {
	margin-left: 75%
}

.col-xs-offset-8 {
	margin-left: 66.66666667%
}

.col-xs-offset-7 {
	margin-left: 58.33333333%
}

.col-xs-offset-6 {
	margin-left: 50%
}

.col-xs-offset-5 {
	margin-left: 41.66666667%
}

.col-xs-offset-4 {
	margin-left: 33.33333333%
}

.col-xs-offset-3 {
	margin-left: 25%
}

.col-xs-offset-2 {
	margin-left: 16.66666667%
}

.col-xs-offset-1 {
	margin-left: 8.33333333%
}

.col-xs-offset-0 {
	margin-left: 0
}

@media (min-width:768px) {
	.col-sm-1,
	.col-sm-2,
	.col-sm-3,
	.col-sm-4,
	.col-sm-5,
	.col-sm-6,
	.col-sm-7,
	.col-sm-8,
	.col-sm-9,
	.col-sm-10,
	.col-sm-11,
	.col-sm-12 {
		float: left
	}
	.col-sm-12 {
		width: 100%
	}
	.col-sm-11 {
		width: 91.66666667%
	}
	.col-sm-10 {
		width: 83.33333333%
	}
	.col-sm-9 {
		width: 75%
	}
	.col-sm-8 {
		width: 66.66666667%
	}
	.col-sm-7 {
		width: 58.33333333%
	}
	.col-sm-6 {
		width: 50%
	}
	.col-sm-5 {
		width: 41.66666667%
	}
	.col-sm-4 {
		width: 33.33333333%
	}
	.col-sm-3 {
		width: 25%
	}
	.col-sm-2 {
		width: 16.66666667%
	}
	.col-sm-1 {
		width: 8.33333333%
	}
	.col-sm-pull-12 {
		right: 100%
	}
	.col-sm-pull-11 {
		right: 91.66666667%
	}
	.col-sm-pull-10 {
		right: 83.33333333%
	}
	.col-sm-pull-9 {
		right: 75%
	}
	.col-sm-pull-8 {
		right: 66.66666667%
	}
	.col-sm-pull-7 {
		right: 58.33333333%
	}
	.col-sm-pull-6 {
		right: 50%
	}
	.col-sm-pull-5 {
		right: 41.66666667%
	}
	.col-sm-pull-4 {
		right: 33.33333333%
	}
	.col-sm-pull-3 {
		right: 25%
	}
	.col-sm-pull-2 {
		right: 16.66666667%
	}
	.col-sm-pull-1 {
		right: 8.33333333%
	}
	.col-sm-pull-0 {
		right: auto
	}
	.col-sm-push-12 {
		left: 100%
	}
	.col-sm-push-11 {
		left: 91.66666667%
	}
	.col-sm-push-10 {
		left: 83.33333333%
	}
	.col-sm-push-9 {
		left: 75%
	}
	.col-sm-push-8 {
		left: 66.66666667%
	}
	.col-sm-push-7 {
		left: 58.33333333%
	}
	.col-sm-push-6 {
		left: 50%
	}
	.col-sm-push-5 {
		left: 41.66666667%
	}
	.col-sm-push-4 {
		left: 33.33333333%
	}
	.col-sm-push-3 {
		left: 25%
	}
	.col-sm-push-2 {
		left: 16.66666667%
	}
	.col-sm-push-1 {
		left: 8.33333333%
	}
	.col-sm-push-0 {
		left: auto
	}
	.col-sm-offset-12 {
		margin-left: 100%
	}
	.col-sm-offset-11 {
		margin-left: 91.66666667%
	}
	.col-sm-offset-10 {
		margin-left: 83.33333333%
	}
	.col-sm-offset-9 {
		margin-left: 75%
	}
	.col-sm-offset-8 {
		margin-left: 66.66666667%
	}
	.col-sm-offset-7 {
		margin-left: 58.33333333%
	}
	.col-sm-offset-6 {
		margin-left: 50%
	}
	.col-sm-offset-5 {
		margin-left: 41.66666667%
	}
	.col-sm-offset-4 {
		margin-left: 33.33333333%
	}
	.col-sm-offset-3 {
		margin-left: 25%
	}
	.col-sm-offset-2 {
		margin-left: 16.66666667%
	}
	.col-sm-offset-1 {
		margin-left: 8.33333333%
	}
	.col-sm-offset-0 {
		margin-left: 0
	}
}

@media (min-width:992px) {
	.col-md-1,
	.col-md-2,
	.col-md-3,
	.col-md-4,
	.col-md-5,
	.col-md-6,
	.col-md-7,
	.col-md-8,
	.col-md-9,
	.col-md-10,
	.col-md-11,
	.col-md-12 {
		float: left
	}
	.col-md-12 {
		width: 100%
	}
	.col-md-11 {
		width: 91.66666667%
	}
	.col-md-10 {
		width: 83.33333333%
	}
	.col-md-9 {
		width: 75%
	}
	.col-md-8 {
		width: 66.66666667%
	}
	.col-md-7 {
		width: 58.33333333%
	}
	.col-md-6 {
		width: 50%
	}
	.col-md-5 {
		width: 41.66666667%
	}
	.col-md-4 {
		width: 33.33333333%
	}
	.col-md-3 {
		width: 25%
	}
	.col-md-2 {
		width: 16.66666667%
	}
	.col-md-1 {
		width: 8.33333333%
	}
	.col-md-pull-12 {
		right: 100%
	}
	.col-md-pull-11 {
		right: 91.66666667%
	}
	.col-md-pull-10 {
		right: 83.33333333%
	}
	.col-md-pull-9 {
		right: 75%
	}
	.col-md-pull-8 {
		right: 66.66666667%
	}
	.col-md-pull-7 {
		right: 58.33333333%
	}
	.col-md-pull-6 {
		right: 50%
	}
	.col-md-pull-5 {
		right: 41.66666667%
	}
	.col-md-pull-4 {
		right: 33.33333333%
	}
	.col-md-pull-3 {
		right: 25%
	}
	.col-md-pull-2 {
		right: 16.66666667%
	}
	.col-md-pull-1 {
		right: 8.33333333%
	}
	.col-md-pull-0 {
		right: auto
	}
	.col-md-push-12 {
		left: 100%
	}
	.col-md-push-11 {
		left: 91.66666667%
	}
	.col-md-push-10 {
		left: 83.33333333%
	}
	.col-md-push-9 {
		left: 75%
	}
	.col-md-push-8 {
		left: 66.66666667%
	}
	.col-md-push-7 {
		left: 58.33333333%
	}
	.col-md-push-6 {
		left: 50%
	}
	.col-md-push-5 {
		left: 41.66666667%
	}
	.col-md-push-4 {
		left: 33.33333333%
	}
	.col-md-push-3 {
		left: 25%
	}
	.col-md-push-2 {
		left: 16.66666667%
	}
	.col-md-push-1 {
		left: 8.33333333%
	}
	.col-md-push-0 {
		left: auto
	}
	.col-md-offset-12 {
		margin-left: 100%
	}
	.col-md-offset-11 {
		margin-left: 91.66666667%
	}
	.col-md-offset-10 {
		margin-left: 83.33333333%
	}
	.col-md-offset-9 {
		margin-left: 75%
	}
	.col-md-offset-8 {
		margin-left: 66.66666667%
	}
	.col-md-offset-7 {
		margin-left: 58.33333333%
	}
	.col-md-offset-6 {
		margin-left: 50%
	}
	.col-md-offset-5 {
		margin-left: 41.66666667%
	}
	.col-md-offset-4 {
		margin-left: 33.33333333%
	}
	.col-md-offset-3 {
		margin-left: 25%
	}
	.col-md-offset-2 {
		margin-left: 16.66666667%
	}
	.col-md-offset-1 {
		margin-left: 8.33333333%
	}
	.col-md-offset-0 {
		margin-left: 0
	}
}

@media (min-width:1200px) {
	.col-lg-1,
	.col-lg-2,
	.col-lg-3,
	.col-lg-4,
	.col-lg-5,
	.col-lg-6,
	.col-lg-7,
	.col-lg-8,
	.col-lg-9,
	.col-lg-10,
	.col-lg-11,
	.col-lg-12 {
		float: left
	}
	.col-lg-12 {
		width: 100%
	}
	.col-lg-11 {
		width: 91.66666667%
	}
	.col-lg-10 {
		width: 83.33333333%
	}
	.col-lg-9 {
		width: 75%
	}
	.col-lg-8 {
		width: 66.66666667%
	}
	.col-lg-7 {
		width: 58.33333333%
	}
	.col-lg-6 {
		width: 50%
	}
	.col-lg-5 {
		width: 41.66666667%
	}
	.col-lg-4 {
		width: 33.33333333%
	}
	.col-lg-3 {
		width: 25%
	}
	.col-lg-2 {
		width: 16.66666667%
	}
	.col-lg-1 {
		width: 8.33333333%
	}
	.col-lg-pull-12 {
		right: 100%
	}
	.col-lg-pull-11 {
		right: 91.66666667%
	}
	.col-lg-pull-10 {
		right: 83.33333333%
	}
	.col-lg-pull-9 {
		right: 75%
	}
	.col-lg-pull-8 {
		right: 66.66666667%
	}
	.col-lg-pull-7 {
		right: 58.33333333%
	}
	.col-lg-pull-6 {
		right: 50%
	}
	.col-lg-pull-5 {
		right: 41.66666667%
	}
	.col-lg-pull-4 {
		right: 33.33333333%
	}
	.col-lg-pull-3 {
		right: 25%
	}
	.col-lg-pull-2 {
		right: 16.66666667%
	}
	.col-lg-pull-1 {
		right: 8.33333333%
	}
	.col-lg-pull-0 {
		right: auto
	}
	.col-lg-push-12 {
		left: 100%
	}
	.col-lg-push-11 {
		left: 91.66666667%
	}
	.col-lg-push-10 {
		left: 83.33333333%
	}
	.col-lg-push-9 {
		left: 75%
	}
	.col-lg-push-8 {
		left: 66.66666667%
	}
	.col-lg-push-7 {
		left: 58.33333333%
	}
	.col-lg-push-6 {
		left: 50%
	}
	.col-lg-push-5 {
		left: 41.66666667%
	}
	.col-lg-push-4 {
		left: 33.33333333%
	}
	.col-lg-push-3 {
		left: 25%
	}
	.col-lg-push-2 {
		left: 16.66666667%
	}
	.col-lg-push-1 {
		left: 8.33333333%
	}
	.col-lg-push-0 {
		left: auto
	}
	.col-lg-offset-12 {
		margin-left: 100%
	}
	.col-lg-offset-11 {
		margin-left: 91.66666667%
	}
	.col-lg-offset-10 {
		margin-left: 83.33333333%
	}
	.col-lg-offset-9 {
		margin-left: 75%
	}
	.col-lg-offset-8 {
		margin-left: 66.66666667%
	}
	.col-lg-offset-7 {
		margin-left: 58.33333333%
	}
	.col-lg-offset-6 {
		margin-left: 50%
	}
	.col-lg-offset-5 {
		margin-left: 41.66666667%
	}
	.col-lg-offset-4 {
		margin-left: 33.33333333%
	}
	.col-lg-offset-3 {
		margin-left: 25%
	}
	.col-lg-offset-2 {
		margin-left: 16.66666667%
	}
	.col-lg-offset-1 {
		margin-left: 8.33333333%
	}
	.col-lg-offset-0 {
		margin-left: 0
	}
}

table {
	background-color: transparent
}

caption {
	padding-top: 8px;
	padding-bottom: 8px;
	color: #777;
	text-align: left
}

th {
	text-align: left
}

.table {
	width: 100%;
	max-width: 100%;
	margin-bottom: 20px
}

.table>thead>tr>th,
.table>tbody>tr>th,
.table>tfoot>tr>th,
.table>thead>tr>td,
.table>tbody>tr>td,
.table>tfoot>tr>td {
	padding: 8px;
	line-height: 1.42857143;
	vertical-align: top;
	border-top: 1px solid #ddd
}

.table>thead>tr>th {
	vertical-align: bottom;
	border-bottom: 2px solid #ddd
}

.table>caption+thead>tr:first-child>th,
.table>colgroup+thead>tr:first-child>th,
.table>thead:first-child>tr:first-child>th,
.table>caption+thead>tr:first-child>td,
.table>colgroup+thead>tr:first-child>td,
.table>thead:first-child>tr:first-child>td {
	border-top: 0
}

.table>tbody+tbody {
	border-top: 2px solid #ddd
}

.table .table {
	background-color: #fff
}

.table-condensed>thead>tr>th,
.table-condensed>tbody>tr>th,
.table-condensed>tfoot>tr>th,
.table-condensed>thead>tr>td,
.table-condensed>tbody>tr>td,
.table-condensed>tfoot>tr>td {
	padding: 5px
}

.table-bordered {
	border: 1px solid #ddd
}

.table-bordered>thead>tr>th,
.table-bordered>tbody>tr>th,
.table-bordered>tfoot>tr>th,
.table-bordered>thead>tr>td,
.table-bordered>tbody>tr>td,
.table-bordered>tfoot>tr>td {
	border: 1px solid #ddd
}

.table-bordered>thead>tr>th,
.table-bordered>thead>tr>td {
	border-bottom-width: 2px
}

.table-striped>tbody>tr:nth-of-type(odd) {
	background-color: #f9f9f9
}

.table-hover>tbody>tr:hover {
	background-color: #f5f5f5
}

table col[class*="col-"] {
	position: static;
	float: none;
	display: table-column
}

table td[class*="col-"],
table th[class*="col-"] {
	position: static;
	float: none;
	display: table-cell
}

.table>thead>tr>td.active,
.table>tbody>tr>td.active,
.table>tfoot>tr>td.active,
.table>thead>tr>th.active,
.table>tbody>tr>th.active,
.table>tfoot>tr>th.active,
.table>thead>tr.active>td,
.table>tbody>tr.active>td,
.table>tfoot>tr.active>td,
.table>thead>tr.active>th,
.table>tbody>tr.active>th,
.table>tfoot>tr.active>th {
	background-color: #f5f5f5
}

.table-hover>tbody>tr>td.active:hover,
.table-hover>tbody>tr>th.active:hover,
.table-hover>tbody>tr.active:hover>td,
.table-hover>tbody>tr:hover>.active,
.table-hover>tbody>tr.active:hover>th {
	background-color: #e8e8e8
}

.table>thead>tr>td.success,
.table>tbody>tr>td.success,
.table>tfoot>tr>td.success,
.table>thead>tr>th.success,
.table>tbody>tr>th.success,
.table>tfoot>tr>th.success,
.table>thead>tr.success>td,
.table>tbody>tr.success>td,
.table>tfoot>tr.success>td,
.table>thead>tr.success>th,
.table>tbody>tr.success>th,
.table>tfoot>tr.success>th {
	background-color: #dff0d8
}

.table-hover>tbody>tr>td.success:hover,
.table-hover>tbody>tr>th.success:hover,
.table-hover>tbody>tr.success:hover>td,
.table-hover>tbody>tr:hover>.success,
.table-hover>tbody>tr.success:hover>th {
	background-color: #d0e9c6
}

.table>thead>tr>td.info,
.table>tbody>tr>td.info,
.table>tfoot>tr>td.info,
.table>thead>tr>th.info,
.table>tbody>tr>th.info,
.table>tfoot>tr>th.info,
.table>thead>tr.info>td,
.table>tbody>tr.info>td,
.table>tfoot>tr.info>td,
.table>thead>tr.info>th,
.table>tbody>tr.info>th,
.table>tfoot>tr.info>th {
	background-color: #d9edf7
}

.table-hover>tbody>tr>td.info:hover,
.table-hover>tbody>tr>th.info:hover,
.table-hover>tbody>tr.info:hover>td,
.table-hover>tbody>tr:hover>.info,
.table-hover>tbody>tr.info:hover>th {
	background-color: #c4e3f3
}

.table>thead>tr>td.warning,
.table>tbody>tr>td.warning,
.table>tfoot>tr>td.warning,
.table>thead>tr>th.warning,
.table>tbody>tr>th.warning,
.table>tfoot>tr>th.warning,
.table>thead>tr.warning>td,
.table>tbody>tr.warning>td,
.table>tfoot>tr.warning>td,
.table>thead>tr.warning>th,
.table>tbody>tr.warning>th,
.table>tfoot>tr.warning>th {
	background-color: #fcf8e3
}

.table-hover>tbody>tr>td.warning:hover,
.table-hover>tbody>tr>th.warning:hover,
.table-hover>tbody>tr.warning:hover>td,
.table-hover>tbody>tr:hover>.warning,
.table-hover>tbody>tr.warning:hover>th {
	background-color: #faf2cc
}

.table>thead>tr>td.danger,
.table>tbody>tr>td.danger,
.table>tfoot>tr>td.danger,
.table>thead>tr>th.danger,
.table>tbody>tr>th.danger,
.table>tfoot>tr>th.danger,
.table>thead>tr.danger>td,
.table>tbody>tr.danger>td,
.table>tfoot>tr.danger>td,
.table>thead>tr.danger>th,
.table>tbody>tr.danger>th,
.table>tfoot>tr.danger>th {
	background-color: #f2dede
}

.table-hover>tbody>tr>td.danger:hover,
.table-hover>tbody>tr>th.danger:hover,
.table-hover>tbody>tr.danger:hover>td,
.table-hover>tbody>tr:hover>.danger,
.table-hover>tbody>tr.danger:hover>th {
	background-color: #ebcccc
}

.table-responsive {
	overflow-x: auto;
	min-height: 0.01%
}

@media screen and (max-width:767px) {
	.table-responsive {
		width: 100%;
		margin-bottom: 15px;
		overflow-y: hidden;
		-ms-overflow-style: -ms-autohiding-scrollbar;
		border: 1px solid #ddd
	}
	.table-responsive>.table {
		margin-bottom: 0
	}
	.table-responsive>.table>thead>tr>th,
	.table-responsive>.table>tbody>tr>th,
	.table-responsive>.table>tfoot>tr>th,
	.table-responsive>.table>thead>tr>td,
	.table-responsive>.table>tbody>tr>td,
	.table-responsive>.table>tfoot>tr>td {
		white-space: nowrap
	}
	.table-responsive>.table-bordered {
		border: 0
	}
	.table-responsive>.table-bordered>thead>tr>th:first-child,
	.table-responsive>.table-bordered>tbody>tr>th:first-child,
	.table-responsive>.table-bordered>tfoot>tr>th:first-child,
	.table-responsive>.table-bordered>thead>tr>td:first-child,
	.table-responsive>.table-bordered>tbody>tr>td:first-child,
	.table-responsive>.table-bordered>tfoot>tr>td:first-child {
		border-left: 0
	}
	.table-responsive>.table-bordered>thead>tr>th:last-child,
	.table-responsive>.table-bordered>tbody>tr>th:last-child,
	.table-responsive>.table-bordered>tfoot>tr>th:last-child,
	.table-responsive>.table-bordered>thead>tr>td:last-child,
	.table-responsive>.table-bordered>tbody>tr>td:last-child,
	.table-responsive>.table-bordered>tfoot>tr>td:last-child {
		border-right: 0
	}
	.table-responsive>.table-bordered>tbody>tr:last-child>th,
	.table-responsive>.table-bordered>tfoot>tr:last-child>th,
	.table-responsive>.table-bordered>tbody>tr:last-child>td,
	.table-responsive>.table-bordered>tfoot>tr:last-child>td {
		border-bottom: 0
	}
}

.breadcrumb {
	padding: 8px 15px;
	margin-bottom: 20px;
	list-style: none;
	background-color: #f5f5f5;
	border-radius: 4px
}

.breadcrumb>li {
	display: inline-block
}

.breadcrumb>li+li:before {
	content: "/\00a0";
	padding: 0 5px;
	color: #ccc
}

.breadcrumb>.active {
	color: #777
}

.thumbnail {
	display: block;
	padding: 4px;
	margin-bottom: 20px;
	line-height: 1.42857143;
	background-color: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	-webkit-transition: border .2s ease-in-out;
	-o-transition: border .2s ease-in-out;
	transition: border .2s ease-in-out
}

.thumbnail>img,
.thumbnail a>img {
	margin-left: auto;
	margin-right: auto
}

a.thumbnail:hover,
a.thumbnail:focus,
a.thumbnail.active {
	border-color: #337ab7
}

.thumbnail .caption {
	padding: 9px;
	color: #333
}

.media {
	margin-top: 15px
}

.media:first-child {
	margin-top: 0
}

.media,
.media-body {
	zoom: 1;
	overflow: hidden
}

.media-body {
	width: 10000px
}

.media-object {
	display: block
}

.media-object.img-thumbnail {
	max-width: none
}

.media-right,
.media>.pull-right {
	padding-left: 10px
}

.media-left,
.media>.pull-left {
	padding-right: 10px
}

.media-left,
.media-right,
.media-body {
	display: table-cell;
	vertical-align: top
}

.media-middle {
	vertical-align: middle
}

.media-bottom {
	vertical-align: bottom
}

.media-heading {
	margin-top: 0;
	margin-bottom: 5px
}

.media-list {
	padding-left: 0;
	list-style: none
}

.tooltip {
	position: absolute;
	z-index: 1070;
	display: block;
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-style: normal;
	font-weight: normal;
	letter-spacing: normal;
	line-break: auto;
	line-height: 1.42857143;
	text-align: left;
	text-align: start;
	text-decoration: none;
	text-shadow: none;
	text-transform: none;
	white-space: normal;
	word-break: normal;
	word-spacing: normal;
	word-wrap: normal;
	font-size: 12px;
	opacity: 0;
	filter: alpha(opacity=0)
}

.tooltip.in {
	opacity: .9;
	filter: alpha(opacity=90)
}

.tooltip.top {
	margin-top: -3px;
	padding: 5px 0
}

.tooltip.right {
	margin-left: 3px;
	padding: 0 5px
}

.tooltip.bottom {
	margin-top: 3px;
	padding: 5px 0
}

.tooltip.left {
	margin-left: -3px;
	padding: 0 5px
}

.tooltip-inner {
	max-width: 200px;
	padding: 3px 8px;
	color: #fff;
	text-align: center;
	background-color: #000;
	border-radius: 4px
}

.tooltip-arrow {
	position: absolute;
	width: 0;
	height: 0;
	border-color: transparent;
	border-style: solid
}

.tooltip.top .tooltip-arrow {
	bottom: 0;
	left: 50%;
	margin-left: -5px;
	border-width: 5px 5px 0;
	border-top-color: #000
}

.tooltip.top-left .tooltip-arrow {
	bottom: 0;
	right: 5px;
	margin-bottom: -5px;
	border-width: 5px 5px 0;
	border-top-color: #000
}

.tooltip.top-right .tooltip-arrow {
	bottom: 0;
	left: 5px;
	margin-bottom: -5px;
	border-width: 5px 5px 0;
	border-top-color: #000
}

.tooltip.right .tooltip-arrow {
	top: 50%;
	left: 0;
	margin-top: -5px;
	border-width: 5px 5px 5px 0;
	border-right-color: #000
}

.tooltip.left .tooltip-arrow {
	top: 50%;
	right: 0;
	margin-top: -5px;
	border-width: 5px 0 5px 5px;
	border-left-color: #000
}

.tooltip.bottom .tooltip-arrow {
	top: 0;
	left: 50%;
	margin-left: -5px;
	border-width: 0 5px 5px;
	border-bottom-color: #000
}

.tooltip.bottom-left .tooltip-arrow {
	top: 0;
	right: 5px;
	margin-top: -5px;
	border-width: 0 5px 5px;
	border-bottom-color: #000
}

.tooltip.bottom-right .tooltip-arrow {
	top: 0;
	left: 5px;
	margin-top: -5px;
	border-width: 0 5px 5px;
	border-bottom-color: #000
}

.popover {
	position: absolute;
	top: 0;
	left: 0;
	z-index: 1060;
	display: none;
	max-width: 276px;
	padding: 1px;
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	font-style: normal;
	font-weight: normal;
	letter-spacing: normal;
	line-break: auto;
	line-height: 1.42857143;
	text-align: left;
	text-align: start;
	text-decoration: none;
	text-shadow: none;
	text-transform: none;
	white-space: normal;
	word-break: normal;
	word-spacing: normal;
	word-wrap: normal;
	font-size: 14px;
	background-color: #fff;
	-webkit-background-clip: padding-box;
	background-clip: padding-box;
	border: 1px solid #ccc;
	border: 1px solid rgba(0, 0, 0, 0.2);
	border-radius: 6px;
	-webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
	box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2)
}

.popover.top {
	margin-top: -10px
}

.popover.right {
	margin-left: 10px
}

.popover.bottom {
	margin-top: 10px
}

.popover.left {
	margin-left: -10px
}

.popover-title {
	margin: 0;
	padding: 8px 14px;
	font-size: 14px;
	background-color: #f7f7f7;
	border-bottom: 1px solid #ebebeb;
	border-radius: 5px 5px 0 0
}

.popover-content {
	padding: 9px 14px
}

.popover>.arrow,
.popover>.arrow:after {
	position: absolute;
	display: block;
	width: 0;
	height: 0;
	border-color: transparent;
	border-style: solid
}

.popover>.arrow {
	border-width: 11px
}

.popover>.arrow:after {
	border-width: 10px;
	content: ""
}

.popover.top>.arrow {
	left: 50%;
	margin-left: -11px;
	border-bottom-width: 0;
	border-top-color: #999;
	border-top-color: rgba(0, 0, 0, 0.25);
	bottom: -11px
}

.popover.top>.arrow:after {
	content: " ";
	bottom: 1px;
	margin-left: -10px;
	border-bottom-width: 0;
	border-top-color: #fff
}

.popover.right>.arrow {
	top: 50%;
	left: -11px;
	margin-top: -11px;
	border-left-width: 0;
	border-right-color: #999;
	border-right-color: rgba(0, 0, 0, 0.25)
}

.popover.right>.arrow:after {
	content: " ";
	left: 1px;
	bottom: -10px;
	border-left-width: 0;
	border-right-color: #fff
}

.popover.bottom>.arrow {
	left: 50%;
	margin-left: -11px;
	border-top-width: 0;
	border-bottom-color: #999;
	border-bottom-color: rgba(0, 0, 0, 0.25);
	top: -11px
}

.popover.bottom>.arrow:after {
	content: " ";
	top: 1px;
	margin-left: -10px;
	border-top-width: 0;
	border-bottom-color: #fff
}

.popover.left>.arrow {
	top: 50%;
	right: -11px;
	margin-top: -11px;
	border-right-width: 0;
	border-left-color: #999;
	border-left-color: rgba(0, 0, 0, 0.25)
}

.popover.left>.arrow:after {
	content: " ";
	right: 1px;
	border-right-width: 0;
	border-left-color: #fff;
	bottom: -10px
}

.clearfix:before,
.clearfix:after,
.dl-horizontal dd:before,
.dl-horizontal dd:after,
.container:before,
.container:after,
.container-fluid:before,
.container-fluid:after,
.row:before,
.row:after {
	content: " ";
	display: table
}

.clearfix:after,
.dl-horizontal dd:after,
.container:after,
.container-fluid:after,
.row:after {
	clear: both
}

.center-block {
	display: block;
	margin-left: auto;
	margin-right: auto
}

.pull-right {
	float: right !important
}

.pull-left {
	float: left !important
}

.hide {
	display: none !important
}

.show {
	display: block !important
}

.invisible {
	visibility: hidden
}

.text-hide {
	font: 0/0 a;
	color: transparent;
	text-shadow: none;
	background-color: transparent;
	border: 0
}

.hidden {
	display: none !important
}

.affix {
	position: fixed
}

a.item,
i {
	/*line-height: 32px; NPO Commenting out to fix double line names */
}

a.item {
	padding-left: 40px;
	padding-top: 8px; /* NPO Added to fix double line names */
	width: auto!important
}

.preview {
	display: inline;
	position: relative;
	cursor: pointer
}

.alert,
form {
	margin: 0;
}

,
.upload-form {
	display: block;
	padding: 10px!important;
}

.preview img {
	z-index: 999999;
	position: absolute;
	top: 50%;
	transform: translate(-50%);
	opacity: 0;
	pointer-events: none;
	transition-duration: 500ms;
	border: 2px solid #fff;
	outline: #aaa solid 1px
}

.preview:hover img {
	opacity: 1;
	transition-duration: 500ms
}

.upload-form {
	border: 1px solid #ddd;
	background: #fafafa;
	padding: 10px;
	margin-bottom: 20px!important;
}

.alert {
	margin-bottom: 20px;
	padding: 10px;
	border: 1px solid #B8E5FF;
	background: #DEEDFF;
	color: #0A5C8C
}

.alert.alert-success {
	border-color: #BBD89B;
	background: #D7F7D6;
	color: #408C0A
}

.alert.alert-danger {
	border-color: #D89B9B;
	background: #F7D6D6;
	color: #8C0A0A
}

a {
	transition: all 200ms ease-in-out;
}

a:hover,
a:active,
a:focus {
	text-decoration: none;
	transition: all 200ms ease-in-out;
	color: #333;
}

.sort_asc {
	opacity: 0.5;
	transition: all 200ms ease-in-out;
	width: 12px!important;
	height: 12px!important;
	display: inline-block;
	background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMBAMAAACkW0HUAAAAJFBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACmWAJHAAAAC3RSTlMAAQYVFiouPEC80ZaQXOoAAAAtSURBVAjXY2BAA5oFIJJp9Q4QpbV7dwOIs3v3DjAHxJ0NojYzuKUBQSADNgAAr3MQ+X9bLpEAAAAASUVORK5CYII=);
}

.sort_desc {
	opacity: 0.5;
	transition: all 200ms ease-in-out;
	width: 12px !important;
	height: 12px !important;
	display: inline-block;
	background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMBAMAAACkW0HUAAAAIVBMVEUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABt0UjBAAAACnRSTlMAAQYVFy0xQLzRMXl+oQAAACxJREFUCFtjYMAG3NKAIJChaxUQLGbQBFEFDEyzVq1aDpTUBHEYgFwQBxsAAJ1bDw2ZcQ6sAAAAAElFTkSuQmCC);
}

.sort_asc:hover,
.sort_desc:hover {
	opacity: 1;
	transition: all 150ms ease-in-out;
}

.btn {
	border: 1px solid #1565C0;
	background: #1E88E5;
	color: #ffffff;
	padding: 3px 5px;
	border-radius: 3px;
	transition: all 150ms ease-in-out;
}

.btn:hover {
	background: #1565C0;
}

.btn-success {
	border-color: #2E7D32;
	background: #4CAF50;
}

.btn-success:hover {
	background: #388E3C;
}

.btn-block {
	display: block;
	width: 100%;
	margin: 5px 0px;
}

.upload-field {
	margin-bottom: 5px;
}

@media(max-width: 767px) {
	.xs-hidden {
		display: none;
	}
	form label {
		display: block!important;
		width: 100%!important;
		text-align: center;
	}
	form input,
	form select,
	form textarea {
		display: block!important;
		width: 100%!important;
		text-align: center;
	}
	form button {
		display: block;
		width: 100%;
		margin-top: 5px;
	}
}

@media(max-width: 1023px) {
	.sm-hidden {
		display: none;
	}
}

.table {
	font-size: 12px;
}
	</style>
	<?php if($listing->enableTheme): ?>
		<link href="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.5/yeti/bootstrap.min.css" rel="stylesheet" integrity="sha256-gJ9rCvTS5xodBImuaUYf1WfbdDKq54HCPz9wk8spvGs= sha512-weqt+X3kGDDAW9V32W7bWc6aSNCMGNQsdOpfJJz/qD/Yhp+kNeR+YyvvWojJ+afETB31L0C4eO0pcygxfTgjgw==" crossorigin="anonymous">
	<?php endif; ?>
</head>
<body>
	<div class="container-fluid">
		<?php if (!empty($listing->pageTitle)): ?>
			<div class="row">
				<div class="col-xs-12">
					<h1 class="text-center"><?= $listing->pageTitle; ?></h1>
				</div>
			</div>
		<?php endif; ?>

		<?php if (!empty($successMsg)): ?>
			<div class="alert alert-success"><?= $successMsg; ?></div>
		<?php endif; ?>

		<?php if (!empty($errorMsg)): ?>
			<div class="alert alert-danger"><?= $errorMsg; ?></div>
		<?php endif; ?>


		<?php if ($data['requirePassword'] && !isset($_SESSION['evdir_loggedin'])): ?>

			<div class="row">
				<div class="col-xs-12">
				<hr>
					<form action="" method="post" class="text-center form-inline">
						<div class="form-group">
							<label for="password">Password:</label>
							<input type="password" name="password" class="form-control">
							<button type="submit" class="btn btn-primary">Login</button>
						</div>
					</form>
				</div>
			</div>

		<?php else: ?>

			<?php if(!empty($data['directoryTree'])): ?>
				<div class="row">
					<div class="col-xs-12">
						<ul class="breadcrumb">
						<?php foreach ($data['directoryTree'] as $url => $name): ?>
							<li>
								<?php
								$lastItem = end($data['directoryTree']);
								if($name === $lastItem):
									echo $name;
								else:
								?>
									<a href="?dir=<?= $url; ?>">
										<?= $name; ?>
									</a>
								<?php
								endif;
								?>
							</li>
						<?php endforeach; ?>
						</ul>
					</div>
				</div>
			<?php endif; ?>


				<div class="row">
					<div class="col-xs-12">
						<div class="table-container">
							<table class="table table-striped table-bordered">
								<?php if (!empty($data['directories'])): ?>
									<thead>
										<th>Directory</th>
									</thead>
									<tbody>
										<?php foreach ($data['directories'] as $directory): ?>
											<tr>
												<td>
													<a href="<?= $directory['url']; ?>" class="item dir">
														<?= $directory['name']; ?>
													</a>

													<?php if ($listing->enableDirectoryDeletion): ?>
														<span class="pull-right">
															<a href="<?= $directory['url']; ?>&delete=true" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure?')">Delete</a>
														</span>
													<?php endif; ?>
												</td>

											</tr>
										<?php endforeach; ?>
									</tbody>
								<?php endif; ?>

								<?php if($listing->enableDirectoryCreation): ?>
								<tfoot>
									<tr>
										<td>
											<form action="" method="post" class="text-center form-inline">
												<div class="form-group">
													<label for="directory">Directory Name:</label>
													<input type="text" name="directory" id="directory" class="form-control">
													<button type="submit" class="btn btn-primary" name="submit">Create Directory</button>
												</div>
											</form>
										</td>
									</tr>
								</tfoot>
								<?php endif; ?>
							</table>
						</div>
					</div>
				</div>

			<?php if ($data['enableUploads']): ?>
				<div class="row">
					<div class="col-xs-12">
						<form action="" method="post" enctype="multipart/form-data" class="text-center upload-form form-vertical">
							<h4>Upload A File</h4>
							<div class="row upload-field">
								<div class="col-xs-12">
									<div class="form-group">
										<div class="row">
											<div class="col-sm-2 col-md-2 col-md-offset-3 text-right">
												<label for="upload">File:</label>
											</div>
											<div class="col-sm-10 col-md-4">
												<input type="file" name="upload[]" id="upload" class="form-control">
											</div>
										</div>
									</div>
								</div>
							</div>
							<hr>
							<?php if ($listing->enableMultiFileUploads): ?>
								<div class="row">
									<div class="col-xs-12 col-sm-6 col-md-4 col-md-offset-2 col-lg-3 col-lg-offset-2">
										<button type="button" class="btn btn-success btn-block" name="add_file">Add Another File</button>
									</div>
									<div class="col-xs-12 col-sm-6 col-md-4 col-md-offset-1 col-lg-3 col-lg-offset-2">
										<button type="submit" class="btn btn-primary btn-block" name="submit">Upload File(s)</button>
									</div>
								</div>
							<?php else: ?>
								<div class="row">
									<div class="col-xs-12 col-sm-6 col-sm-offset-3">
										<button type="submit" class="btn btn-primary btn-block" name="submit">Upload File</button>
									</div>
								</div>
							<?php endif; ?>
						</form>
					</div>
				</div>
			<?php endif; ?>
			<?php //Fixing the filter so that it works in subdirectories as well - NPO 6/2/2023
			$current_dir = "";
			if (isset($_GET['dir']))
			{
				$current_dir = $_GET['dir'];
			}
			if ($listing->enableFilterForm)
			{
				if (!isset($form_action))
				{
					$form_action = $_SERVER['PHP_SELF'];
				}	
			?>
			<form action="<?=$form_action?>" method="get" class="form-inline">
				<div class="form-group">
					<input type="text" name="filter" id="filter" class="form-control" value="<?= $listing->filterBy; ?>">
					<button type="submit" class="btn btn-primary">Filter</button>
				</div>
			</form>
			<?php 
			}
			if (!empty($data['files'])): ?>
				<div class="row">
					<div class="col-xs-12">
						<div class="table-container">
							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>
											<a href="<?= $listing->sortUrl('name'); ?>">File <span class="<?= $listing->sortClass('name'); ?>"></span></a>
										</th>
										<th class="text-right xs-hidden">
											<a href="<?= $listing->sortUrl('size'); ?>">Size <span class="<?= $listing->sortClass('size'); ?>"></span></a>
										</th>
										<th class="text-right sm-hidden">
											<a href="<?= $listing->sortUrl('modified'); ?>">Last Modified <span class="<?= $listing->sortClass('modified'); ?>"></span></a>
										</th>
									</tr>
								</thead>
								<tbody>
								<?php foreach ($data['files'] as $file): ?>
									<tr>
										<td>
											<a href="<?= $file['url']; ?>" target="<?= $file['target']; ?>" class="item _blank <?= $file['extension']; ?>">
												<?= $file['name']; ?>
											</a>
											<?php if (isset($file['preview']) && $file['preview']): ?>
												<span class="preview"><img src="?preview=<?= $file['relativePath']; ?>"><i class="preview_icon"></i></span>
											<?php endif; ?>

											<?php if ($listing->enableFileDeletion == true): ?>
												<a href="?deleteFile=<?= urlencode($file['relativePath']); ?>" class="pull-right btn btn-danger btn-xs" onclick="return confirm('Are you sure?')">Delete</a>
											<?php endif; ?>
										</td>
										<td class="text-right xs-hidden"><?= $file['size']; ?></td>
										<td class="text-right sm-hidden"><?= date('M jS Y \a\t g:ia', $file['modified']); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php else: ?>
				<div class="row">
					<div class="col-xs-12">
						<p class="alert alert-info text-center">This directory does not contain any files matching this filter.</p>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<div class="row">
			<div class="col-xs-12 text-center"><hr>Directory Listing Script &copy; <?= date('Y'); ?> Evoluted, <a href="http://www.evoluted.net">Web Design Sheffield</a></div>
		</div>
	</div>
	<style>
		._blank { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAWBJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/7vXLzhP1D/fEIOQMZMDFQEarKSDBqyEgmEHIEMqOoAIT4eBl1lOZIcQVUH8HBxMogJCZDkCKo6gIOdlUGAl5skR1DVASzMzAxcHGwkOYKJmJRKLGZiZGRgZWEhyREs1AyBMzfuMdx9+pLhH9Axf//9Y/j9+w/D95+/GP4zMDJwc7CDHAFSlkjQAf/JsNxGX4Ph2Zv3eNVsOnwmgTgH/CfdCRxsrAxKUmJ41XCys9E2EZKVcKkVAsSA/0Q7gFbexeIxuobA0IkCYBYe4BCgVSr4T2wI/P1HI/uJTIT/hm0iJDYK/tIsFf4fWAcQHQL//v0f2ET4h1ZRQHQa+Pt3YEPg798BTgN/aOYAYtMAraKA+BAYtmmASAfsOn2JJg54/+krhhhAgAEAOOceVk7I96wAAAAASUVORK5CYII=) top left no-repeat; }
		._page { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA9JJREFUeNrElktoE1EUhv87j2QmD5uaVPsyrbXaGsXHQlBKoSKC6EZcVMFNi1IURBHBlYsorhQUXOnCF4puFNpNQRFSBR/gA7uxxSIt2oei1bZW7SPNeGfunckkpElGoh56embu3Nx8c/5zzw3RNA12Iyc7Yk3VoSY4tIGxn7pfQ3Rna7Z56d9nDNgd0faY9gcW7erVVl56qtHPX80FYHcBBbRttRXYsbquJReE3aRsD0nHePJmdATwBwCXag01hUTEGnzWfZVPwtrSSkiEtHRE25FLDt2yZ0AmQN8L4NUDoPMKMNzHxkyXSDK11Es8AuoCLjRHKrFrTX1emcgIoHEnsxPAIP3S/jeAxw+87AL50APiJobrAOZc3YrcAsp9IpYXyQZE87rcEFklqA4G0D89DbE4BH9lGK6aOngXl1rPS10khdotEhQrAgQ6rPvuyBKIVI7bWeSQMlcqixH6RsWbt0D1euELFONpLIYN4fKk5lQG+66SaD5VmhUCBiHSf3tW6RBouTkPhDSfBLrVU4D6+lprfLK2BkO9vdiyNmLch2XBmqvH690f0DUwigSliieAqTkNkzMapmfmUFHkaxmKto/RaUdzAiQSbNmwkkzx6+FR9H/9geHx73g9+BBlRX4cb1xJ58rG80MblqL708S8cratL8PWG4/X5ZWBBI8vB7/g+cg39Hy2Laz6jTAyA9x79xEHIwHjfoEio7Eq6Lh3ZK2Bge+/UOJTDM9ktUEV6Z21IABzfNHO7ctyLjD3NwH+hWUG4EV45s592vFokUluFkX9Wo/0Y4JIo8gioftPoE4IuwYx/szYsNhL3eM8A4/evqfdRWUuUwiXm8FINhATRgcwYAhzG0SFR8bGRQ4A4pzg7vF9BUt1fB5dMwLM8rnPet6lptpIs5CMREi+sfXWtvbMryu9suH5A3Da5rP0BPTQ41b1Agp1N02jS2FS6JJkqol0MGpHIiEcXhVyAsBi78XTBZPAXDM/AL4LXrzlEghiWqEJ7LgjGSrfkoBYoVyVUe5xIME0l6D1/GXWenUZFI9NAoVJYO0GOasEbXVBtK0I5g8wwzPw5ELhJDDXzAtgKv6fO+EUl2D7sRN8F/jYLlBU9qPUksCVuSGZEvCtuLdmoeGOAU4d2J/aA1L2f1oPMPuAVX/JfrBIkaw18wL4GWe/CGrCSwqWanNNRwDnrl5jle82K5+nXrZVv5X6tPTbzoNNJT7qXicALF1V1ctSt1tK15N4PxBTT0Ir/cRSwUNlNNfMC2CST27c1FAwCSadAEzMav93G9563v3PAH4LMACMNVxnrM+YQAAAAABJRU5ErkJggg==) top left no-repeat; }
		/* .7z extension... css doesn't let you have classes start with numbers. Use an escaped code (list of codes: https://en.wikipedia.org/wiki/List_of_Unicode_characters) */
		.\37 z { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAALHRFWHRDcmVhdGlvbiBUaW1lAEZyaSAyOCBBdWcgMjAyMCAwODozMzoxOCAtMDYwMOhuhXIAAAAHdElNRQfkCBwNIiYW2QBcAAAACXBIWXMAAAsSAAALEgHS3X78AAAABGdBTUEAALGPC/xhBQAAA79JREFUeNqtlXtIU1Ecxy2bUklRUBEVRVH2oix6v1+gFAQFBZFFf/RHJPVHf0SJPbSgPyqhhFQozFqoma/NVqI5bam7m9OpJTpXU5dazZmzljbNX+eccQ6XsnNPzAsf7jnf/X7nfnafQUF/bMf2j4HKvFVgfr7zv8A9966MhaBANyzQ01kGStuj2GAG3nCPOWcupCaEBCbhF9CjJYe5ZMaPY+A57ml6FQWfzYcDkxAVMGTsZlABm+EYDLSnBCbhFyhFiw5x0d1RMfAc99iMMeBz6wOT8AuUoD/l41Kj2cXAc9zTYj4PQ15bYBJYwN1RjBYd4PImXcXAc9xjt8TB8KD7D4lD/ydBBD6+hOEhL5eOhksMPMc9dssVIjPsk0skg6M8UvwR9Qvo0KJ9XOo1wQw8xz34fYAl8JnAlwPfEzZDNHo6IuFNxjQxCSLgLESn8isXl+0Cg2Zup5ZLZe4KMYHu9nx0Gl1c2oyr4MfXMrJXqqWYtFsEBdpy4NdAJ5d+Tw15A+K9Ui3FpN0sKNCaBb/627gM9NYRAa+7SrGWYtJuEhNwOZ6gu9jO5V3RZnDWJUCTfq9iLcWk2SAo8CEdhr43crEWboTXaSqCUi3FpFkvKPD+Pgz2WbmUPZgKJSkqglItxaRZKyjQkgyDHomL7q6K0YMumVI9xlSwRkzgi+0u+HoM/8TTng0G9RoyNj7dAZ/eJXHrKVLBakGBptvg6y7+J70ONRTemQ2d9YlQlBwOruZUbj1Fyo8QFGi8Ab4vWi6txjgoTl2JbsajirUUKU/wTfj57VX42ZU96kh5ywUF6mPhZ8fjUUfKXSom8Ml6Dh5eDP4LXdJi8o0f6Td5jscjIeWECwrUxEA3+s5TytPWk4Ur1Nug35HIckvuPnZQeY7HIyHlLBQT6Ko+CT/s1wnelgTITJhMDtJlPstyZ9UpeBwXSvL8W7NIRmXk4+KUZaC+PB7e64+D8dkCMYFO01HwNl8k2F8dJgtpE+ewrLv2DJKaRPKM+DBwWWJITg8qH7eWR5P9i6T5YMyeJyhgPAjexjOEkpRFZIG3mj0sy785g2TpsSpwlB5gOROQjb81nPaLXp2IBOaICXRURMH3hhPQU30EneYQUF8Khd7aaJJh6OI1T9exTJ7Lx826XWRfmroQvTVnCgoYtsI36wGwZkeQ5tf3F5A5ZaSnQJ7Lx5nxEyDrWhi063eCMWu6mMDH8gjos+wICCrgqd5OwFlV5hQxAWfpXPBISwKCCciyqowwMQGDOgQVh446JWnBfwn8BvjFCvbqRE8WAAAAAElFTkSuQmCC) top left no-repeat; }
		.aac { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABMhJREFUeNq8V11Mm1UYftp+/aU/tKy/o2MbLaz8jAkscVkUNMYLTUycemVMZrLojV5464WJ8UqjMc4lJsaoNy7b4p2Ziz/LyIT5E3RAu4nrCkQKg1LY+t9+La3vOYRCoaU/Gk9yaL/3e885T5/3fZ/3ICkUCtg+egfPXevptg2jzhFaiSMUin/p+/31l/fy23meUM7pnbefrPd8XLw0ias/+E73DHyMaiC2D2kFnHVP9vf4YCs87RoG4otaAZRloJBH/YMQuD0OuF0tEK75TwO1MVEWwHq+fgQstlZ7Myz71JArZEiL0zWBqMBAoSEAmiYFHAdMkAlSAiHg0te3qoIoCyDfIAMSqYSDsLcaIQgyvCgXcOGid08Q5UOw3gAAYk3CsloigVotp3AYOBMvUTi+Oj9ZEUR5BtbrD0GeGLjy7TRu3lwsMpLLriMRz8Bi0WFmLsqq4wG9eqMqgFwNDIRXYojHMjh4eB9mA2Fo5FLIyG7UyiGXy7YcrVqgvQUnHj6Id9+/fuw/CcGfvgX8MhpA30AbnG0mypkCWlo0CC3HEZy9j75+B6w2XeM6wKirNEZ+vA3fVBAqlZLHnfkyJgL+MPY7myGhHJiauIcTJxXkIzQIIFfKwPivAcSiKcwFVhCJpKGiJNvUC+ZrNGn44YmECJlUCq1Wgbv+VRzxmBsFUMrA+M93sbjwgEpMSYcrt5VrgfvGYmksL0Vhs+spDAk4DzRjbS21a5+aAWR3hKBQoSjylCvMl8U7lcoiGsmgiX69hDpMJp3btU/DOVCogCCf3yq1lVAcOp2Kg2BlLJNJ9sylKgzkSp7NFj0EKq04HbSbgRzC4QQvPXYgw8pyoYkUcec+DTMw9EQX/2SxHh25Q3SLW0lIvpT4SCaYTQKLtYkApeF06v4FA2J55CqlgEcf78Bt7yJRnthggHxlFHOdnmJP14vQchKGZiXUKmnFfaoCEKss7Opx8KwXxTz3jVDc11bTvBFZrWqiX6i6R0MMbB8mqv1NX71ODmOzoq71VQCs19+M6l9SWxWYLVoolXIE5++X+Oj1KugNasqFGDKZnVVTfk1DOfDcCwO0mYBPP7lOsU4V7Y8MueFyW3Dlshc+70YLVpL2P3vqIVJCE39mwC6c/40SM1bfrVjMZvm0O/RchO78tYRD1FI37Wyyw6cm59HuMhdtni4bVYMK585exQfvfYelexGy2YvvawdAyNl0uSyYmw0jQE2ot7e1aG8/bEY6ncXYqB8dnTbISAiY3e22YuKPv3lYotS8Pv/sJ1z+ZrK4rnYAYpbPTo+d02+16qnRGLi6MXtHp5VrPzucDVeHhduZNLO7xOZ6Jseb39msGQArI7NZB6NRw8XGQLQy9es8YuPvPN37SfnoNtTWQl0vQW3Xzu3eqXn00yVFp1VCIHU688oQnnr6KH9XqTQrJmH/YBsWgmv46MPvue3U84MYPH6I6I3yS+dZsjNQR/ucOPPqML+A3qCQdHU78OZbz/A17P2NMf+eoiQp98/pyWNNw61OE5JJkRQuzu1qjQLMthqOQ0Pfg/NrxTVuCgV7TiU3egTzY/5+St7tY2wiMeIdf+2x6gyQDszMhEptESa5ybK/4pYvWPK8c23dOjA9K+L/Gv8IMAA+baRs/m86owAAAABJRU5ErkJggg==) top left no-repeat; }
		.ai { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA8lJREFUeNq8ls9vE0cUx7+7O7v2Ok5s49hJqZ3QBCcEJBoFxKWiooeqPVWVqgoOVIUDUtVLVfFv9NBTOXAAqT3AAQFCAqmiDeJHVR9AISJVfpQmJU5KghOcjRPvb3e8BhPw7GZNDE96smd23nufee/N7nDlchkb5a/0V8NypuMQGhRjeQ3mUvHsntwvx73WvRqPsBZt/+7jRuPj6dVRKH9MHhvDUWwGsVF4NFGin/RB7okdG0sdPfPWACzVgPpYcf6LOzoRPzLYEAQboOxf5y6NYPr0LeRvTkFIxiANdDcEsaUMFO7Poji5AIHjwFc0EgZJJSlEF+KHKxDRTSF4dqfavlQZf1wNzHOgP+CCAfDbohSiA9LuZxDveUMQ1xL4kPSX+2CrJpS7/0K5MQ6OEPAt8kuO40eApXMjrqeDDWD7I9AWV5G/NgZRFhFMx1C4kMXan1O0M2mGdB3ltRKslSJIIgwup1QyUaBm328K8OrLgiWrD+awNDwJ3qDBeB4ChQ4kS9CvjDhzYn8SQiYBtIVoqpII7+/Dfz/9PugrA5X6esmT3yawem8WROBp/Wlw2gOE0P8mEPn8fZjZR5A+HfCVRTaAxQawNRPzF0egz63QoDQgOHoC8Ex5SJl2cB1hiJ/tdvXh7xjaNlNXRnPQZgtO50uRIDq/PgAxItMxzUKAgHRFXW0d3WoGxNYgDVbdMQlJ0GeWwOmWMxa7o7537QPAYi7WF5Xquaca6mnH+p1pkEoP0F4g/QlXu8YBTLYjLVdNf+tgGur9uRqMEA7Qbpdc7RoGsBmOrKIGY15BS28C1oICjjakEBRB0lHwqTamzRYyYNbNrY8vgNAXjhQLQRudRyAdgTS0HVyL6GrT1BKoU3m0DXWhlJ2BTAOLfXHPcm2xBPW7iXzUCzWbg/xhN/3shphrmpcBvd45JwHywZTr8+ZmwGhegNcD0Axvo3eS4OUA9H9ma3NST5qekkWUVa0hAN4NwEvjJw47yrWEXpoTEts87fxnQHfPQCCzw7mxlEYnEBjoRXE4+6J3DMvT1j+A6u4kuCcDdXwa6sQ0wh8MQbl2ewO46WnrG8Aq6a4GocFdMJ88hfguvfelO8GFw3S8XCudl63vHrBKBlNJRxKkPeZ89UgsApteueS9/c6zKoDlavt8jb8SrLF30XpwCNrDHB6d/NEZJ7/5Am2H9mP5/PVa6dxsGyvBKtvJ8sXbyP/8a+35wqnLCO5MOeOZb3+A+ncOdrEZAHm2k/X8w7p1xkyhekm9/mDTYIJfAH7dejOvPa5+6n8BBgCfpiX4nmuUOAAAAABJRU5ErkJggg==) top left no-repeat; }
		.aiff { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABGdJREFUeNq8V0lvW1UU/jzF80hrO4OVMiRNm4S4aUoWBdLSigiJDUK0CPET4BdUAgkJRASsYIHEgj1C6qalKpuE4JAiHDokFRnstrRxbMeO53iKB859djzEz7Gfi3qlm+uce871977znfOuRcViEbVjdOK72fcvjZ2DwLFy30/Td4c+nl9Z+jjSzO/g90n5nC5fGoPg8dNdBHw7dv/O3uzI6W8PBVE7xM1QCp4UN/lKH0aPa+30kYEwPAUA4ZP9eXGoB29OD+HUsKFtELwACoWC4MlAmK16jJzqx/RbJ3Bm7Lm2QPBqoFgoCpYAS4NSKUN3rwESiRgymQRS2Zp90Rk4VBPSZgx0AkAsFkGp6oKlh0BICUSXlNZVu+OWrykIXgD5fAcAyqyJRCIoFDJKh67KhFRin3N4OBDkEmnNQF54CgrEwI1fVnH79laFkXyugN3dDIgGmIwKeyic/pG23mkJINcGA8FAHKGdXQwOWfHQHYSMClEiE0Mjl0ClktV4qoHnTZg804+Zr+cN/0sK/lnx4JbDjbHT/Zwvy7fVqsW2PwH/ZhTDo1b02vRtMcfPwF6+acDNa/ewse6jPMu5vDNfxoRrIwgLgdDrlbh31wu1WgaNVt4hgFw9A84/3YjHUnjkDiAWTUOuLFGcp2phviaTCsdeMCEaSROYJI4cUcPlCmFk1NIpA7l6AIsubHkiUKrlUCnlNeVa5HzjsTQ8TyJEuwFeTxx6owKB7WTDOW0D2DuQgmKToihQ/pkvoz6VyiEWy0BvUEBMpcgqYO+QVArSQKEJgkJZA4lElp44AS3lPBbL0trVUkuHM5CrDzSbdVxDSSQyjQyQbzSSon0xpweGNZ3OQa2RNZzTMQNTF09yayaTw+yv95FM7VVFSL5sTSaZLYfuHi38/iR6ezVPwUCWXzzU6nFhehgryx4SW6zEAPlSx4WKyo61Xu9WgnQgh1olaXpOSwDZFoGDxy1cvbN3FvONUmmGQxmu9i0WJa3Slmd0xEDtYLW/72ukJ2ZTSHwLAHk8q9GkD1SfQKdTQEd0B7bjnAjZOGrWIENKZ/8fNWvrYjefhLl9ubz6QopFU1Se6c408NrUAGw2IxYW3Fj661/O9sGHk/jD4cLjxyG8+954XexXX97k9mvHjevLCAYTQgCUyoxdLF4aMGP+t3W8cWEIiwuuujdmrlznn392reGM3ymGxXVWBZkSgPGJfm51zK/j9alBEp4aPm+kcmfYF9uVT96uxH565WqZuUFu1traB1DWgN1uw9/OR9QJS3l+2d5HtAcrXXCfgR++n+PWcDhZiV2iOBZbe56gMjTS03bT5ZLN8YljnP3sqwO4+rOzIQVrq96GM3Yo53z2tkV44mQPPJshzHxxnbMplV2Y+eYy9144CICv6bD9dpqRiO/H6Vm7+lyfzUT9PUsXjKp6920qunqzNZXKcraNNV/dGXyxbCzc2Z1bdn50vi0NPHiw3WDns0Wjybb8BKVg9WH2mXXC/wQYAD/xvI05S4jPAAAAAElFTkSuQmCC) top left no-repeat; }
		.avi { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABEJJREFUeNq8V1tvG0UU/nZjb3zJpb7bSUpsxyHQqKKktIrUSuEBJMRf6EsR4omnvlQ8oIoHkMorf6DlGQkJUSncxOUBKZS0iiiBkF5ofEl8SWynXt+9a85MW+P1mmbXiTjW0cycmXPmm3POnB0L7XYb3fRx7KMfAvPBV2GS5KyMck6+/t7999961rre/Sz9Fr32wRtm98edz9Zw99u/Ll6d+RAHgegmEUdFdLC5N0/AE/MyENcOB4C5ySQzx7rDLpy6cNoUCPGI9ufkdDsReMFPIBYMgzgyDzCW7FaM+UcJRMAwCPEI94c4JMDmlEyB6AtAVdummWWhIAgQLaIpEH2vYVttm78EKrD+5ToStxL8RqiKilZDQe1RDU6fE8WtAgNRpKWXDgZAymYp9vocivGCTj4yCXhfDCKyNIufrn53ypAHVMW8ByzDFnhnfeb1+gPQeqBWqMJit8Bisx5oML2x/eRatlGX6wifiR4OgLxTws1PfsbL75yFK+rmsma1if1UEeU9qv+7MjIbaext7UJRFaiUDNUSAbZZ4DruxnML4QEAtP4FII1IvF27vopGpQ5BElEv1zB8zAZ5r4ThcTvk3UfwzPgoBH5ejJweJ/fCw1sPNLYMA1BaSqcvWEUeX+uYBGlcgigNwak6CYAd0+eitLEXubsZjE+64CMA3ZRaT2psGfdAU6s0cXYKoTNTSP4SR3hJH9N6qUZhU3R6bVXVyQYCwPKgQQlVzVf6GlSfnJLNZe9nIecpNyg8cq40GAClR8nudWDzxgakUUk316y1kLuXw84fKRRSeR6iGuUIv5YRn269QQCqri4MkWHmUjYnZ0ucs5sZpP/coVKsQBobRmRxBp6wl29stUl9bRkC0Kq3NGOH34HkShwCrU7//iMUijdLTrWtwBvzITg/Af9s4Jk2zHmgoVW2exw4fn4ape19VKm229x2OEjmjni4q/vpHKoSKg193Manj3E2stYMif8Vgl6GAIyFRmGlRwcbs9busmvWsPmn61irs2EYALmzl4Mngjh94RVEzkX42OF2YPHtRQiiwMeeqIfPK3WFt2y+14ZxAHS1etk/50diNQE/vfnYOLWW4t8Eb8zLx77nfUivp1Hdr3ZC02vDMACl1tSwSKdkJ7z3/SZ/bPhpMybf+W0b7mk373soIbcJFOvzq0sAeu0MnAPs1IwC8yE0Kw0EKBxMnrydROhkCE7/CHd5YjXeibXSbA2eA8y13Rw6OcH+dnEv1OUGJl+a4PKtlYf8r1ZsKYYkPcUqVKqZ/PEhFJ0d4x6otDos0m9qYQq/XruJb658ha+vLMPqkBAib7D5BH2gIuejiK9sdXQeh7GlsfNUbqwSlptdCAUsX75BFTDdmfvi3c/RKDd4//anq9hc3kD+73xHj63vHpsvxXJT069kypr57J1Mp1+UCyg+0D5GkyuJw1XCcr6M/4v+EWAASsVNgE0Xzt8AAAAASUVORK5CYII=) top left no-repeat; }
		.bmp { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABCxJREFUeNrEVs1PG1cQ/y1eYwcw4CQ0gYACh6i4SmksFVRQFExyKIVD+w+0Ndc2oY3yB9CovbSnNj20SlrhHlu1KvSEWhWjKEpcVVENB0w4gAnNB1EIXza21+vdzntre9eJsXdjlI40em/fzpv5vXkz80ZQVRVGEj4aDdoPHfLBImUSCSi7uwFc+XqklNzT9sRiQq6+Pqv2kVhchLy87E+PXkA5EEaqwj7S6+0dcLnq/Ri9MP6/AHj1pSYMejyWQOwrgFZ3A95oO4Y3CUSdSRCi5WB7+BCJYBAqBV3N0BBsR4/m/7lratDmdue/pyIRf6xMTBQFoBijNpmENDfHR/H4ccR/+pHPq7teg0DGFUN0O+12NLnqCnSVAyGWShVlawuxQIAb5HTzpjbaqyFFIpDv34fj9GlUnzypKbPZUOtwaDoKQMzvCaIkADY6zp2D9FcIyvb2s55K7CLxx++A08llJ2fn8Hd0BRlFgSTL2EmlsB6L4zBdTSweZzGxSdsulr+CXLGor0dmY4MMJfmpGdlaWqBKEhxnz0IlA5zo1GJDA6Z2doCUpCuqJm8cZHwQzo4OJEOhU5Y8wCg9P0/G7agiJcqTJ7D39CDz4AGkUAjO4eGCfTZDAFZUB5gHcsyM23t7Ye/v53MWeKLXCyWTQSocLpAtx6aDkN1h/lQ+H4TWVh5UIp0498/W3Q2Z0lFoa4PQ2PjctaOqOAA1z2g5lp+rDY36/HATeaMZ8u3bBfKl2DwAVTHFqqcTajyOzOIdU/LmK6GimPNfnQs4cABYXQU2t+gZpSLkojUnrbnNXUtxABRgJYml36NHwL171EAI2lpsR2PKEE5UG0BvAmpr9wnAJtUQqv9gBYnlO+9eBJ4Ze+qgaomuLopYm1UA2StQSMnjxxobQYkW3rA18tSRI1YByNq4vq4xO6koPl+eCQZ9lq+AGa2tq6xJYDpKxFRpACzCGVdKlgHIMl4UlQSgfvChngRSCiPT05hYWsIn3T0Yo1I8Mv0nAgsLGKcn2/9yJwYmJ7hs8O13CtS5v/8Om7mX01RPyABkQQz88jOEK18hQK/ir4NvaeuK5tL+5mb+7WtueWYf2+P+9htEKW39J07s6dXiANKyxkTvd3r4iT8+5cXI1JS2nq2Ufs8raKdmo536hvxd5+6byckZPa3TVgDIaY1zZZmUhtfWMD44yNfHevtw+cYN/ttHDUpubgSgXrqEjfPnEaUCFpid1fWZioG0LvxD+B/MrKxgZnkJwXff0/8RsMvXr2N8aBjea9cwRr2h0c3CZ59WEIQGANxolr68dUv/RyedWIhg7MwZhP9dxUw0qrv8KR3WAUhaXzdw9aqeBckEwtmHpuOLz/kYpX7RS8HG5C/+Nknu3tD3SVLlHpihd74YRdlLmKXw3bsFI9+3fWd/6sCLoP8EGAAao3IQDslXAwAAAABJRU5ErkJggg==) top left no-repeat; }
		.c { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAfJJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/6vm73yP1D/fEIOQMZMDFQE+spyDJJC/AmEHIEMqOoAIX5eBgsdVZIcQVUH8HJxMkgICZDkCKo6gIOdjUGAj4dBHOQIbeIcQTUHgBIUKwszAzcHO4MgyBHCEEdIEHAEEzEplRgMNoyRkYGVlQXFEZYEHMFCzSg4c+Mew92nLxn+AR309+8/hl+//zB8+/kTnDbefPwMcsQHoLJCgg74T4blJprKoIIIr5oZ63cbEBUCGKUVEUCAhwuMSQVUzQXkAKqFAHUdMBoCA+2Af/8GOgoYBjoE/v4bYAeM+ET499+/we+Am/cfM+w+fobh24+fDIaaqgyulsZkOYAJuwP+48XX7j5k6Jy3HNgCYmeQFhdl2LjvCMOGvUcI6qNaCBy9cIXBUl+bIc7fHcxXlZdh4AI2QsiJOuwO+PsXr6Y37z8yqAEthanTVVUkSh8JDsDvEyE+XoY3Hz7C1Z26fJ2BExgCuqpK1HHAHwIOMNHRYJi6fD0DKLdyAlvCB89cZAhwsiWojwQH4A9KeSkJhvQwf4Yj5y6BQ8LX0ZrBylCHoD6qOQDiCHEgdiVJD9XSAM0Lol2nL9HNAQABBgDE9HIxf4Gv2QAAAABJRU5ErkJggg==) top left no-repeat; }
		.cpp { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAmlJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/6vm73qP1D/fEIOQMZMDFQEBqryDJLCAgmEHIEMqOoAIT4eBmsdNZIcQVUH8HJxMogJ8zNYkeAIqjqAg42VQYCHm0GcBEdQ1QEsLMwMXBzsEEcIgRyhStARTMSkVGIxEyMjAxsrC8QRvCBHCBB0BAs1Q+DMjXsMd5++ZPgHdMzfv/8Yfv35w/D95y9g2uBgeP2BCeSID0BlhQQd8J8My231NRievnmPU94JiGes321AVAhglFZEAHZgAlSSEiNZH1UTIVkJl1ohQF0HjIbAQDvg37+BjgKGgY6CfwMeBf8G2AEDnQj/DnQIIDvg24+fDEfPX2H4/uMHg6GmKoOsBKS837T/KFyNsAA/g7WhDl5xEtMAJArefPjI0DR9IQMnBwe4jt+w7yhDdmQAg6GGKpgNcgxI/OaDxwyv339g8HOwxilOkgP+QENg9/EzDDJAw4riw8D8LQeOMbx69wEuH+ruwKCmIAsWv3H/MYOXHX5xknPBo+evGNTkZeB8LzsLFPlVO/aDfXrr4RMGb6AcIXHi08Dfv2Cak52N4ev3H3D+k5dvgNHBxiDMzwfmS4uJMAgB2aa6mgwWeppwdbjEiY+CvxAXa6soAuP0CIMU0EAOoGOWb9vLYKqjwRDobAuWNwGyVeSkUfTgEyfZAUZa6gxv3n9kWL/nMLBt9xPsIBcLE7g8KLdgswCXONEO+A1sTMKAo7kRGKPLtxWkYagFAVziJKaBAS6ILtx5SDcHAAQYABHcpCu5tn1VAAAAAElFTkSuQmCC) top left no-repeat; }
		.css { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABFBJREFUeNrMVl1Mm2UUflrpD6UUSgu01A7Gjx2jwTlDGJsTF3+SQWaCWeayGJ0mXsxEjcMLr4w33i1q1CWLu8C4xE0yZYlzqHXWuSAWNwRkvw4cjo111JbSUlpGW8/7IrVrv7ZfWWM8zcn39fy87/Od9/y8kmg0injq/OCQvcZU/giyJI9vDu5Z/8fvvPLs8+nsEvfjgnje+/4n9ugK6BvHcPTNg59Fyb8rE4B4liKH1FizCoaSot2ZQMRTTgGUaNRoaajLCkROAWgKVCjXFWcFIqcAlAo5tIUFWYHICoA/MI+hS2O46XIL6mR596AgXxkDsaGhNiMIqZhMZdw/fA7b976FN979CLb+s0n6sWs3IJVIIJPlxUAYdNqMIPLEfr2tfxDFmkLUr16FB+rrkvRGfQnOXBzH2HUnIgQoHI5g4fYiAqEQClVKuLw+BmKGTF/LCCAqIMtXKmghFTp374CK3hNttm56EF+dHsCxgUG03L+WcyId6LGtExWBpG5FpJDLoaLQ5lOiCemL1So0W+/D2dGL0FM5VleU5a4PBEMLuO50QUkg0i5GOcBsLk9M4q4aUXxyTU27caD7OOSUXE1Wi2CCLjPLA9YL/pqZxXuHvkjSiwcQx6GFBczNL5VYRZnuDl0iK+h4GmoruS3zSdSvKAJmYxl2tT8KqVSKvl/PpY2A0+XB6JWr0FK17Hn6ybuIQIKj2VBKjUWL21RW6QAEKVpSiRR1lSZoKCnFABCsgkhEoM1SckkoyYR0y+T1BbiNQiZPayciB5J/EpIz/nn4PLx+f5L+ltvDu6EkzRqiAUSoiyWyrriI6xwjF3D+ykSS/sczIxgnAIxYsgqtIf4IBM5r03ordFoNfP7A0gYJNpbVZlSU6mAq18NYqhNcQzSAVAljqTKntEmnyxpAWGwG5YD+rwCWQhgIBnGSZv+lq9doAiqxbctG6glLQ+ZLe1+SnNl399rhmvFCT0m7Y+sWrs+6DFkEGO8/fAwTU7fQ3toCbVEh9nUdoZtPALaffkHf0CiXs2vY/sM93L6rpxf++SCXT9x04kjv97G1UkVVOALhMCad07hMX7jv9T38LlBjrkBzYz0NJRkfzYFgCH9OObmMMfNhzYr5uTwz2P54K/dj8uyPgGp2ju54jNiG4X9q2KjX8fcm6xrqdBE4fruAo9+eotZ7L17e9RQ6HtuMHwaGcNIxyMd32+Zm7pP1ESzSJhq1mr9P09ew/z4C9LntVOw5RxF4aWcH3n71RfxO85/lw8Gjx1FNkep8bide6GjDidMO7rvMoiOwSGHTqAvw0PpGfEjn29SwhqbcH3ymtrfmocpkRPfXdpr7Xri9ProlKVCmK4GBvvbTE9/F7BmYxZUcwbJT28MbUF9difHJG9i4zoq1NVVcZ6GL6TPbnuDyKpOB27E7AHsa6FLimfXdYb8CAP+Gy2ws55yoSyVvtNSmXEs0ANvAyH/WiP4WYABfuhLeCCyEDAAAAABJRU5ErkJggg==) top left no-repeat; }
		.dat { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAArBJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/6vm73qP1D/fEIOQMZMDFQEBipyDJLCAgmEHIEMqOoAQX4eBisdVZIcQVUH8HJxMogLCZDkCKo6gIONjUGAl5tBjARHUNUBrCzMDFwc7AyCyI4Q4sfrCCZiUiqxmImRkYGNlQXVEbpqeB3BQs0QOHPjHsPdpy8Z/gEd8/fvP4Zff/4wfP/5i4GXm5Ph9cfPIEd8ACorJOiA/2RYbqOvwfDszXuc8k5APGP9bgOiQgCjtCIqAbIyKEmJkayPqomQHEC1EKCuA0ZDYKAd8O/fQEcBw0CHwN9/A+yAEZ8I//77N3gc8PjFK4ZvP36C2aBqVlYCtby/+eAxijiIjwyw6SEiGyKiYNnWvSiGWhvqMCQGeILZj4CO65y7nEFEgJ+hozANLAbiIwN1BVmG0sQI0hzwBykEQE7xsbdk8HGwAodG68zFDHpqygz6GioM567dYjAA0iAHPnj2gkEG6NMZ9cUMt4D8voWrwGx084iqDf/+/QvHsNYOiC0lKsygKi/D8Oj5SzD/wo07DCpy0gxqQLGj56/A9fyDWohsDgiT4IB/cAy0Hd7CQea/fveB4cnL1/A0cvHmXbgahAP+oZpFrAP+ABXD8H9omgCxHz1/xXD70VMGSRFhhgtACznZ2RnevP8Eln/38RNYHqTuLzQNIZvzB4cDsKcBpOACBf/Oo6fAGASMtdUZNJTkGSYsXg1m+zlag8VB/JOXrzH4OljDffsHR7CT5AAvO0uGH8CGJbjZxc7GIAlMByB5b6A4jA0C0T6uYHUgvpiwIENqiC/5DkCOLzEhQaxycpLiKHw+bm4wBvFZWVjA8n+JqFOwOmDX6Ut0KwkBAgwARJcLEDF1yEIAAAAASUVORK5CYII=) top left no-repeat; }
		.dmg { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABCJJREFUeNrEVk1sG0UU/vyzxt41ifPjOJsfJ07jtE4axSEBHEGxS6sgVaKphEQRF4JU9cYBCSQQNw6cuCIkVMQhCLWiEgJxSUUJJcCBNGpom6pNcJIqbUpsUjvxT+K/LLOz9hJQ7NkFC0Z6mpm333vz7Zs3M88gSRL2tksfm6bqxWAIOtt24i62kytzZHj0uTO78XK4v69n3g80fOJb5oIT7yimLk8Qo2cuI3ztXSRWP/VHouGpyXPGiiT2NmMZnkyxPGKgYuYMqq657Vl4Dxzxk4lMwvEvCLBba0+QirN9QNWZ+E6InWO6SJj3V+8yCWytf6844Ip4srcGiwsmewshQT/5F8PTX8g5oZ+AxCYgdh2hvb2hv4gnBDgHjLZ2OhY76baEJs/98AnJh1erHoF0bFpxYCnhCQETD6PF+SdJJRLjhATKkTDvH4ACk4Dnsbdob+HdCl7eAgNhY7ITEtBMokwE2AQ2lt+jvVD/NBo6TtMo3F+cwMMHVwiZPCGVgVRIQsrFSW+CwDsIibh8NF/XkANsAuKhN2nPWd0U7/adReLhjbJ450Hg6uSYX2MS5pkECrl11LS8guT65xRv5gTUuQK6j3OZHMgxDWvbXoO1ZpCMrJrwOo8h26HBwCvnJb+tCV/1CCxMv4xHXeTNym+ivf/takcgyzSMR25QqXUOa8LrjEBlhzupB8hmlHEuJzHx/yACmcpv/9ZdZItrRlZnmXjdr6FUyFaUTDqGVu8LCL34FbmKm5n4kmiPwO5ORda7hRy2NtaQ2lwlWyEx8fq3gOFQdA8i9tsdzF35AIcD49UnIBXYDn1Dp6hoxVc1AtVsZY5h+n8mkE9jfvYSbhIptZ7+ZzA4chIXPnqDzg8PjaKPyNTXHyKyFqa602ffx/LCVfz83XnV7onQS/D0DOvNgRSpL5Tr+OiJcXLmd/DjN+fBC4JSA9gdZNEF9PqfoovL81QyjntLs3TxvsEgmkQPlhfn6NzpEilGFwEUb7fGpibae3uHcH9lXnnbm9uw8utNxKLLdM7bayiBFfL3Mq534Mmi7Si8vgHwNk7xqTkJSSWj3u/yWC69OKNaJwiCTXkPfl/9S/2QzaTgqHNQm1vXZzD/ywzVh0bHSBRa9dyECVpSqWMisjNBsBaTNIPOri7M/HQZjwdG1ArK4aihuOz2Bjo6WhE8drzoI0196NiCTfV+v/jZhPJXpB3wktAvLdFvtbVKFBobBaJTIuDr82Dxzm18efECnE1ORCNRZYtskuJTO4EYGhs4+HrbVF0g0E2SUMLQcDeJBAeetyFLvtusGbjddfD5RJiNSTx/cgRra1Gk0ztE342WFic4i1ygZvQQiKKhHkTse7RJWuW622UT+Y8TOHTQTrElnTw2m1Ccl2xjFWvcfQncCof/s4voDwEGAMErAZwENZiuAAAAAElFTkSuQmCC) top left no-repeat; }
		.doc, { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABKdJREFUeNrMV1tMHFUY/nZmdmZ3dne4L7CwcmmxFGgsiYkGihGjD30wUYvyYIJgUhN9IeqzNe0zMT6pD6a3xBfSxltq1TRFbamtGlMDhaJGrq3gUmAX9jazu+M/Z2G7hdmySzbWk/x7zvkv53zz32bWous60of41CtDQmHZk8hxJCIhouBJbeh03/30Nt8nmCk5WnO+H9GpMdj+mepd6ezBdiDSB4c8jmda96O+vLzX2tlz4oEAqKlyo+uJdtTlACKvANxlRWjc5cWLOYDIKwDF5UC1x50TiLwCkGwiiouUJIh6L7o6tgchZCgW9htevEXLBNWYvj4bFIe9uoHJ4+Eg1Lk/wEkyKy+B52G3SyiGkjqpC+04c2m4dzJDdQjmtZqcLZyAsG8WuqZCj8fACQJkd21KrvpuITR5Axbayw+34tOLw7g2OkEY41C1GEKhCPyBNZQqCuYW7/Sis2eFzN7M2gNSkRu8ZMfqzDgsRrysNoillSm5anjIKjKAFpq/WeKBpWXE1wJY+/5zODueBa8UUmORIe6rhHb9h/3ZhSCtWQl2F7nYjkQ0jLgaQUKNEhCJZtpHQ6RgXQdzG07PLraOLfvAyQ6I3oadlqF+D1mdRaRJT0cx1laXGE/1L8Ja6gFnkxkIdXkBekxlMm1hBmLd3i3nZA/ACHIaSa51AETRwB3Gi4UCsBaVw1pcAYvhBQMEecGQaaQj1e7dck72VWBkfNrgyeWcKDH3xzWiSBBaMACHZzcECo+6NM9yRFtZAEcgLXYHeAP0pnOyBmAxQSvKLkSYi3kEqTJEpYQu4ygEDnahTiWp+ZPeEStqTM/IIQSJLSTKCnQLqXMW8oJKeVFwV1ZQCp0XWBi0wBIkt9f0jOwBGI1nEwm8SH3AmgIhUPPZkElKKTlGYCA42QnB5jQ9I4cciJuyRcmBSNxPOWEDT+7f0BMoPwS7E7HwGqTi8oz22QNImLtLpjBIoo2KQdii4yp/CAkKDS/aM9pnDYBLZH4Cfr3xYJOOkf2gijCT5RVAvod5GT5oAKA33/8CwJn+59HWUMXWgXAUA1/9jI+HrkORJRw71IGXHmtkssFrN3Hk7CUEQlE0V5fh6KEDzM6wOXL2MgavjufWiIx3v0FGVxs4dxWVr7+Pvo++pEsPoMlThKMvtKO5qgR73v6QkbE2eIbN8cMH8ePELLPpP/Uts3FJfPK83EOgUz4k2P7K+BSu/D6Lg/vq0P14E/o++AyB1SDTGvhiGCfeeA6DwyPwlii0v8z4X/86gT1EOwiBdvetaCTk5r0RkrVgis/W6cA39Hf8LohpSaL+rRseoLUi8mjxujE6dZt54/DTj6b0jLXBG52cozyIoG23h/GbPcW48O6rZMsldbP1QPLDIvnE3R2PoK2xBt6yQgyP/YXzP41gZt6Hk2+9jAvHXkt+jlNS9r73CX3/reKd0+dwvL8bN6bn0VJTgfO/jDF+pmEx+3NaUFbB/hy21FSiwJHsbv5gGKPTf9+j295Uz2YDWPookG1oqfVg1reMGaKN4ffNf6dePNW5vQe0pAdG/py+b/wu/3bTlL/iV0kW2HkfCK4G/rNG9K8AAwD5lUIY8UX0jwAAAABJRU5ErkJggg==) top left no-repeat; }
		.dotx { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABE1JREFUeNrEV11oHFUU/mZ2dmZndjfZTXbNrgmt2iQmlSCtAQsNtIL6JEjtQ32xtOSp+GyhIIpVKMUX82h9qPRNRaUmiFilrVRRqbUoIRtjTdOkWZvuZrP/P/PnvXeTyW47uzsLCR64zLlnz7nnu+dvZjnTNFFLh98dvuwPigfRJpWLOiol/eMv3oodb6b3oD/BTmlwT1e7/hGfz0FNe469cnoIrUDUEo8tpKd2jmBwZz8Fcf5/ARAJ9GJ0cF9bIIStBBDwdiPg79zYEhBomY4tBSBLCsIdkVpRSxCCfaW275yauF0ivB7/gz81BeEoAqWCBl0zq8DM6tNcf7oEDh5FQDGnged4iIIEeOAYhCMAa4kyUislqBUDhm6SZUAjgAQ3h0DIg3CvgtT9Iq5NX0FscZoAI78bGspqGflSllS6G4ossxYldLxtAJEdXuZkfnoNBXJT2jwCDAw83QV/QGQ6Q8+E8PX330DxC9g9Gt40lqqrr0vG4px2zBkAmxpw8Rwe3x3ArT9TKJGpR0EsxNIYJo5JMjB7Mwlvpxt7xiIkMvbdLZB0OSxC+yrkybm9T/gwP5Nhe001EV/IoVLW4XLxGNkXZjXRyN7cikGk+N0IRWUChgPv4pCIF5FNVdA/EiA3bH+u8Q2hNlk9fQpkn2CBoF2geN0t7exCYAvAMFsvUXIx5xREuaTjLnkZtbJxHAGTaDdbK4t5lMlsCIYlC8QqadNcutLc1jEAs/HKrVXY0Ik+5iO1oMAjb0aCvpI1zWxoa5eDtiJAb5j8t4jgIx64RZ7JumlBku6iIKjz+O1cQ3u7ENi2oWHU79WKjlJeQ3a1Ui080mqWDjlUkgXyRUQGFAGRz6hY+idHJqQExedu2YZCowjUUiGtsjEselxsn0mWSfhdTJYmPL29TLrAMNdvS8Y1jRQX5ViKmiFoEIF6TV9QtNWhQ6c7Ijft87qznALQdRPbQY5TYGwXAJsR7SgF2xkC+xSQduoNDuKNFy9Ysve/PYq7qb8w0ncA4/vP1sk+OPKzpff3/Ru4OvsJxsfO4tSXL+DMoUs4PXUIq/m48xTQfhZ5L+MnLp3AQM9eBmbiuxPM+bmrJyGLPiY7+dnzTOfZXS8x/V9uTWFu5QYwBuac0kp6ueGnHt+oCDfqIBb/DZM3P2J8f3gve/6+cAU/zU0xPtrRz3QoJbPLjKf2F358h8ne/PxltmeFbTqchLpqsDRs8AGph/G5YsaSiVw1QtlChu3rbMk6uv9ttn/v8EVLZhoOI6BSg/UInBu/jjNHJnHx+of4YeYrSzbx2mVcm53E/L0Y07+3umTZPjf8KuNfP38AhXIW0c4BJrfrAs7uz6k3IB1UJD92hJ6sTkJyyJ3ErKUz1Dv6kCzU8Sh7JjLLzC5B0kF1auWlnIpPT01zLYuwQtBW1DTWcr/adtMftx+WLyeXNjshPmMrN5zOAY2+WLZjDNjMl/8EGAAeHq79x7LEMQAAAABJRU5ErkJggg==) top left no-repeat; }
		.dwg { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABVxJREFUeNq0V2tsFFUU/mb21c4+2i3ttlJa+gChoeAmgGKUUEkU/6gRYsRHig/8Uw2RpkZDIvEHqEUpSIKmRgyh8Meo/DNpizYSGgxWQNAKtrS0Fmi3Zbvt7mz3NTOeO9vHdjttZ8We5GzuPffce757XneWUxQFicR90dnizk6rQIrUH4wxvkzDx1C13DebXrI9o5ZS1QOLsMxhSglA7aW7MHjD7lujkRZ83jEniETitYTV5z1o7BN1G2e6jLcX2bDuPqubRAxE5n8GsK3EjtorPuJhBKLynAd0jkZxtH0ENhOP1Vkm7FrhSAmEJoAdyx04tCEbrZ4Qqi/cRac/Orn23U0RtVd9CMRklQ/QOM9qxDa6vUswYEOuJSUQvHaiAGucFpzalAsr3YyBODcQit/2+ggabwexm2QMSH9IwsEHs2kTkGbksNieGgh+LnQ2I4+69YuwlW63l8JR/ZsXj+Sm49TGXNrJoXUorK4xPZbbHAcIptRAaFaBkjSvLLGpxpjL31mVSQY51D+UjQMU+xPdAQQkBbaJMkYCCFho5sARwN12R4xXB+CbF4CchKCB4j4QlvGJ2wnBwE2u15RlqCH6vm8MpUYFvCLj59tjk2dECJiP9mWTFavZ4BYj0mm1T6TiARbj03RormCEK80wbW2A1po9YTxbIODlQgE3RiIzzsoSgBInsLnQjvo/vBW6QiAluCDHzOOj1Zk41BFA1SUf3l9pR4nVoK598JcfAarSF8h4OnmmPMsyZ8kJRl5fEpLnpnERldfOYisGqCe82z6KpsEwDt6gsERkFNuMePWiD5dHYzP2JbPuJExWFkmwvzOAzTkWFFMoDncHVfm+FXaaG3CsN4g91/x4nTzxlGt2Lyh6AcSSHowPu0TkUPx3FKTDSq5eSRn+p1/CSls8FFVFAsocRhztGcPVQAxvFsb19ADgtQFM8de3QugOyaguFmCh2meyAgLzZI55mt5Gpxkfr7CpujV/i+gMytPWGSuKTgBRSkLGPw1H8YM3irfpRk6q/Qn5bJxv4bGvVEBBugHvUY6w/YnrioYPjNoAgF6q35OeCLZTR1tGB87zJk2SidrhW/lpaCLjX96JoJ08sT3HBIHnZvSXWQEMk7+OkfE1lOGbMowIy0qq3yfqvpJ0HvUDUdTeCqOSQqY7CY8PRSHTTZ7JJONS6sYnyEWJWJNnxld0Xl0/NamIzhB0kOIu6p8sj0My7onYGW9km9BMVfOjrAMAJ5jxfIYB6TQeu4fbJ9OjAo8bZn5+AEowAhclTFDC/06SRh1qlmFAkunjAsgXOJWdFk6VMWZzmdKJjZmc6U3oJ+sl6jLWdCj7TE5kerNbXvzFr3zbF1YSqYf6MZNP0MS4zRudJt9zVVSSicmYjquhR0m2p5mEQ1SGwfGE2dLqh5Wa0Im1VtxPudHkieIJlwkZdNsrIxLWOo2qPqNPO0LYXy6g4Z8wTvbGn+aXCszIt/JoI92o3k44KDEAU+Ob1JSuBSTkUV3/7o8nRym9B+d9MbQRZ6XF+34bvYiMznpj6r7nlpjVyhuiHzaP6s0BD91IHPcAGzNm38VMdmHcSDl54wy16bPDMWxdbFZl5+iWI6RrN3PqnmEyunOpBY/nmuJn6O2Eg/TtJ457YJXDgKV084epKVVeEdE73hi2UG0/fTEAHx3cvN4+ue9ITxh1ZQL6wiJ+HZUmWztb0w0gQod2ifHNjetsU39CAnHZ8b4wXlliUfXODMb/M+xuD6rzvdeD6CK9b9xWVX64O4TPbobUNa1ezGn9OUV+VgUWgoZGobxWxM3rAcSUBbEPvSGA178wAGIz2+u/AgwAyZMrFfMyhh4AAAAASUVORK5CYII=) top left no-repeat; }
		.dxf { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABS1JREFUeNq8l3tMW1Ucx79tb2/ftOVZHmNsjMmy8cqcEccWNHGa+IeJi/zhY8Esm2RmKD7m4iYZSzQubpiYCDMacTjdXzPRLPMx4zYDGhOngEKW8TQgtBYKLX33ttffvcXSlgItJDvJL9z7O+f8zuf8ft9zLpXwPI/oJmkful6ZqaxFis3s5gS7gSMlD640Ln49JtGg0/dnQyuXpgTwwZ+z+G7KXetsG+wgiOeSnZdwlad+nMKQI5D04r0zPlwem8f+Ag0Ks1T1ECDWA7A7V43DXRYK6lw1gDMQwpu3ZsSM7cxg0Visw6YUIBKW4PVyIyrTFThDae2x+endAC0TZu22eGH2cNhfpBXfm3+3waRhUJHGIF8rQ4FaDqkEaJOgfqhtEKuVg0ksFGBfvhrFaXI099hw+GcrTlemQ0MQZ/6ag5MLoWfWD5NKhiEnh9ZdGej+xwUdK8EmPQMJlJRaCc4TxO1VIFZUWrFOjg+rswiExcu3bHiFUp2jZnBuVyZ65wK4PO7GE5QJYZygbblMAoMiDFFjUuCF4jRsz1GvWI6EAHyUCbtuqTBCQzV2BoGWSiMqjCwu1mSLYF8SxLdTnshcAUJPEBupJA/kEMQWHcpNy0MkLEEo9qjimtkLi5/H2UoDshUysV9NC7VT6k9RSd69PY9ihoeUD+HmpEcsYZDMw/GY9YaQRWPVrKze3TY4R+GaVtdAtMopSPuIE+W06zIDu6Rv2B3Ew7kqNGzWYNjuj4mTRpZDWi3NBB4p0uFYt7kyqQwEo1KgoiI1bdWhddCJ1/rsOLlNBy3tSGgtAw6xLIdocRX5dtDJSbUl1ECQj7X7aPcnSnXom+dwtNeOIVcQF8c94u4hkeB4vwMOykb8vHhbM4AQ/CNacIdBju16ORppwS+mvDhYpMF75XrwBHGQwHodXMoACUvAxX0wPqbF/w3waNuqhYZSvU3HYMwTwt4Mudh/6h4tOia8OEllqstVos6UfCmWAVh8vjrtx3U68++UaKCgK07o25POYk/UOMHfUKhCqZZB56QXo54gGjaoRNg1aSBAIhRMqHGn2YdDBSrkK6QR/3K228Dgjc1qWIjs+LBbnB/dnwIAYKcgb417Kagc1XSpCL5kLI+V4hhlo0Apw4kxD67aApG+pEvgJ9r3p/xIp9vv2WxWfE+lCUGfN7H4wS7FpekAxnw8nlzQS1IAl2YCGCfRNeey8AZ5rLXV0NexiEr3iTWAc2a6pFhZcgA3XTwaM+UQJOQNYV0tk4R4lLLYOcsRAJMEgEyKR3UyGEkdnnXsPr4dMMjQ0u9LAiAYQhl914VL7m60hCVwBhfz3navNqbv1T9cBMeL/s/GfPiFRFZXqEBtthxHfnMuGf/2gAcTK+wmIYA9bvwNEpFVUHIBi7NVGjz9qxPnR7w4sFGBGRKrsPiLPa7IvAFHEP2O8IvFH1oSb1UAW1ztv7EE0EcBr1j8uLBTC6NSgq/MAVTT0XqpREkfJh/uuBezFly4Jc2+UIw/aQArFwswR/eA4PvfH5CExwzQt7hCL0MXKTx6TlmaTDShfT7pTx3AHAcgZETwbVCGL85ROptu+vtYjhzj9FxPGrgy7YqMbx31kvnWLkIrF5u2x3NYVOlDdM8rw/fEHAffPqP4vOUnO+7s1WPiIT0U38+KPldoaYyUAPxxGXgmj408G6/NIm/hZ1tVlwNWykBTvxsdFZrIvBH6hyU+RkoA4KJ+rH5tW9I9RzKL9n/6t0+05cavAYDH3WqJAWzzdw3gPwEGABOQlW22oU1lAAAAAElFTkSuQmCC) top left no-repeat; }
		.eps { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABDFJREFUeNrEVk1sE0cU/myv1/lz7PyVND/FRmoFVZTaQglVggi5VdCq0JxBhnsCSPTccKt6qaAHJC4EKqVqKg69oVZl3UuKGqGYHEiJkHAgPzQixEns2F7bu30zG9u7jdl4nVR90mh2Z97M++bN9948m6qq0Ivt0ojkbGo6CYuSSyahbG2N4cZ3F8z0/m1PKKXk7uuzah/JuTlknz8PZUaGsRsIvdixj9Lj88Ptrg9hZPj2/wKg650WfPLhEUsg9hVAR4MHH3d0WAIhWCbbq1dIShJUIl3NqVNwtLYW5hpqatDZ0LDNNuD+7GxocxdOlASg6FmbSkGemeG9cPAgEhM/8m+x+yPYyLiiY3e16ESLuy4/wjBwEHETEIJZqCjr64iPjXGDXCYntd4pQp6dRXZpCa7jxyF2dfFhh92BWpdrx35mIEwBsF4kA5nINJSNjZ2eSm4h+esvQFUV1/358QymovPIKQrkbBbxdBqr8QRa6GoSiXhIHRmO0bIru19BPlnU17PkQoZS/NT8lG1tUGUZYn9/cQGdWvB4cH9zE0jLxXGRvNHIWiNcfh9SDx8GLHmASebJEzLuhJ02Ud68gbO3F7nlZWSmp1F1+rRhnSNPQAtif5sH8o0ZF44ehXNggH8z4gnBIJRcDulIxKC7WyubhOwOC6fq64f9kJ8zWqAT5+ccPT3IUjjaOjth83orzh320gDUQoPPV/hWPd7id3MLeeNdZB89MuibtfIBqEpZTT1yGGoigdzTv8rSLz8TKkp5/qtzA9XVwMICsE5h2kgkrKNEJDgpLZZ3LaUBEMFMhWIcS8vA4iKrILQxFoKs5YVyA+hNQG3tPgGIUQ6h/A+WkPKG7GSc0m9pL9IelC3R3U2MdVgFoBQ3ef1aa3pQgoU37O8V4MABqwCyWr+6qjWbzZpRQ42n28/yFTCjtXV7KxLYHiacMgfAGM7aXsUygGzRZSfp8dFLlAgYo1cu0NxcGIsQR2Ky9gh5RZHPMb2oPioqBSB9+plh6trUFMJLi4bxmJxGcGKCjLsgnfmc93nd0ak/9waAyeC9ewgvLhS90t6h8evGdc3o0BBC73+geYgSUvCHcVwOBPHVsWMY/WOyAgAZI4BvT5zgbmdyRXpQYPVoTy88VAv4qG6Irq0hsrKCS4EApC+G8PvLlwh+f3fHXmV6IGP4jVAhOk/lGXc35X7vdkgOtLfz/jq5euxxBF5XFQbHx+Hz1GOg8z1MnzsP/82bBD5l1QNGAHeoJAvPzxf+fdvpdfDuHYPe7TNn4aUUfPanCUSpeLlMT3agqdGwtiIAEp2kQEKqAcLRaEm9aw9+g3ThItaufrntuWWEnz2r4ArkYl03eOuWMQxja4ilktq4To8bfPEC/m++RqC1jetEqHSrLAx1JwvPPS2pEt4oPR6jtW+bqzgM/0v5R4ABAMVhWs13gzHRAAAAAElFTkSuQmCC) top left no-repeat; }
		.exe { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABG1JREFUeNq8V19MU1cY//XPbS30L7SlLbQpQoEodbCF4XQ62YMuuof9y97mZrLEmCVL0Oxpe3FPcw/GTJdsiXFGHxZ9kGWyLIaRGpBlKrouTEQHItWOP9VSKvQPtL0795SWXuC2vY3Zl3y595zvd873O9/3nXPulbAsi1w5cvK8p666ahdEyuyzBQTD82ePf7r/QD7can+0I1cPf3POw5YgV67/xR47/xNLxv9QiECuSvEcpcFuRaPd8lEhErnyXAkYtGq46xyiSIgmMBMMYdw/lW1/fvIMTl/6lfTPQlOmgrlCL4qEvFjHC9EYLvb0obvvOmptVTh6aD9u3rmPkQk/Vb1Ojxcb62BQl1O8Oz2MI4F8hVk0gXPdvei7PQQFw8AfCOLjL0/Qfq7NiUwmg1wug0qlyI4phoS0mErldO+rbWAYhaBKCQGpRAKFXE5JcJHIpMNuqhBMR8EIPJp+grINSvQODoFRKARxXAQGRx5gzD+NFCGcTKWwtJRANL5Ix8tlUo5EiEA7CxLIPSpOXPgF0djicriFCbDE4TXvXV5fx0vNaNtUn35HM77r6mkpKgKZ0+pfkutkijhetXKVksGBNztQX2PhTj+c6fYQ7CwPs7OlCXu2vkCwitK34YPJAAk7s0bf2dVOnXNSQfb+J+/ugVZdxsNscTkLOi8Yge3uBtiMBnT130JoPpK1V2g1vDN9A3HosJgxPhXI9nlHfZCQonRajOIjwOao02qCyaCjacioj6QmF8OR8wfneJg7E5PouTXMw4mOQJxU8e1/fAjOR3kF+Mfdh7SyNzttmAk9g8d7b90C3Wgzr739xBDgnvf8AbASKRSr8nlteJxqRnLtSkZOyTvMlaURIDuKCiOTo72pFn1/jxV9V6hVSrz9ihuLS0loyHtmLnERyMmYy2bE46dhWPRqzJPzYMQfyDuh3WQgp6GMKgu2tF2QSvJpv7bJufLlE4kjFIkJTjgdjiC+mCDRK+6iXZ9AnrxFEqnswVRr1GFzdSWm5hYw+HAaNQY1GiwGyKSSvHMUXYTryb4ttWSVURoJs0ZFsVXaMuxwVUO7fBOyRToXJJAsUDlGtZJqLrZcKS84TjSBp6E5PAmFebZGpx2PpmboOVCp1yESi9M21y+EL2EbpkPYTz5Afvb8zrOdPvoZhsd8uHx1AF91HsS3P3ZBRci4HDWCeNEEEssR4Ig0kBUc/vB9nq2jvRUD3iGcIs651X9x8APaL4QvIQKpbDFxDo6fvUDbNRYT3tud/mfZt3Mrvr94mT4N5HLixuTDi6uBZDK7HVVKJers1elbUK/N2nyTM/R5f+Ix3igCLy4FyZUUVOg02L29jWcLzoVx9aYXb72+A1cGbuDPkVG4XRsF8aIJLCVWVjTq86Pz2Kms7esjh3Dpt35YTZXY1tqMhVgMXb39cNqsgvgSIpAm0NJUTya2rLFta2mmBLj3jpdbKYZ7F8KXUAPpsGnLy6mutjmsVTxcps2QT/L18KIJeEcn8H/JfwIMAB89dfI9JzO2AAAAAElFTkSuQmCC) top left no-repeat; }
		.flv { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABFpJREFUeNq8V81vG0UU/9mxnbWduP6o7U0aaOqkzqcCKQgJqFRKi4iEcuIACA7wFyAuCCEkOABqT5w5lStCogc+ikqlkgIVEYIUoiofdhIndtIk/sjWXn/Gu2ZmULy7qRN3ksCznnbn7Zs3v/fmvTdjU61Wg54u935yMzgkPgdOkjdl5JPyF+8tfPDWfnq717M0Urr40Rjv+pj+6g7mr8+9eannYzQDoSczjoqIY+GxfnhDPgriyuEA0DBxMg2sr9uLx14b5QJhPqL1GbUdd0IcELlAHFkEKNvsVhwT27lAmI9wfZgtZrS22TQQrzYH0RCAqta4mWahyWRCi6VFAzEYxAgB4dkHRMMyrCk1/iJQgbvf3EX8jzirCFVRsV2uopQtwUlyIxuXKAiJqL7TFICqqtwATj57CvlU3iATCLcT9g92oOf5MCYu3Xj8P4uA3eNgzEuNI6BoEaiWtlEtViF47E2NbcXTqOQrKBfKSMVSrO1S7jvXj3a/62AAJj/7FeKZTpy62FuXpaMpVAoVSIkMNuc3UM6XkF5OwyJYUCLvqqJAIUlB7dDtFPs64PS2cQCoagCEYwIWfoxg6dYC8a4MhRhsdbeS/ZbJU4CcluHqdCF8vh8OrwPuLg8ryZWpGB4dPYlsMku2VDXYbApAqSr191YCwN3tQSGThyPgZB4JZOHO0S5a43CdcGNhYh79Y0MGG2szqywCTo+TPfU2m0dgW1O2uWzoeSmM2MQius+F9ihB1TCnLiML0xzwdvke+L5/BHTKdp8D61NrkJa2oDzT2IhKqobOSS0mkSeRymdkbETWWTXlUlk8/cZZg00uAKYWMyLfzSE4KjY0sjm7QZrPMqI/z7PtKRfLLAnFgU6sz91DJpHGU68ofGWoVLQJFrsFltYWlKQScqskochP3sxBWtnCxtw6KsUKzDYzAmERwb4gvN3HYRWse9rjBtBCSotmdTZxH+lIklWBqiowWc1w+tsQIodOx/CJh17wIQFUDePQi6chLW8ht3YfTnLIUI895PIhuISG+ofuhLs9sLXbEBgOMj6op1wAqmXNI7vbTlioj4skF4pSkey1B9n1HGnV/+ruHtN5tDPmiIwfgC6k4qCI3vNaG47ejCLyUwRPvP4kJq9MIhPLMPnpC2H2PnNtho1HXh5BZimDLZKs/ABKVV2NK0gvpnH789sNt2pHN/77CkJnQ5i+Og0HObhcogt3vpwy2OIAsK3rCSp8pOWOXx5n46tvf21I1h3dxJ8JDI0Pw+awwv0Ibd0FBvxgOaCPADlEpLjEvNn9TR+B7FqW6QUHgvCHA1idSjT1fm8AxW0dAAUVuYx7f689uAUkWfW6i7eiCJC+EAj7cePTvwzf+AAUdBEgW1Ajl069bIcuvP9C/f36hz8g9ksMZ0hy0v+JqdnkIcqwoCGf/36WnYR6GaVr735rGNOMp7chKqfP3fp8AGRtsiQ3LqPEb3EuORcAeov5v+gfAQYArQrnb6qUzVMAAAAASUVORK5CYII=) top left no-repeat; }
		.gif { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABB1JREFUeNrEVu1PU2cU/11u6QttoS0lOG2kTbZElA+NCXGDD8I2ls1sCc7Ez/UPoM6/YNsfYHS67YOZgU/6bQO/zIwPlMREQzWpjIljcTA0GpBq0ZbS3red597e20ILvX3JdpKT3ufpeZ7ze87zO+c5nKIoKBXufHSmtbNzCDWKlM1C3tqawJWr5/az2+3PUsnIPTBQq39kl5YgLi9HhOgYqoEolRY0UfpDIbjb2yOIjo3/LwD6urrwWe9RuN3mQTQVQMDbgROBQ/i0txcukyAstTrJP36M3N276rdjeBiWYND4z9PWhsNerzH+dXExkq7CiYoApBKmypubEObntQzp6ED21i312z4yAq6nx7Bl7Ha0tqLL7dL5bgrEvhGQ1teRuXkTSi5XnLRa1Z/t2VnkEwnYBgdh7evTNuN5OG22kpzTYNzeB0RFALJ+KnJmP3UK29O/EYh8ud3WFrLT02pkWASmHs4jvvIPJFlGThSRIeDJdAZ+uppMJh1RomMpWnahKgC9WHDt7RDv34ci05jCy4SnVFPevoVjdBTSy5faArLjfT7cpmKEUqBWioaPqQ+2UBDb9+6FTUVAByCTI+HJE9V5S6cPcvIVrBRyYWEBudkYbB+P7FjH2+01Z07LXiRUNZ1WnbeePAnL+x8AzjYoLhf448chpTaRf/SoaGtCTZNQpjtUI9HmBD80BC4Q0E44MKj9Z7GA7++HMDdHyR8A53TWXTsqR4DunKlM5MHBQ8a49Fuhb67TD/HBA2OumpqvA4UIVJWewwDVCOlv4kkwVFcEKtcBWTK3mtitZsfyMrCxQc+oG1SDqUQSGT2eBgBIVSLACtPTVWBtzUhPMMIyxQttbCMQ9CagCj/MRUCiMaUkWJ6/fg1QAVJFd77XHn8tAceOEXv5OiMgUFHZSJLTVxoIY5XJN4xxaW0d6O6uFYCo/bLTpkg5zrzT3cKV7GcegFQ8qdPVWJPA9pCkOgE4HJo2KjUDEMtD5qGXMez3I0UZkEgmd8zFnj9HkFKQqS4rRFqm9aXhLgCRI0cw/uFHO+a8139CmLqfmc+/APfjD4i8+x6+pvKsy7fxOL6JzzUOIOzvUp1foAbk8sOEOp45cwZD3QfUaBj2herJXfmuCZVQKAIYDWk932XqCzx2GzxEqtNTU1h5s4kg9QGGfaF2KNHzGpCLFxsAIArlNYHmwt53MHP2rBbiO3cQW10t2hfshm/cKN+j5rZcEAydXFxUp76iHiBGj07o+6vFIqPnN7MtXAGzYVq6h6Hmr6BonHj2FOd++Rnjp7/EpZFPjPnJPxboShxFez3VBKEJHMjvbEAnqPGY/H0e4QMHkdrOIvFCe3A89OoNX7um2k/ENZvda+skYfkpUjQXe/PnnnMr1MI3rx8QRfxX8q8AAwC7ohz5ZBJ6IAAAAABJRU5ErkJggg==) top left no-repeat; }
		.h { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAchJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/6vm73yP1D/fEIOQMZMDFQEBiryDJLCAgmEHIEMqOoAQX4eBisdVZIcQVUH8HJxMogJCUAdwU+UI6jqAA42NgZBXm6oI9QYJIUIO4KqDmBlYWbg4mBHOEKXsCOYiEmpxGImRkYGNlYWVEcQCAkWaobAmRv3GO4+fcnwD+iYv3//Mfz684fh+49f4LTx+uNnkCM+AJUVEnTAfzIst9HXYHj25j1OeScgnrF+twFRIYBRWhGVAFkZlKTESNZH1URIDqBaCFDXAaMhMNAO+PdvoKOAYaBD4O+/AXbAiE+Ef+mYCplwOYAQXr/3MMOrd+/B7Gt3HzIcOnuJoB6iHfDv33+CeMO+o0AHfASzr99/yHDk3GWCeoiOgj9ERgHIVyC1IMP/k6CPcBr4+5cozX0LV8HZqvIyROsjwgHE+SQvOohBRU6GYfuRkwx3Hj4hWh/hKPhLbBT8B6uFRwH1HEA4KJVkpRhYgQ1QkFp+Xh4GCRFhovRRzQEpwT5wtQYaKmD8h95pgGYl4a7Tl+jmAIAAAwC4QbCaDpQyqwAAAABJRU5ErkJggg==) top left no-repeat; }
		.hpp { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAkFJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/6vm73qP1D/fEIOQMZMDFQEBqryDJLCAgmEHIEMqOoAIT4eBisdNZIcQVUH8HJxMIgL8ZPkCKo6gIONjUGAlxvqCFWiHEFVB7CwMDNwcbBDHSFAlCOYiEmpxGImRkYGNlYWkhzBQs0QOHPjHsPdpy8Z/gEd8/fvP4Zff/4wfP/5C5w2Xn9gAjniA1BZIUEH/CfDclt9DYanb97jlHcC4hnrdxsQFQIYpRURgJ2NlUFJSoxkfVRNhGQlXGqFAHUdMBoCA+2Af/8GOgoYBjoK/g2wA/7SMQ4GZyIc8BBAdsCm/UcZrA11GIQF+Blu3n/M8ObDRzD/6PkrDG+BbBDg5OAAi4GqYVziJGZDRBRs2HeUQVVelkGQj4/h+v2HYEdY6mszHDl3GeiYTwwiAnwMj1+8Yjh37RZDaWIETnGSHPAHLQpAIQISAznsP1QeRFvqazH4OFgx3HrwmKFv4Sq84iSGAKoGkCEwoCovA5YHJdTjF6+CLXn88jVBcdLSwN+/KPwgFzsGGXERhpOXrjO8+/gZIg/0qiAfL4OyrDSDjqoSg7meJn5xUhzw+w+qiyVFhRkUpKUYbj54wvD24yewPCgyQJa4Wpmi6MMlTloaQHMxqH0HEvsHbXyC2CD6H5SNXoZgEyfbAW0FaXAxRzNDMAaxk4N9sDoWlziJaWCAC6Jdpy/RzQEAAQYAgGvRHyAyjTUAAAAASUVORK5CYII=) top left no-repeat; }
		.html { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA4tJREFUeNrkl81PE1EQwKfQFkpboEChUChI+SiCgCACUQMa8WD0YMLJBIOJEg9+oP4BxguJIRijMfEGkYPEmOAJgyhgjPINgQhVPhoQqEWFypcIdHd979ktbdnSblPx4CST93Z23rzfzs7OywoYhgF7ufmgvk2rjioGnmJeXoWFpZW6u1fPnd/Jz3k/YrDXG/cftzFeSHPXIHOn/jmD1te6A7BXP/ChpMRFgy5OVe4Owl58CqAIlkGGVsMLwqcA8iAJRIaF8oLwKUBggAgUcikvCJ8CCP39IShQzAvCz5NK9VT9BAIQCYW8IIS+zEDvRwNMzM4BjWAomobNTQusrW8AAwKQBgZgiB/I7bpbAMaLzQ9n6cD43byjz6PGlmyPMrCtW3HI3LwZosIVZL7ycw1k6AtIjInkDc6rCL+ZF+FpcztU3K6B1u4Bm31y1kRs+B724VW4nmRgaNQAHYMj0K8fs1oEoFFF2vyCZVJie9XZTzQnLRmydVrI35fmJYB1bO0agN7hUVhYXCbXCepoFDwJDqSnQliI3OYXq1JC1bULyPcTghwHw4yJ6Iu3PZCXkQrH8vejHiHmn4F3A8NkDEebnSouBN2eOJdZwm24pDCX6KRxDlre95E66RsZg73aeIiPieIPgFvr+sYm0DQD7T2DMI/eb3pyAoSQlG8X7PthfJJkbQkdz2LUEwLEIggUi10WNicA+oSJXD57BsanjTCCgk6gsWNQTzRJo4aCrDRQKkKJ39LKKnQO6WEY+bGiQU+Mnzxdm+AQ08Ma2KLVoiMWK95EP/EZ9IYpMCAYpSIEIpCyAPqJKZCg95yWqIFsVCfB1iwxbroKdwao7bgyiYQUFFbDzBeSWtYP94DjBTmgQ5vvFMNzADeNKEGtcvCTIoAUVKA0w7+Het0JfXaCchkpmv7vAXbvFfi5ygDWi7eqHeZTRhMZWW1oek3G6toGco/1W/u1bvNn17vKKjcARRF1nofKZVBZVkrmpSeK4GDmn8NmdHIaBtBBdQmdiPaCj2l2PRvDQwCaKJZ79c+IYhGLRJAYG0PmMcpwiI4IJ/OqygqYNn0lo2NHpW2xKIpHBizI2WJdUFKYR9TZjuvEYhcUH9lctcSusbgAEHIDUNuajrOdIkG3rp2fHsvDJ41kPJKbCaeLD3ECCLh+TstPFhX/jYqva3rTXnOl7Kj7z5D6x33gZffQrgH8FmAAw5hI/7HfF0AAAAAASUVORK5CYII=) top left no-repeat; }
		.ics { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABPJJREFUeNq0V1toHFUY/s45M7PXbDYmaZqk2aatNmkNgvWhvvTBglXwQbFEwVqkEpWEKHmJwSYIlRqsrbb0QdA3hQgqSB9aFAUpfeoF8YJgW5PFpGmM2d1c2GaTnZ2Z439mNzaX3WYKm7P8e2bOnvPvN99/HSalxPLx9idD37TGGp7yGXrGcSSDhyEEZ1Mzc0Z8YuozwXkfHSooXTou3WtGtx+9eXjFWW21MkPTIgef2FtRGQpW4D7GzzfimJ5N995ZNHNSOv2MecIOXmTNMXMW7neYloXW5kbsbm446kj5ITHrCYGGMg3pAJXhIHbF6mGaud7rtyZNweXAekyUDwB9wgE/NlVH8VjrdjI56795a1Lpf4cwyA0HoP6CHBfRcAhZMuGenc3KBfuuj01anKMkE2VlQNcEgsRCNGe7gPa0bFPTPZlYC0CFpVy6lJ7+XD0dhSwEF27gVUWCMHQBwxDQNA5NiL4/Rycs8suBDWNAo6f/Y2QMC9kscpYNy7ZhEhMZuvcZGvmHrz+9kM3Q1kHPAKRn/iUe2RFDJOiHTUy49iZhBXZ0wbH34Z04d+nqM+sDYMv1Sq/+hxDZvm177J77/IZhbpgJHMcpnaopDJRZij1QEQB3XdUrA+tbSBbixHMY5jeXE8Cy4EL5ndCDj3jOhHIF6nIyQNHhyQdoj6PEyUs5hgosx7sJpFvaZEHKwwBK6tJKH8hL+QAUd4YiiYipsgZGuVxJuUzgAhHcgxNmMhgfPI05Owebcfe0XN7hsYKVVt2X2reUWdRvVm2DBwayJqa//BqLMzNwdN1dsuczLjMiFII0TdgLC3mmSCv3+8B9PurjTEhalwo0eRynusB1I4+KvFlttzte82YCfzSCAJewqcJJ6vWqXzpIfukgde48RE0tatqfQ2j3LiyOjiHx1bfIJZLQm+pR++Lz8G/bioW/4u66fecOmVEDsx1XL9c1T00plKk0JYSaWybqjxzC5pdfcK8fPPU+mnq6IJjEls4OtHx6BrpfR/NAL+ran4WTTCLW04kdJ45BSIvEyeviK+pcaQbUJnpwKLCMZk4o5OIinLk5VO17HFUH9mP4lQ7MnP8OlU/uR9XTB+CLhhFqI0ZG/kbi8y+QuXoNvqZGGBFlMssFK/MV2gMDtEnTGTUYd4Wrw1aOaG+BeXsC1vAwgkR57vffMNFPTU4qicnjgy6Itos/or6nG7nxcQjqP3QfdUVC9QSsKAO8KAO0qhcOKVGLjFCwzDwENZ1G0IBIzyHQUEd2b0cwtsVVNHboMMaPvAo5O4PYyQ8QaKyHsLLEJnNBwBMDLgC2TGiTYUCLRGBeuex6fE1XJwItD6FuoB+bj70LoyKExtOnUNvdBSs+AjMeVz0aNGrFdGX/AgDOPCUi5CmjQ4xmSUqcsVE3GjB5G6mBo4h2v4Xw0BCcdBrTx9+DM3wDsydPIPr6G9h64QIkef/c2bNg//5DbFE4uuWdoZgRijshQdUVXBUO1Gqlz3zsZhVfTTXMSxeR+vUXiLpNsBMJ1zmNB6pg/vA9ktcuu2FqT1MOSSUoOgL58FN1BcWdsAgAycKOiYiThblUDtOZ/KwSk5+OzM9C3kyCEc3w6eptkmbKGbMpcsgp9boMptZlzs2GvFCIBNa+L64BYGqG+OnRffDlTDj/G42t6hZYiXajxLrMf82EK9cUl/8EGAAluAAKvGl1cAAAAABJRU5ErkJggg==) top left no-repeat; }
		.iso { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABJlJREFUeNq8VutPHFUU/zGzD7rLwuyDXXZXyNbFgi0qmEKgD9rGRyVE8RETTfygif+A/4Dxo99MjIlfaqwxmmg06SOWQGpolNoIDWgToKWFAi3v0h1YFtjd2R3P3JVlhtmls6vpTU7m3jPnnvu7v3PuubdElmWoW+9XfJ/Lf+IkCmzJhIjoyt9/UffU6Q/TYj673esxhVp6znB9chHt4dwVuf97t0zzh0mEvQCohctjVpR4vI14qra9kQZ9e4FQNw7/YyuxeOEPdRUEIg+AdBEio8QsgC87mAERNgaCyxOowoVAlPB2cKXVKhDHHwnClJ+B3E1cGsH9m79AXByBlIhl9XahHC5/CJzFk9X5QyzjG29P/K6AyHk6TLkJSOVcfOrGN5gbv5SJN4nZsvNvM3oXCNQCvINA7CQzYdoG8TV93zDGQA4A90a/xcp0N6zWPHUgCUQWfsOkUglkCXI6Tm5ikJMifXnYbcLrPWfEz+jvRwYASJphTByDOHsJFmv+ZDKZHZCkNUTu97Kx78k3KSz12f+VdcD1nq5GgwxoAYhz3bS4toJtRKNYi2RCWu4UYHM4qOfacZFYgNP3/iOPbu4cSCe19MYGYS1VMbK2jo1NJ1pe/YKNhy9/DN60SEAqsjap+AhSyVVwvK1wAGoGEhs3affanJh7GMHzL3+O20PfsXFzx6f48/xb8FSVaeykrUlYbPVFMCAnVGwoCaWlP01JZbWV01EcY2PepLjhdXZsrsqXcQZUITBZqpCMax0r+TA+eBatnZ9QLdjAzOgFptttx/HlGl8FhGAHNcfxVJackOKRrE5we7E0cw6LU31UC8qQlmbhDfiQiKscW53sZKAYBuS0dpLgewHzd37U6Jyeyu0sUQ4ZvQe01bPC16TzUwCALc243NOApekr2FyfN3Qr7ivzwx1s1/kxfhml4jrZ3/Ae5YOX7XQvUWwU21w+CkhCPXKeK8GB597BwswA5qcGkZK0DnmTFd7gswiGjzNbGNh9/hCkNnPTRXwFQs3w0UKx6CJWV+5l4u2uht3hA2+2sgso3/z/xIBmtzzlheBlonnGGdy1gSTcxONqeUIQ04z7Ln4Jrz+MA8+0o7/3LJbm7hDlLjQd6UIwdIgK0BaGr13A3VsDzH5/XQua2l6DWX2BFHIKlBiqhSihkprErRu/0kX0AKc6P0BlVQ2G/zjH/g9d/RmRBzM4+uK7TJS+otP5McyAtK5/IVFREVxeJBJbjIFgdRihcD2znZ0eQ8uxDgSeqPk3h9ow0N+t82M8CVNR/QuJSmog6MfRk6cxOzOJkalRepdxeKnzbTr/WzCb5Ow8pa/odH6M50BUdz0rT6zBqz1seLj1CC1wEOd/+oGxITidGB8dgsfjYP+VvqKTiwewupsSUsbhD7hwrX8QYmSFQpGE2WxGRQWPpw/VMv3l7ouZFxS9lNqONefwYxhARDOuqRFgt5vhqSxF+4kGLC+LtLgJgYAHJm4d/qpSvNJxGNPTC8y+tbUWNnupzk8BAJa1AKoVM4np3fTsc7u2Xz7rpMsk2j46cfV1en1RAEYnJh5bIfpHgAEAxCmxxOZWjoQAAAAASUVORK5CYII=) top left no-repeat; }
		.java { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABDdJREFUeNq8l+tPm1Ucx78tTy+09EZLGQWMUNJQHMIcJFOZYxnZnJcRfYdxy4jxnZqob/0DnEZfqC+WGGXZjCGZcWICCZLJdFtcRQuDjVs7oJRCu9JSoGtX2qf1PAdLCq60T6n7JSfn9Hdun+d3vudSQSKRQKp9+OWlQWN5aSt42sr6A/jXghc+f+9M527tds5HHanpgy8uDiZysH7L7cS5Sz8lSP+uTACpSYg8mqmyDLWV+85mgki1vAJolEXYb3yCF0ReAG6NTtBcISuEvljNCyIvAFctVppLJSJoFHJeEHsGmHW5EXoYoWWmoAAyqZgXhDAbpaZLd+yz+Pjbbrz12kkEQ2EIBQKIGIYXBJPrl1/7axRdPf3obD+OywPXsREHNmIs7rk8iBM4Nh5HNBpDOLKBBASQSyUcRIB0fT8jQCLD5OMz87jYexXvdLTj/OVefPT2GyjVarC4vLJrv/NXBhqzisB/TqsdNu/2or6minyVFAwJeYlGBalYhGqDnnckcxLhQXMNAsEQdGRihhGh+5ffcxZxTiLUqhToPNWGW2NT6HjxCCJRNivRZi3CdAswNG6Hy+tHs9mIilIdBeF+v9zSBPuCmy6D5a6NAMXQcbwlqwjw0gB347n9q/h7ag7jc67kDQiDToMyIsImczVOPnsA5777OaOOcgJoa65Hzw0rWFJfQy4ezjighxtR6NQKLPkC6LeM4dThpr0BkC2c1l5oqIVtwQOHx7/lKyViVMplBCSG0ydaMo6RhQbS03OHzfDsIkpURVu+GQJzrNEEg1a9a9/sI8Bux39ATrObkw6oZVI0Vhnw5tFm+NZDW/VahQxipgDroQicywFUaFUoIsdx7gA71o8pEMJQrMJqOIKhmUWo5VL4gmFapyyUwOlfR5RlISB3gVwihsO3CrNBlz8RMkIhzOWbA3rWQgiTta4oliCWXGhBFEqRFCLSrpjAKQvFexMhu4uCdEXSjIOy2SowF4B8WxqA7eHzBVY3xaZW0bIvsAbTk5Xb/Jw53fdJWUnrOavcp8/tLuAikJo+IY+OG9YxWu4ZvIlvfuyDdXyaqD5M6zh/MBTCV99fgUQspvmnXd3UlzpO9gBE0akpKUyuPDXrxKGGOgxP2mAo0dI6r38F1gkbLTsW3TRvMBmpb+c4WQLEaZpf8tCnVnJrjkzYablcr8P03AJt87SpmkJNzznxetth/DFyd/OJTi6q25P2rbFYlkcEYqQxl77+oRe/WoapTyISY2TKDgOZfH7pPvWNkAmanqpF33ULbA4X6oxVGJ2ewX7yWIkTHS14lukyJcfjAcDSdKb9BIbuTKKqogwH6ky451zEq63P4dihgzT3EhHqyS3I2fPP1MO7EqDlV/5tQ19PBDY53qNM8Kg/p2dfOtL6f2y5C32/Xfvs3dNHM27DdOF6bOfAwJ+jjw3gHwEGAHqNwb/xcTeoAAAAAElFTkSuQmCC) top left no-repeat; }
		.jpg, .jpeg { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABCFJREFUeNrEV+tPU2cY/x1O762ltgtMRVdcCLLEjAW2GLy1JPtOEzPjPrVm+7INjH8BS7YP20fxD9C5GJNt7mK8JcaA31iI0pEwgaB0KCKMabm1tKfnnD3vWzjtwULPqc32pE9Oz3t5nt9zfd8jqKqKYhLO9PRbA4EQTJKcTkNJpS6i73xsu3Wb9VlKLdrR0WFWP9ITE8g+norKPd0oB6KYalBFat23D06PJ4qe7gv/C4B36+vwYfMBUyCqCmDvTh869jaYAmExqyQ7Nobs8DDUtTW4IxHU+HzanN/tQsP6O0u1O2Nj0bUyOVESgFKUqcriIiRKMF4hdjtS167x/46jR4HaWm0ty26XzYY67w6drHIgSgLYUC/Pz2P1yhVurUZWK3+sDQ5CmpqC/fBhWJua8sJEER67A/DCMAjLtrVKFtmOHEH290Gomeyrtf/yJVLXr8N14gTf89sfIxhK/AVZUZCRJCxnMlhcWcUbLheeplMsJ5K07azxEHi9UEiJqqia5WJjI9TlZdg7OwlURlsn+v24Tc0IGlABYN5gHAjA8fZ+5rVWUx5QSJH06BFXXkMKlBcvYCOXSw8eQBodhf3YMd0+0eEwXTkly1AmAJxXVrhy6/HjEA8ehEAZrno8EA8dQm7mKbKTk4W1Bth4FVAMuSdcboihEISGhjxaygk+Z7FAfP8DSFSOqKuD4HZX3DtKe4Bizlih5MHuPdq77v9bQcDpRO7+fW2sHBv2gLzugbLU0gJ1ZATy7CxQX1+RB0p3QkU2tpuyn1fHn6PAk2lgp5+OUg8PEYo6pHkAchkPsPJjCufmtPIES1jGG8TyghIXoqUKHpDpPUk9JEs1Tn0BqZSuK5Y+NGjtw4dAczOBECv0gERC5uaBpcU8CG2XwTMsu75/m/zYAkAu/2TWLi9RUxOMK91MQpE84wDkgqVuz+tdEpgMWa4QANU559cl0wByOfxXZAhAf1cX7s3M4MuhIaiffa6NJ+iwity6yZ+91JqjB5rhs9kR/2cBsbt3EV9YqPBOyAAUM2ujrDuuAwtf/QlC3znE6cLS296O3rY2hHbtwnuXL+fHKfMvhDtflWPYA3SZ2HQ+50tzY5zFlP1n4/SLtryD2O1bSNBxzShGXqlqCLiiIg/0f3QyHwK6L0Z+voqu2GkkV1N8vv/UxwjR9wGvwG+/qTAEZF2rP0DCThUsVQoeCH9/CcLXX6HxfB/ilBu/jo/jTHsbnw9f+g6RH38oeLKYzYQgsfA3gt5aDH/yKYJ0sMTIUk1ITtYJPHvzBn4hsFNfdCNBLTsUDOIiuytsoVTXp0p9nMLl4h+nPqcDrW/uRvz5MyTT+ZtxqHG/7l33aUaJ6HM4t5ynM2RAPdcXNpyESXoOLI3rpgYmxre0Jj49XaU+YMB11aJ/BRgAins+q6BEqrIAAAAASUVORK5CYII=) top left no-repeat; }
		.js { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6Qjc1QkYzQkZGMTE4MTFFMUEzNjdDQjVEREQzQ0FDNEIiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6Qjc1QkYzQzBGMTE4MTFFMUEzNjdDQjVEREQzQ0FDNEIiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpCNzVCRjNCREYxMTgxMUUxQTM2N0NCNURERDNDQUM0QiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpCNzVCRjNCRUYxMTgxMUUxQTM2N0NCNURERDNDQUM0QiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pi3Sx0MAAAXoSURBVHjarFdpbFRVFP5maWfrtKW0bKW0wNiy1QItUBSFWohBKzFG1MiSIILGAioEMDExEoPIDxSBYusWksYdIv6oKBhXMFGREEoaEkpZCtTSQjttZzrLe/M85/a96XQ2Zgi3uXmv975z7rnnfN85Z3SKoiB0bNpbXzulIHe50WgAhm7FHDqdDt19LjS2tNbZTKZNLBuuVxu71q8Y8r8xirJhqx97yIYkR/PVdrRca98oBwJ2RcLalDhGhA59lDV3X78n2fPR6exBadF4zJk8YY2r37PbL8nCM3diQEKWhw8+0GaxYMGMKSgtLHjZ5fHU+CXptkYYcZcGuR5WswlZw9Jx372FfImXTjVfVmxm3bp44dDfLQNYvyk1BVaTCZnpdswrmYSywoJqt8e7J144kjago6sbHq8vigdkGPQDh2TarMi0p+H+4iJMd+StJ0x9EMuIuBjgp/Z+i0C27u09yK18CgeP/R6x3+XsRZrVLN4z0qwYNyobjnFjsGReGSpnTn6RPtzHRtwxBo7+eRJf//gbNq5citnFkyL2szLsOPZ3I7x+Gf1eL3pd/fD5JXh8fnh9PvZOtccvuenTLbc1QAl7suP6vX5Mc4zHO6+sEWuBEFCxFypKpwkmvLqzBgvLS1E1f45wuV6vR6rRSPvFOPTrX8sTMyAkBGp2go3cOzwzA929LoqvLQLVBjpoYdk0vG8zo8SRh/Kp90ToJYBeTwqEiqqYx3+dXcigg5lqMXOBHMDonOG40tYRXJOInsF3WU4OhAa6uc/vx6ff/oDT5y5gRdVCpKYYBec1AGqT1yymVKx+YjGaWi7jw28aQBQUOuIVFX08DHAM+QZnz18Sq+PHjgomHUX9TpuyetMJ9A177cz5iwRIv9ChJJ+KB54+SqWcXN6sXomJeWNw4PBRwXejwRDhAV7j1PvxoSMYOyIb2zesQjrRkXUEMZVsLQgEFEiE7HRKLNOLJqKzy4keopeebxVmAK85+9wCKzMmOygX2CATJljHIGCVxPKAhhuW02RZmYFuaTWZ1f+VYGYTB6QAFrNZUE6SA8FvNH0GfXQX6BEnBor6p+HBRbdvbL5IOcEHnV4X3Od3Ts9NFy7B5faEYEkZoiOJEAQGxBlsKuKnOvJFtntrfz2O/3sWFsKGts/vJ043YUfd54KqUybmD4RG3Q+oz4TLMWOcY6dlO25QCnJHoXrZ4zhDdCwgpHspxWr7/J4/ZgRWP/kIigsLkDsyB6FNjY5pGqMcRzWA461NbTh73RidnYW8+dlEL0m4Wq9WP5ffi7yRI+AgpshkeG+fO1KnLhC1J4juAQoB3y4QJtBDjSen5RQCGh/O3GfOWy0mIdPV0ydkwsuuLhCpKy4GBP1IuRxjMqj8siSM4LrQfrNL1Ihh6WlijfNBVDkl0RAEIFzJM3SYTSnoIyZs3lWLZY8uQsWsEmyrradS/Y+44dMPV2Dt0iqioRLMjBofWJeSaB6QFY6/HKaE2aETBYXpZiJjjpw4iS+P/Izva3eipbUNG3bswaQJ+agsn4kbt7qHyEoxWBAjBLJqwOAcXFPI1XYB0BxqQPlHyCeHGoTrv9u7HSWUMds6bw6RlVVZJbFERO6i9Ms35SdPH9EsKyNdtFxcHanNQGvbDZRTZ1T3xiacbb5Ejcg+bNt/gNJ1N6yppqDs4JSihiCqBziGEgNRVkQatVD6rfniMP441YiigrHUZnlhp0LzFbVoza3XcHj3Nhyvr6GS3YyPDjYggzwkdIRMkZYTo+FACZZULzClcqiwnLvYis8afsKiuWW0LpGrHThBBm19tw7nWq4QJlJFrzCLvOIkuoY2H4L/OiQRAhL2qwYwpdo7b2HzqmdQ9eBc4fotzz0rckHF7Jl4b+t6XG3vIBBex2vPLxNrbR03hWz4TJAFOit3NmmW1GCfz8J2qwWvv7BCNKhcePj3ASehxQ/MxpIFc4M/zzq7naIz0ockI9GYUiWlNfttDSB3df9yqsltUluvuzHYFtEvuPo7wvf+F2AAWq+5mvadKSwAAAAASUVORK5CYII=) top left no-repeat; }
		.key { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA7pJREFUeNrEVs1PE0EU/81uaUuhQAEBLYYaTYBEjAmJxIMEjPFigl83E0DvcDDxYPgDxMiJyE0TFA8ejErwYKIgRj0YDCRGGzFRIEq1GGhpF0pLuzvO7tJtC/3YJY2+ZHZndt/Hb37vzcsQSimS5e3Fhkm7g7bBoGyGCSIbuNf65OuVbHrb45nSKdU3i0bj49ccB5+v9PKbC/XIBSJZOORRattb4ThQK4MY/i8AzNUuuM52GAKRVwBcSSUs+xsNgdgVACFIEYvu/E4sheAdNUkgnDlBGAKw5KH4PE3hnqFYXqJpvJlACosYiGoGogGujtwgTHqDT72iELVdE8y7wUYCBMe2UkEIwMsgisGDMcG+uzrYY2yMgUDa05EeQLrNiUQJklFY7D+vXyDg/qg6kETQaARSKAhLeQX4xSWZiVX28+quGCjgSQ4NipU3z5UZIwD7DqXuorQJ+DrDH9XFwLZmhVCAAdBVLaqhxQwUl1FdG9PNAG+gXDmiX1dXDYisz/NEv1cpkoZGIwC228aYQwPxIW4C0TBzbskTgKJKCqt9W134WUPyEjjqKMy2NIeC10eCLgC8WR6Jj2E/waZAlFyHltm7msJWSXP60V8DUmaDCAu88p1oZz+6AfgXCKQYq/wqCqOSFoBEswFQ4u6Q8CqBbU+eAFApc8VxBRkcWWlWO2MMZLkQWUoB65pcB8nBwXaf3S5vDMhSvJeBcDC9rYAFRWpBUClPACQdjjizMX1jDGztjC9zglhLEPN+Ua9crhZI4aCylucpzWd1kenXKm9x1aM6r2lUG9mWvW4AoqimwNZ0AeYDLVgZ7oS9rQe2493wsbn8v6zrQYrN2uRtJSBXWKLoc6xzOZiOMDmEiGfWYApi8eNIlGZiPXIexe298D++jshiwtny3U5E5qcS3Y8Fr7k2oehbGPDN37MQ3t03noI4A3JRFextgOPiTWy4xyF8GEVyFyg90wdpQ1Dm/mc3lICBl0Own+yFyeGEd/Cc5ssYA1FoADhWA6HP47AdPgVz3TGEvyV2HGZsxHxqvqNrgmIXeD2CoubzCH4aR/jH7O6KUIwlqlvO3+87Pai61I/yjj78vHVO0wu+f5oCSLMPCYixEfdj+FYsX7nlIZ8Gkd3p5Ln3UT9M5U7YT3RrV3Jn7wgODs4qo+x0T8KOqrbxdXzoZyCq5s33dhT89ISyFgMCFga6tP8LA90pNtEVj2bnfdivsBBfZ7vtZgCw9fZ6Ur6vzyVyKrinMtKarLerGlhbp/hX8leAAQC8cZ87yOHNmgAAAABJRU5ErkJggg==) top left no-repeat; }
		.less { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6QzA1MTUzQTNGMTE4MTFFMUEzMzVGNzY5NTQyMjUzMjgiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6QzA1MTUzQTRGMTE4MTFFMUEzMzVGNzY5NTQyMjUzMjgiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpDMDUxNTNBMUYxMTgxMUUxQTMzNUY3Njk1NDIyNTMyOCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpDMDUxNTNBMkYxMTgxMUUxQTMzNUY3Njk1NDIyNTMyOCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PmqKs/cAAAZaSURBVHjarFdrbBRV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) top left no-repeat; }
		.mid { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABGBJREFUeNq8V9lvG0Uc/tZer4/Yjo8kdmwnjkRKTTA0avJACOKQKlr1BQlUJISQyjs88CdwvCBVAgEvCInjgaqtQOLBKoIXqirlUqBJSdUqiZM0zeEj9bmO4/XFzBQ7Gx9Zr4s60njWv/125pvvd8wuV61WIW9PTH72S/Bx9/NQ2WJxEbGY+PXCX2+/eRiucT2+Fej9d19Uuz4uXppHKLRwNjjxKZRIyJumDU/Vnf4+O+2Fb9BISXz1QASqFfWdMjgS8OLllwLwe00dk2jpgnKlotoF1Lcujw39fQYIAo/vf7h1FlB2B99agWpXBEw9ArzDTvC8FjpC4tJ3NxVJtCRQ6VIBTsMxEoM+O7S8Bq8LWly4uHAoidYuKHdBgKjG0aDiOBiNOrgGbeC1Gryh4/Ht+fm2JForUFbvggpR4MfLt3H9+lZdkVKxjJxYwIDLgpU7GRqYKXLrHUUCpQ4U2IlnkbiXw6MBN1bDO9CRNNDqNLCbddDptPtAlxl4xImpp0bw4bmr4/+LC24tbOL3mTCOTfgZtkLkdzpNSCby2FhN4thxD1xuC7pOQypdu/ZT6AaWFqMwGATmd4oVswWsryXhHbKBIzFwY24bU9MCwfBdEigdVGD2jzCymTzWwnGk03swGIV6vaBYu8PEFs/lJOj1PMxmActL9xB4rL9bAgcVmP1tGVubKZJierK4XpauVYbNZvcQjWTgIyS2NrMYGrYhQdzROE/HBIoNLqi2SYoK8T/FUn/n80Vk0gX0kN1zpMAX9kpN83SugFRqSrHWBavKsKIoIR4VYbEY2M6tVj20Wq5pns4VaJBuYMDKUkskOd2kAMGmU3lSfHgmuZYUH7rzHlIRi926oDELnjsxxkbq65kri0zuehASLB0zGUqOw6DHgmh0FyMj1kOz6XAF2khnIBF+4uQY5v5eRzyWu68AwZJNkx3rGIHtLRG9Nj0EHdd2HkUCksKDY0EPi3pJqjBsmgRfMrHHDiKXy0jI8IpzdKWAvDlI7tewdpvAuprnFQiU8bBamzqwvwOr1QBrrxGFQpH4XWz67xuykzHLsP0D+/Wf2gqFrmOgWL8+GvDj6WdGySt3Ft98eQ1Pjo9iYtKPu+sJXDj/J145c5yNtNHrWqOLU3ssmlFPoCDzIT2aacC53FZm7+szs/+0CNVw8or3wXshNp55dRKnTgfxxedX1b8VF4m8tU6P23xeQmQ7Da+nF/4RJ1ZW4uyFg96/XzdKrMuf/XVmiWSE9cBcnbtAFgOV/8778HIMw34nyfMUdnMFZqvh5IdOo00+V1dpSBWgu11cjODkqSA5ZqN1Ww0nJ1CzTU2PkpMxqZiSioWottidtR14vHZcDs3D63Mwm9SCwLmPX2MjddsnH/2sWJC4Vh+n0+M99Y9Th9MMk0nAxt0Ejhx1s9FIXkgabbT5hhz1eagtvysdmPvaXO7KP7NvvaAqBiKRVP365sIGG9Pp3SZbo/2BCtHtVemhVcJ/BRgAbVC5kaUn48MAAAAASUVORK5CYII=) top left no-repeat; }
		.mkv { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAALHRFWHRDcmVhdGlvbiBUaW1lAEZyaSAyOCBBdWcgMjAyMCAwODoyNDowNyAtMDYwMPHeMbwAAAAHdElNRQfkCBwNHDSkpWppAAAACXBIWXMAAAsSAAALEgHS3X78AAAABGdBTUEAALGPC/xhBQAABHNJREFUeNq1lvtPW1UAx/kjFhM1Go1G0YhG44PETOOyxLhsJG7uF52abBluwyyIAoMxxAHl0Qrl1RVx2LWlFAqF9pbS2/aW9cHD8obyKK9BS+Z4TBeDxvjL13sO9qbdnLtdQ5NPzjnf87ife3vPvTcp6a5f+mOn8FPaj3FTlyrH8UeOIinRHxEIj63HjTXPAuV7DYlLPLTABQtshb1oel+ZmAQVGA3HTQ8v4G8ewoh6ODEJIhAaCccNEZjpnkbIv4bhRCSowHAobnpyGSyy87iz+uu/Ev6HkyACa/5Q3FhyGCw7g/hraycxCSJwY2gtbphsM1a4Bfz925+JSRCBlf7VuGG+MYPJ7IK7koO7gkOfxAFHkQ1MVjd0J7SQJpfh8L5DD5YgAkvelbiZY4MYbBr6X8jaogQWry/vCaIFgtyiwGTHFCU6ux8ehQeeBg+6cozQZ7RCd04H3dkWjOhHab9ogTl7UKD5YBOsOT1Ce8oUgE/RD7aYhTHTCOUhBWRvVKDwqYu4nPwt8p/IxYXHs5Hz6NfIfzoXWfvOg5O76FzRAjPWOQHdMQ3qXpWjIbUW1SlSyF6qQN3bcpQ/X4LKV8pQnlKKmneq0HauDUwBA1e1C16lDy1faMFVcXB+76SQtUQLTJkDAp0nDdB8eA2N7yqgSrsK9XEVWk5oYMo2oa+qj45p5w8ePYegPa2BQ+qAvdJOIZlogfHOSQEm00TLjnRDTB6N/kzrPZn61DXYyljYJDb08pBMtMCofkzAWeyAraAXqiPNMXk0rad1tGQlLNoy9NCcVEO2vwINR+pRf7gWg/xLivSLFvBrhgU4CUfvAd0n2pg8guWiBZWvlaE4uQgFT+bRm/DSM/mQH6xC1QEZCp7LE8aKFhi8OiTgrnbjSmod1EdVYL9j4Sx3ojurC6qPmlGRIkHxs0UoffEyGtOU6M7u4rehN2Z+NKIFfFf6Y1C8VYf6N2tQ+3o13QWVL0ggfbkcDQdqYchov2f8/RAt4K5xx+AocUD/eStaP+MfLJ+2oOOsgT4H7h73IEQLuKSuPUG0gL3EvieIFlj2zmNncxsDjT5YC62UwSYf/ti+TXFJOaFO+oLOGVonZYCZpPWZnknaR9Yg7fXxFfECS9dn6aQ74Vtw8HvbksdgM7guHNReYhPqE4aR3QNaJmHOMdHxpH1zapW2SU7aI9o4dsECN8N/zSzRiZvzYQRM47S8vXKTZr2XegQBwmjLzzCe7xS4FVijOZNrxsZsiF5NUhf/OrZPY0DpwdrQonAQRymL7cXdq8DwZxbJN+ZCMJxpR3t6mwARioiR8oYvSHPxr2PrFLy1fTB9ZaQS4zo/v/202FrYFejmzzIiQM5u1jJB+yMYv+wQ/kJSevjXMclFC8yaJ+CWcVAfU8WwFQzTBTv4s4kIDCq9tBzgP0aix0au3u+/bEH3sYZmogUCXWNwSexo/qApBnIfkEXb+IdRRIDkC+w0dja2+Q8XszDWJ++j/aQvkokW+GG/Yk8QLSBLLt8T/kvgHzmtkmE2ZmxwAAAAAElFTkSuQmCC) top left no-repeat; }
		.mp3 { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABK1JREFUeNq8V2tMW2UYfnq/QKEtUFhHYVO2OWTCBtlcWJxzc7/NFhOzuGT+nxr9Z6KJhhhj4g8zSRZ/mBmjJppopjH6Y0NRYdME5Coy2oIMxqW0SGlLL6cX3+8rLb3SnhPjm5ye853v/d73+Z738p3KEokEMuVIV+9PbY82PAmR4lrzw+Xyfzw5/OILu+nl+lMWUup567xY//jiyzH03Zq80tb5AUqByBR5EZyiL/Z7vKsRrS16BuJGuQAKMpCIQ7wQgpbDVrS01ECptF8BymOiIIBYXDwCFtv6PUZYanVQqZUEYqYsEEUYSEgCoK9Qw9pkJudyqNUKIuVeSRAFAcQlMiCTyziIhkYTFEoFlMREIDixK4jCIYhJAECsyVhWy2TQ6VSot1YTCDkuX1bis8/HioIozEBMfAjixMAP309jZGQpzUhUiCHgD8NSX4nZv72sOjZo6pWSAKJlMOBe88HvC2PfQ7WYc7qhV8mhoPemShVUKsWOIjnHwzU4eWIf3n3vl47/JAR/TT7AbwNOtHc2w9ZsppxJoKZGD9eqH4tz/6D9mBX1DQbpfYBRV0z6b09hcnwRWq2Gx53pMiYcdjcabUbIKAfGR5dxsltNOkqJAKLZDAz97oRvM4jlBxu0Sx+0lGSpfsF0TWY9Oa9GIBCBQi5HZaWaAHnwyOE6qQCyGRi668ASOddVaCjDNRnlmuC6Pl8Iqys+NOypIoAB2JqMWF8P5tkpG4CQE4JEkaKIU64wXRbv4JaAzc0wKmj3MjphwqFonp3y+0AegMIIGANM10+ltubyw1ClxaY3nC7jmFQAESGaNa6zVEFJpcUc5TLAdN3uAJWenCckwxoKCcSEKs+O5Co4fa6V31msB/pnEAxGdpKQdCnxsUUhoJWoqdVT/EOw2Qy7VtPuORApjFyrUeKJpw5iamKJKA8kc4B0FRRzQxXFnj4vPO4gqo2UrFp5UTulQ1BiYWublbJ+k/TiXNdLcfd4Qqigg6iuTguDQVXShiQGMsVMtZ/SrSKHJqNa1PoSAGLiDyPxS3brA9Ht7K+ERqOi0gpSjYfyxo02U3pN6h0TDeVKncVAeeJDOByVngOXnj/B78ND8/jx9jTOnW+FhQzfGXBgkA6ji88ey1p38+sR2GdcePnVs3zMnH94/WfelER9FUcEgV9MWLJ1djXzMXOeOi1T859+chdv93yH8bEFPHPhKHT65J56r/VxJliuZNorC4BAyIVt6uZm1/h9r9WIlWVvGkBqvu3IXpw6dQCPtdsw+sd9uIn2N1+/iasvJVlwzKxm2SuPgYjALyaBQBjLSxt0wJjhdLp2GNie7zjahNNnDvHnb78ZSa+99v4t/q7loCXLnug+wJw57Ks4+3Qrbnz0K7ppt0kASZ3rvX1wOlxp/a7j+/Hcpcfxxmtf8bGF2vhoZF5kDpDxlAPm7P68hz9PTy1lMBBNH90pfXaNDM/zVt3zzkU+f2fQnmUvV2SF/px2d1TwP6eNNjP1+Ag3yJ7t91bS79Y9fhw41IDFhXU6iiNZNnR6NdfLnRscDfRPDF09U/ZpODu7Q63Xu5X37s/JxYK7inijaX1JjWhhRcD/Jf8KMABvVrSjMVvF5QAAAABJRU5ErkJggg==) top left no-repeat; }
		.mp4, .webm { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABINJREFUeNq8VltvW0UQ/nyOr3HipLFjO01b4jR3V5AmVRWBUFtRaETpS1UqoUqoiEeeEBLwgARIBYV/0D4BQqgPSEhQUKhKVW4KqUQapCg0sXNznJsvsR3f7fgcs7vOcezEqX2SwEijPTs7O/PN7M7sUWSzWRTSZ62fPLDYrWchk6LeKGK+6Bfvz3zwxpP0tvtTllI6/9GAXP8Y/+ZvLIy4rg/iBsqBKCQOB0UksObnW2BsNV0fPH7j8/0BoGmSyTSx9c2HcPJanywQ3AH5Z6Sv18PcaUaPDBAHlgHKap0KBnMNLAxEb0UguAP0D45XQKtXb4KwVASiJABRzMpmWl4KhQKckpMFomQZZoXsnqpg4s4E3KNu9i0KIjJpAclwEvqGaoRcQQoiRDTfLgtAFEXZ/ltf7EBoIbhDXt0EmLoA25lW/Dr4c89/lgGlRglTW4P8fSUzIGxlIBlMsFF7SFfWmGdqhaV/zeVHKpaCuHk72890oqbBsDcAozcfwtp7GLbzrWy+kdhAeGkdsTXS+9di8DpWEfVFEPaFodQqkYwl2RFSZvNIApaORtIjqmUAyGwB0NZqMXPPibnfZpAmUQlZEZpaDWL+KDgtzxzpSHbsLz+Nqvoq6I16qHRqLIzNw0oc0+rIkoAKbZYFIGSE/LeGAKgjLTYeiEFv1oPT8OAJH3vWBkOjgZRYAyZ/mkDngL34Ho1mWSal16/QZnkAG1vKaoMaLRfbMXPXgeMX2kvq0iwU7pEqSaBRb74T29effAQFyjpjFTxjy4iuRIrk26uGrvlnfQgtBZFOpOF1riLijRD/Ivoun951b9kMKHgOzh+nYOw2lYzCO+khzceF6d8dSMXpzRfBq3kYbSZSCUkEFtfAq3h5GRDSW8pKnZLUOI9MIoPgbICdf5RERpuOZ2qVRcupOZjbreS2W8iTbIJKq9rVnmwAPCkleo/Ci+vMaTqZJpdLgELFoZq0WHOXFY0nmip2WCGATNG87VIn1pz+XEnRZ7dajZrDBmgN2pL6++6E2yOgaTefsJTVOzAAmVQuohprDTlPJRKhJOHEjjn9BZNIkuUNEz0D0Q/MB/cAYDOl/W/2s3F+ZB6Phx6j+2I3MWrA9INpOH9xou/aqaJ9j24/godUBTu2F9rQ3N+MoQ+H5P+SZZIbjCmtL68zQ3ROnUtdTVofvjWMO+99D/dfbvS+1svketKS6Z5CW5J+hQAyjCn5HD421h2pK3orpPUjJ5vQdq4NR08dheuhi8mfudLDvgttSfqyAaQiKTYabUY47zt2ADh2+inyDnRhfngOY+QIbM+1oLapFnN/zO4DAHlyKeecCZi6O4nuV+xYGV/Jy6T1+5/ew+3Xv8afN4eRCMTRczX303P2nXNsfPXW1SJ7lV3CeKbgXRDhJz2g4wKwPLaUl0k6Qkoo0v/qypdstNgteOnjgfxcXhXEc2i/e+tb8g+QZhxeCCHhi+dlVGfo3R8QmAvk9QvJ94+XrZdaKw8gmtvkHffkZXFPbIdsccS9q2FqQ9ojGwD9vfq/6F8BBgA06BrIMls3JAAAAABJRU5ErkJggg==) top left no-repeat; }
		.mpg { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABIhJREFUeNq8V0tvW0UU/mynTmzHduJ3YioSx6nrhqShSCxK0xYhpILIgkplw4KyQmKDWIEEy4LCkh+AYMVDXVWIBRQJAlKrFvUdJ3VDguO2sX39SMCO42vfe82ZsezYiRP7tm6PNPLM3DPzffPNOTNjTblcRr194f/st4m3Jk9CpSVCcVa++Xjp03f38tuO19XMafzMJNTbTYg58ewMzqEViXrTolNGCxs65oN7fODszMi5rx+PAJNJZWHC2ob6MX56Ana/o20S2g7hczPZTHAddGHy7RfaJtExBVjRG/bB4jLDrYKEtoP40Oo06DHp60gcaUmiKQFFKasuLL00Gg20Xdo6Eu6WJJqmYVkuP1IWhH4M4f61+7yuyAqkoozCfwWYnL1YX1ljJNbJ88OWBBRFUY3vfzWA9ejajv5eL+AIAsMn/Jid+XXyiSnQ1d0Fx6hT/bimCshbCmxm8pAKEsyDlj0nyqVy2EhlkaWSS2ahlCtxUS4rCBwPwuyyqCAgbRG4NDML9/MDOHRmgrfTSymwYye1KBBgjgNnomm+bQqBFTYKtAAZOkrJQnaT93sODNAZ0ftoCvTR6SaEEojP/YzihghNt44mlXld26PjAEY6gAbGvXCOung0ugMDuHnhGv9lKvBMkZX2CciSXKvrCNBKJLKxf2F0mThg/7ANPX0G2EYcHDB6NYKDp8YaxvPUJNDq7Vc/Z2sCpS1nBvrM1BBWZpfx7AkfdlOsfkwlkBUYrEYeEx5SYvv3vbegzpkdLA8vR5GLZRv6G8BotSLle2o5ifXVNR54D25HEQ/HuGIu3yl1WVDPtsdmxPz5O7AHHTtWIRVKSNxN0OGzgoWLIej0OoibIsxuM4xWE8S8yMnttvrdCRTluoyQ0W3u5nuZuitwkJyQxVo0g3QkjSIB6un70IvDcAU8sA859pxPNQGD0wRJlLC2lIEwF4fM0o2yQLNPC6u3D94j+9G/39YWWPsECLDeRqcDSC+m+d4yJQy0LZZBKz/9mvk/NgGp2DihlmR3jrla+nWMgCzKlOc98B728vbS7NKO9uDhQRjoLGBWomBcvbXKj2xmZo+Z4qFCWAgLyMazKhUgSfVGPXxTlbyP/hWl/bbW2uFfwhibHmsY46SL6MpXVygmvJh4c6LWz8Zc/+46EvMJNQRKlDqV1aSXUzDZjeilO53V7T4H/86JXAzjHhX7iB1H33sJGnreMPAbP9zAA/YuIBubfg56eqBUx7StQDWahXASvfS6sZCsrF4hUCEXoDcAK8xSi0mYHCZej1z6h/8GXwvyO6OYE2tj2iNQ2CKQmI9j9JUDcPidmLswxyet7nWS9le4J6CULyFyOUJKVQh004o30nm6H4IVP/JhcdP2m1DaLNVSK0bB5Z30QlhI1PrYd2ZxInf7/C0s/DTP3w1MBWavf/4GPIc8+PPLP2oPnOqY9hTISzwTqvVMJIPYnVhDX+XOUGr1qn3/zrc4+v4xTH1wvNZ3lYJzu1/VNM3+nNK9fhJPwJKLwu8f/f3Jy60VyJbwtKwpAXaHPy37X4ABAGC+9Cq7Qyj+AAAAAElFTkSuQmCC) top left no-repeat; }
		.odf { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAz5JREFUeNrEVktoE0EY/iaP5mGSJqUllbSltUiKIohCTiqtBy9iffTkQVTw2B5EL9WDeKgUehKCJ0Fb71VELIiPYsVDBNFDbapQiza29hHTbJtsstldZ3ebTVJjk92G5odhXv/M/833PxgiiiLyZaKn443TI3ZCo6RZglQSD4+NTl/eSm+zPVMxJf9hXqt9/JoxIBqtvfT2nB+lQOSLARWUpq6j8LT5JBAPqgKgxtuG1tPdmkBUFIDBVQ9L8z5NICoKgFhsMHoaFRDdp8oCUVEAMJhAbLsUEC37ywJRWQCEAMZ8EKWZMBVPVn32F8dfYHXys3KBwEPkUhAScVjq6mCc+y2BiNHNq6UB6BBvs4BE/DkQL75fewCY/mg8WBYDog4GDEbA4dZ+cNsMZFjQlxNwtLe5RNjdG6U5SeuCTS8ADQ9hFonKWDRCMENDICNQ43a6aBRhdQAtewhMZg0AtLjA3VSo3MBuVEWrnBYl7ywLAA1osH8IMmmAT9GH1dBmoWs04JglAkEKenrI4gScDSLc3vIfURIAGyNILCsGpUCTjCcpmHRC0bNKRr3U93WiLgaLx4CQG1ppYFldhdvORhEsQ2Rm7NnIF/QFcVEAQhkvqHGIZetqd4FAsFNSnAEe1QVQfQaEajNQbRfwfM4Fzq5eODr7lPo+G0J8bADcQhjOTrre1afqJT+NIva4Xx7vvj1dcN/8Lb9GF2SU3n7orGx8MXgG3PwUPD2D8Jy/h4Wh4zT9FJCRm376+fCh8fprpCNhrL0flteX719A6ntI36eUzyhNMsi8Gwb7c0qeR5/egdHtg7klQAM1p5teimD1ZRC1J2/Ic0nqrzyCb2Babtn7NDCQcwGfYNS5wDAbaUpUANm9zfPZax35fzV9DETHgnCf6IVodslzx5GLEJIMkj/CaqZkdSW92PiI+lJza0Dd08xAhlP6lVcjsLYH0DYYQvJbCLa9AcyP9CMdj6uZ0n43LPfcSgRLz4IQuFwxy96jPQs4hTJ+lcHs0EVYmztgsLmQmuuVXSJRGp14AubLB/VM4mtIpVs6k5oLq/ds9dP5D4DC+fpM+F+dhQhY2or+kiZD26sDa+vijhWivwIMAKDMd2+CfSkwAAAAAElFTkSuQmCC) top left no-repeat; }
		.ods { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA9pJREFUeNrEl1tME1kYx/9th06ZtpRKay8KGimoq0YFNiGbiBrfWNR4SZQXozEaEx+8ZB+MMSbqmxoffdjd7O6DWfVlE5UY3WSVmjUYucULQrmooBbFaqAFepnOjOccoamAOFOrfMlJ55zvcn7nO9+ZOdUpioJ02XJq8W2r3bgGGiUelZCISX/9c7xj13R2E+fjpjIqXTlL6/zofzaM8Btl5+aTi/AliHTRI4tSVloBt8NNIf6cEQCPfS5WL1+rCSKrAPlCAeY7fZogsgog8GY48lwEolg1hH7qSs2gEb8czgiLyUYg3GMQawiEa1qIrGZAr9OD5/g0CB+qWCY+D8FlE+D/tnp0vGiDrMiQpCTiYgzD8TBsQj5ChhCFGCRmh74JwOy5ZgwMP8BAdIKC/9gWFNjQ2fp+hboMKNoBDAYdLDajZj9OzetyOhHjMqIjJN3RJExmDtZ849cDaJHBUAyyTKBlBa/7RvCwYYBASeBzDaQWFAhWDiXLCmDJM2oA0LAFTq+QenbDjEKflfmbBE5VzCkB5AnGsqRgNCIiKcosEFVzOXqMhEWE38eRSEiQRAU5Jj2cnlw455gnxdBWA2nesVEJQ6E4jCS4Tq9js4+EE4iRzy8F4wUDXEVm5NmNU/pnWIRpp4js5exC4RO9zcGzwhPJyvPs/CSfrz8FKlZgImC0aVmt+hqQ8d0k4wx8UwB5pgEkaaYzkAZgyrFiTn4JomIEwcGu1LiXjOUSHZWJOrvZg1mCB6/IWIzoMt6CH+f9jI3LD5KJLKzf87YFfzQcYUE3LD2AYmdZyqex9zouNZ1iPtsrjqXGz/v3oyfUou1GJCUV1ujk/sAlHLxciZN1m2Anq6oorGY6eu5vtP3OdGdu7iATV+MHVxUqiqrh77zMxh+9uoNVvm2peKozkCTGJa4ytvK6B7+xsYGhIO49rcNSbxX+e3KRAdBMUdveUABdb1rgsfnQ0FOHreWHCKwbna+bmU8yqWjMAKmB8TrgDWbWp83EWdinmj7TX3LxSenG+4H+Zpy7uQ+tvfXwkS3aU3U6ZaMegHx02l82YTQRwZbyw6zvtfpQWVyD1uf1rM8yQAOP6Urd5eh9G8DRmguoXFCDux3X0BFsQqmrnNnQpnoLxDHji3fPovanX/Dr7ibWpwHvtF9legqwvmwva+w+GLiGxu5bLGO7157AuiW1bPzfh3+n4ql/D4w5+B9fQWPXLRQ5FmI0HkEf2etxueA/Tf4HfDyG6Trq09bXCIfVi1AkiFA4qP0YJtKIE+IQBofvT7Lp7m//bNDgu5esZfwiSpL73feSDwIMAGIj/teVICR3AAAAAElFTkSuQmCC) top left no-repeat; }
		.odt { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA5VJREFUeNrkl01ME0EUx//bdvsBCkUpIG1BQBs0qODBKPhVozF40QMalAQwejAeTDwZjXowJkbxaEzUg2JiYmKM9WKiByB8GMVEERQFVMSgKLSkhVLabnfX2SlgC61sK8rBl7zszLz5+O1/Z9/sMqIoItTU26rqVXrDVsRogtdDfOwWV3/74O/6TV9PFalTYtGWWNeH73MntIN91U5rJWaDCDUF5tB2FBUiNz2jmrVW3pwXgGxjGso2FxOIdNkQcwqQZkhBfl4WgShBjkyIOQVIWpgIU6aBQJixVyaEIvJOjc81WjUWpSQRiDTZEFEUEONw8kopldDpNL8gcs0o2/R7CJWcd1WeiXhQ14Lnb7og8Dz8XAAejxeuETdSk5LQb3dUw1rpJB2PzwrAxLG82piHxw4X4BieFtEBCTqoCzLAvW4qlKUA4lBAGPdAFAQoF+jBsGrZ46IACDEt7v36ASKRnXcOIeD4QTeWMOoEPzgAVm8Am5YJ1rI6FoDYFNBm5gULZgu9cN96CYEA1pQX3rGn7c8U4P1e6oLfB0alglKtA+8ZBTfUD85lB0i7irSplyyFxrxclpKRAQQ+fGHOD/fwABQKJb0zKc6PjwZBSEzBKKBJNQbl1qdGnCNGAGHGW5FsMIeLRJ65b8QO0TcOrcEERqmKOHZOFFAwzIw2qUmbnBp1zJ8B8AH8K4sCwM8vAMNz8wugCHD/uQJiYN4B/PRakJWB8xU7UZyfDRc5Wq8/aUXNgwYas52sou2SSbHLtkZce/wMg7Vnw+Z6+r4Pey7UxgjABQFqj+1DS+cn7D53AwXZGbCdOYwv3+242/iSHBcCau7X4RLxkhVLaay5oweGA6dRsjIHttOHaDmub0KR86HYYoLZoMepmzZa7/jQh7v1L1C+aQ2tSxlPJMlHKje3d6Hl7UeUrrXQ+uQjpOUQj0GBX5O4nM6pdteom56UdDLpxCQJa2rikHooQHxfxeSQedP1ES63B0d2baT1ZFaJcus6NLe9o3WqgJQxJ2IFOUZ0dPcGYxOPkJZDXa4CHDly7cRPXL2Di0crULp+DbLSF6NvYBBX7j2icWkP7N++ASWrLDTW1NaJh43PJzK5f2qe2YyJ9HOq5LxTP6f6BYlYvSwbTvcY2sk+mDSpTYpJNj02OaaRQIVleFbb4K+rtc6qAB8il2PYh/rW4Rl9XnV2R72raGPAamVuQk3CX0k6kb62fwowAMCC60T7iz9lAAAAAElFTkSuQmCC) top left no-repeat; }
		.otp { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAz9JREFUeNrElk1ME0EUx//bLW3pB7QULdJCqImBkpgYSYgnIokx8aJBbiaIJt6IB6968CSYeMSbJCoevMnBRBMS0OhFqzF6IEBiamMoVMSCrS277e6Os9uvXah0tzQyyWTz3r4385v/zLxdhhACdXs73PPK5SGnYbBlOQb8Nh4NPFu+ulfczvnMlYK6+0Sj82M1YkIi0XzlzcVuVINQNxPq2AKDA/AEAzLEwwMBsPiCCF44bwiirgCmJi8snaEChF8XRF0BGGsjWHebIYi6AsBkBtPoKED06oKoLwDDAKwKoqMXXVUgzJUva23zr7+exe+FL/kBJBEkx0PKJGFt8YJd+SFDbNGXN6oD1NB8HRIyyRdAsvL75uPA8if2hC4FSA0KmFjA6TaeuG8FBA7g0wxyPIHFDtjd9QAwsJDUOqMoRsAgEQMi9AgIUh4GLIHNCbR3MrA1GgAwsgXugDb4EFeoijblWlQdUxcAPdDgNhkI2bI6rJX66IFLJ6hfoDE0qYGuurmNwO3Tv4iqANkUg9QqA7OdKAdNbtsUJpvJx1lcgNdHYG8hNSlY+QxIqg+Mg8B7TDuii66So2AiDzhaya6cfR9CSccKLE4a5NQXa3wLJAb/q1VWQMTBAhy8AtJBK7BjC1i3n/YAxK0V2mP5xLYQTLYm7Z9x9L3GL9FCIcQXjQOIYnkLms7dhOPUaMlOv3uM5MtxuM/egqWrX5O3drt7l78Yb2wLhPyz4UhImXxjagT8tzCswX60XnuC9McZ/HwwosT47yyX3heLUGr+PpJzk7CfHIJn+C62no/XpoCz5wz4SBiZrx+Uui4/ZdtK/dzKkurMMGXV5OpIFXAOXoftaD+EzZhGUX0K5Iq3gU5rc5Vs5fNCbdmv9smKlXIogNnjh5VCCOkUNmbGNLG6/glFId9Tn+dgaQ/B3jek2PJTtmV/MSavWDlHBkiGZxCbvIz41Bi470uaWF0KCAViIbqIzflpHL40oXS5yXYmurircAkqBYjKrukairnynq09ncDG7DQavH7kfsWUrv7OR++NgqfnoZgTp/FiJqUZY68/nX8A7LDjMXDxWOU/ooWwxk5HlvZfiP6kyX+rhH8FGACobH4ptuQwXAAAAABJRU5ErkJggg==) top left no-repeat; }
		.ots { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA6xJREFUeNrEV0toE2EQ/tJs0iRNmjSNtkkbC9quSn1WihefBz3oQaygCCLqSbyIiAcFRSx4ERXBgxcRBEVEfKC9qNAK4oNIRaVYG7RobWM1sY+kee3L/f90Y5Ju211NdWD4d+af+efLzPyzWYMkScilba0L2x0V5nXQSamEgHRS6Lh9onv9VHaF8Rg1I3a5W298hHpj4EYs61pOLbgig9ir1a8ERaTGusVoqKvfQ0D8FwDVrho0syt1gWCKCcBVVgmXw6mIMghgunIUFYDVbIPHUZVpNo0gGPVO1R+cuJgYM+zW8nzlNCCKmoESQwnMTGk+iAySSUEUFcDTrg5093XJGRTBizxSXBJjySiMMMFmsZLGJGZ7ZwTA7NoyfI+9wfdEwUZphmvdVvQF+T3aAPxBDxiNBtid5qmvHGPQ2oTaESTjPHhOoj42uwlGlSBT/a6/KkF0KI3EGA9RlOgaDkXApQXEYxzs5SYwZgP89U5U1dp1zgGNCXC4zJQV8s9zYDiSgqfaqn6epBGAWGAov+UQj3LZGcGYSmjKI4NJqhd4keodLhPmsM4J/ronoZRzwo/+OERBQqmNoasCRhi3cVdZ4KwshdXGqPr/UQ/k9qDHZ5uwz6dF+dcn6B7pfu3TUyrOLTCaDPK9t+m+Mdp7QMQ/I/UMCNKMBJO0Z0D6vxkQZqgE2jNQgKDC5oVb5p/xEIZkJjTP0zTBb2AkiAQXze59DHdO29yTAPj9vGXpQTTXbcbAcA98LhaBz2249+YCNszflwEyqwn9w0Ek5cB3Zf3+VRdhMdmRSMfgLvPi7OPdMuhvOm/BeAZIwDX1O3Dm4e4sgCMbr+LlpzZcbD9Abc5vf4E7r8/j449OCsbnbMCxOxtoJnY2H8fcyuUIRNv0DSKez5g2elcjONiJL+EPVCYrkYle0WUAS9SH6L4O9eD01kd49/UJ3sr84lOb/hII4wDkPzbUSZGVQ4g+VycIGZsYH8Xp+7uwxL8WbFUTWpoOwetkcStwbtISqH4X8JxIubO3HWz1CnjLG6hMViITvWKjACDPi3xr0NpyD+/7X+HG87N4FnyAGldD1lbzLVBK0Dv4AQ/fXsfhTZfQF+mBv5KlMtEXZoz4dPUFMLZsFMe3XEMkFoLfzeJyx8nseWovKYPax2lZhSXv49Tj8MJT7kN4dADhaCjPfkHNClr7eCqWp1N6JlefjKZx82iXYdoMcOn8ORCK9FNWo3e9AU06XU3IpfiZmYQqE/aXAAMASAvvftCvl/IAAAAASUVORK5CYII=) top left no-repeat; }
		.ott { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA5NJREFUeNrEl1tIFFEYx/+7Mzs7u17Wy65rmkoXMjLCeoksKn0IeqqIMhBM6623Hooe6imC0Kdegh6iC0VFRPaQFYEalVBEN0QTovJSqO3qrrq3GWemc2bdm47tzGr5wcfO9+03c37nzznfmTEpioJk4+qOdrJ5rt0waHI4SDxwXey82fy3urnjsVpFWZt3GR0fkR+94McGmny1jUgHkWxmLKHt2VKN1cXFTZbaxmvLAlBR6sbhndsNQSwpQJEzH+vXlBmCWFKAnNwslJYUGYIwa6/UzNxqtaAgP8cQxAIKKBk42VIMA57nZyFcKsShNBCsnr2qzxQ87HiNNz39kCUZoigiGArDNxmAMzcXQx5PE2obfaTwZFoAUwbDc6Vr8MzrB7zjc/7hARsPrsoN8dPLal0KIAMFTIwFjMNp+L5FA4RHfkD8PQyiOVFOAetwQQlNAwKJTWawJRVg3WVGAWTdALy7XHVq4uggacl9kANT4EiOpYqEApAnRmHOcy09gCLNQJgYg0wGAblm7Dmwk3PEbOF0P1MbQJZSQiE4hUhgEjOhKXUg6izHQ5r2QQmHVKktZHCIAiTvCGyVmxe5BuQErUQeSult2XlgC1dAEsKQyNEb9PyMnpzlleAKihe8P0OAhAIMaS4MY4/nGZas9mwHOOKSGAFjsc5TbPG7gMqswxgzo7vWIICE/2XanVASlxfAvNwAppllBlDmADhsVmwsd8MfjKBnaFTNlRU6UO50pNQNevyauSF6SBkDEOLXR3ZU43zDHjjsvBp3fxnA0Uv3UL9tA04dSH17bn34QjPX2vbCIICYAKCDX3nSjdYHHShz5ePR2WOor9mIlvvPVW87dxzdvd/RQv6nRnOnD9ahZsMq7D9/NbOXUgpAvWbdSnXmLXefqvHgr1Hc6XqHvVsq4zW06ymkEcVi1WljovnkXNKk0vcBMRz9nV0LuZwZk4FgdD3wluhxHauh17QZxeJYI0uuMayAEFH91fse+KeDuNC8T42rVhbhSN1WPH79Pl6jzpQ0rlisujSrQHKO1upVYEZIkJ+5fBsXTzTA235FjV9+7MOt9s54jUIOKpnMOPkeGtN8cm7BNymtj1NGjKR8nOZl27FpbQV8RI3PXwdS6mnePx3AwIgn8YVU7IQjO2terWSxdgkdN2rTKiDNIfeOh9H5dlxzBh96++flvg0Oa0+Xnpy6tqHV/k+6ntbb9h8BBgC+gfbonYrxkgAAAABJRU5ErkJggg==) top left no-repeat; }
		.pdf { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABFpJREFUeNq8V0tvG1UU/ublsT3x2E5IiFXTRLilCVQoQRWUBRIsWbAAgiKVTbtg2SrtL0BsEa3YsEKseBQqxIJusoBKhLRFSAQVnKQSEknjNA117Pj9mBfnju2xE8b1Q6bHOr6POdfnu98599wxZ1kWWuWT6OSNAM+/ih6laJqkxu/Ufe3STmK/nd1hf6Kb0ZTo6dU/7ukarFFlZms3e+NyJPpIEK3Co0/ZLOSwnHyAdLXizD393CRmpyIz1GUgQv8rgPVMCrulIu6TNkQaH8exU9M9gegbAEexPBkcRkBsRlEaGYM8MdkTiL4BiByPjdw+KobRnAuNwBOJEogJxE5NYfZEZxBivwCekH1IVkpQWhgQFBXS6LgzjtWamZW7O1eoPTdQAGFZRoYS0C9KTTq9fojqyAG7Ooizl+mLTsa5gQHg6CNw7Lspa4s/IrFyB5ZWgVkuwaSToucy0HkBqt9zlkLBjubFgQDQTYPygEOuWkXQI+NJQUT5hyWU2tA8Qem2zvMzA2HAoBOQLpch0P53iwVEFAUyJaXMCR2OHNfdKbA6aLJYtBfGQmEwl2upPeiW2XHdQI4h232Kik+AaA/KXkwGQ6joOtYJRJHKcc/HudcFaXLOLpQxop0JAzGhhrCZ3bdBjHh94HgOJtkwTngKTZhsgpKnewDt6NIo8faI/pDXSyeAxx6BKWhV5CpVclRz+k+pQOGwbKYsCnnJ0KFR/wWqkhCELgFY7hCS+byd+XnK/AwloYeKkE+ScJTCoEi1elClypgs0x1RyKNKV7TEsSib2KYx1GD/DJQ1DQVyHPb74Sc6JdoN2/XhNWw+ogRszVKhYsUqTFVziACuaJXeAaQKBeQrZTuWEhWUkM/fMVQNYYnK9FG2rgDMevsgsw+dKBVpV1XK9DGi0MRgpQ0AC3mKMXMq8rzdqkS9RxJhdtz3AACwC7ZI8a6VXBMKZb3i88HA4EVsV2zUoSGbfoEYYGpYFvC4AOj2dcfBMzwM9dlpZz67ugY9m4UveoQ02kzU2784/eHTLx34rdZn3QOo7zY4PYUXr37eLETk/KfX30Bk7i08s3DhALCb8+/a4FrtmVyfONb7OyGrXEz1esJ9dzSG6ydnUUxs4wg5ZxUvSTtrzItqAMcXzttrmCzNn7GfMW38ltYmhK4MaHXHDSZOXLwASVXhJ+qzWwkoT0VtEMxOy2bw97VvMUrUN9a98vWXdhu/8rGtPYegYpoOE61xXf30M9z95hqev7Rgu2rYMaCt41/f/wDp+CryiYQz1xOAct1xtd5+Pzd/6JhadkFidjIxE3tnDhuLi8663T/j2Ll12+U9jusOQMk06gDMA+PWHIm8fBrvbW/a44fxOJY//Ih221x3eI3tTBC7A1Cs03bvzh/44s23nXFDfvvqKv76edkZb9285fSZ/UNioOxCvSp0GYKCodfadAp7S0v/fb6xgfukrn/ZXOwdAC4vJa4AwpKMxyX/CjAALhkSt6rwJ9IAAAAASUVORK5CYII=) top left no-repeat; }
		.php { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA8JJREFUeNrElltIFFEYx/+6s+vOrKvrbrhr2y5526SWSqKLlRSFdPNJeyuiICKCguo96KXooR7qJYguFBQ9REUQiJhBGRlR2YXspqmFZeZ1dS85M5057pxm1ZyddakPDnPON993zm++75zvTIYsy9DK4TNXGou97rUwKP3DI+gbCl06dWDHrunsJq5HFdp26PTlRjkFqWtukY+cuy4T/4t6ANqWiTTKohI/Cpy5O/UgtJJWAGeOHRXBUkMQaQWwCzzcTochiLQCWLMscORkG4JIK4CZM8FmzWIQKxaUwqMDkZnMTk22ZWZkwGLmGITHRSKhA8Gl8qUj4Qjav3aj0FsAG29l+qetbfj09TskAiOKEmK/xhCORune6B0cViAGiNlBXYAJpQI/+gdx/2kLW7yx+Tl9JoTSZIKvwI3NlctR5PUwPyUaQhzy7M36xUlFYGK1Onb+Op1MmzneZoMsJdp9/zmAi7fqyIJZGA1HqW5P7UYEi/1/jaZuCsLRGELRX+AFIfmCFCjEvq2b0DswBIFEYDrRjcCHrm7wPG9oj4jy+ByuXPvU9d/IHujpHyYhFQwBeFxOyEna6kaAI8dKILs435EzDkTCqidLygqn/WqDAGYCYMP2qgr8GArhxoMXupP63a6ZAUiSxoAz0wiYOQ4uezbts1Dn2eFx2BGKxPCxu5fpIzERFlIVU4+AJoNmpbLZBKobicVoX/ulQV8++kfC6B4aZfoQKT55HD8DAM35DridcOeMn3kHKSgbFpbgcdu38es3W2D66vIAOvvIhrVwdDyxRhhLgfwnByZTBlnIynR23oyqBb5JtlaLCQGPY5J/intAxr+SKQFESfq/AFIcoHdgEE3PXzP9vLl+lBX60NreRd+tLg9S/e3GJqwi/VmOXNpXRRmvitsY+h8YIylQmnK53LrXRCIiY3g0ghMXruHNpw68bevAw2evmJ1io9iq/Z6+Qepz9W4DbjY8ZHaGUyDFi8mWNRX02fWth3x9JyvX2lTRf4D4eMWi+QjM9dFi1Pq5C5unSenUAKKYkIo79x8hHIngvTIZue8/dHyh/b1HTyakTfV79OI13pH395qfYd2ycqY3ACAlAEhknGW2YP+2GhTNmU0nL/V7ybiWvj9w/HQcQGKnSPGpWV+J5QvnM33SAGNxBzGet6qVSxPeKQvIGjvVVh0vDZah2OdNmMsggMjKcJFvNhurkkvuBM8sF9MrNoqtMtb2Uz6GqnO+Mw+7a6snTba4rIQ2Va/YqH7a/gwA/nMhqn/y8p8B/BZgAOnDW8QALqzQAAAAAElFTkSuQmCC) top left no-repeat; }
		.png { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABJFJREFUeNrEVu1PU1cY/92+UAoUyltrgGBJ/ACDuErETE1Gu4Hbhy2h++BXyx8gs/+B/gUy3Zf5BV2ymS1xa40f5tBQE40IM7l0Gyhmk4EIGsECfW/v7Z5zb3tbSN8x25Oc3HPPec5zfuc5z/M7D5dMJpEt3JdjU9rmZhvKFCEchhgKXcWly6OF9Pbup8mlZDhxotz9EV5cRPz5c2di7CyKgcgWFd6hDFi6UGeod2Ls7MT/AqDP1IpPe3rKAvFOAXQYG3C8ox2flAFCU+4msSdPEH34UOrr7XZoLBZlrrG2Bp2NjUiH2e2FBWegSEzkBCBkRaq4tYW4zydnCJ0w7Lkp9auHh8EdPKjosujWa7VoNdQhO86LgSjoAeH1awSvX0cyGs0MVlVJn8i9e4jxPHQnT6Kqr082plajVqeDaY+d2wvzeUHkBCCmT0Wb6R0OhG/d2g0irRcKITw5Ca6hQfKAZ86H2aV/IIgiookEArRmIxBES00NAsEgiwk/LXMVBZAmC66+HtEHD5AkgyD3MlF3dSG5swP9yAjE7W0ZGOmpm5rwC5ERorEsb+mAJtaaUE3rItPT1pI8kAYg7gSQWH1BhrRQkRFxcxNV5PL4n38gSlegGxratU5dXV125qjyBaHUAjvSybUfDkLzwXEKQiOSdXVQH+mHsOVHbH4+o1tCKzkIReZy5onaWmiGToEzy2GlOjogz2k0UB89hvjsDCV/BzjSq1Rye0BMSk3U1yDZ2qr8o61d6Sfb2sA1NyPx+LEyVqyVzgOiUBr8zk7gdx+ElWWgvb0iD+TmgdQVFBUKTGiJF549A9bXAYoPGAzEUhSMFC+VAxCKAGCpx0796pUUD5JQakptbU3+ZyC6e4Ai8ZHHA3uuQKB/P3FIKCxvEgrK4yluyH0IQfZM73uUn+oKPRAnUiE6Br0HkkFlVYlvGDsIW28ylwsgIX/fvgWI7cBxpW9ayF7pAITMSVlg7UeYDUGoEIBeL7f9StkAErLLrEQ0Rnpemfgp8vmNDalvIxLi37yBPxaDhdLOSK9meo71rS0tu/TLz4IUgIvE/7Ysghn3zcF1/z6mPvsc3persLvdcB46hEFiSLuH+t3dmPjoY0WfgWTj/hxPeeGakAFgjR6QC4+mwV36CqOTv+Lc4fcVcDba1GY+IJMW6VmNjdLmLnolmf6R77+TvGMzmzP2SgYQj8uNDLPTnR84hjNEKkvbW/J46komqCyTUpb0Rqg29K6sYPy3WRhVahg1Wjg8HvBr6xl7lXjAQsXGIF2Dn4oNx40byklcd+5Ic2d6eyU95gkpXmjeShQ9dfq01JxUIe/LA9fm5mD/9hocP/4AfnVVOcnS5gbGZx7BQuUY03MvLMBqMuFcfz+8f/+Frq8vg2dUza6ogAc0eQHIpZGcQrkWJwRcuHsXThYXpMe/WMHozz9hwvEFLg6fkkESfbupesq3eX4AMbmuc930wB+JKP9psV+5An79JV1LhPrfKGuuzszATc+z9UAbrQvT/a9VmIYpxPzycs5p7+LTTKrt0fHTWu/2033WA4kE/iv5V4ABAGwMbZQpCDPqAAAAAElFTkSuQmCC) top left no-repeat; }
		.ppt { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA1BJREFUeNrEljtsE0EQhv/143BwnKflkBijiMbQREhBFIgABQ0t1AFSIV4FLVQ0oaAkHUhBUKQDpQpNhEQUChcRQgIchOIg4Yg8HL/kxK+7ZXfvjO/ss3PnOGGlO69n52Y//zM7PkIphX4sXD/1wddLL8PmKOYJCrt4dfHt8kQzv9r9XGZO4VHZ7v5YW3EgseG99fFaGHtB6IcDbRxDoyPoHvJziOn/AuDyhxAaO28Lor0APQOQgmFbEG0FcBztgbs/ZAvCZTX4bppXsFbJ2sVvlTk7ASBSBxy+frg1a2iMH6tPDAINC9MywPoPgiLbRGGxFfZdVtS5zG5lDWzQ5QbxdApZ3Rpg6AKHWGx4OswBaL1p+CxFIQf8WiLqOttl4AQQGAaySSD2DdhYnEc6+oVRyaDlAih7QM6lIHV1w7GZ4hAp9uTDlhTg44iXyUwI21vVnk3F8PUCwZMKVhbmVFVYrgJDDDCoObDn+kaA5SXnGUsKUNqk0AhPgQpBdL6SB3ASKsQhzCh5CDp76N4nx26lEx0E0eVKKTMAsUCFg9tt8eharQE9QEWJiq/MNk+uEdXGbk4Hhf9Y8zgtp0DkXVvPZwi2YqwIE/zPiCvAUiEBx0+zuat5nJYB9BDiBzuBroD6jLePikK1EqN1BTQBOITHR9EVpLbgrdWA0rwKCa12wqa+rQIodO+jUIFQ6AEAUIWYCyNXU8AnpZ3GvvtTwOSFKB0jKOWNdVBgp2CTtWBvgMLTe8AKdPjZ1aBdOCXWkpR2KmASzNnRPJDSTgAq49CGKYAsV1PQP/EG0vA5FSyfQWZuEjuf32HwyXLVPxVHcuYu/Hdmja/qqxEkpsftv5LxP5bKxRtLZn4K8cdh8em7+kjY+dh6OS7sxbXv6Lz0QMy5jQ8+33wxbohlXYEyMVQapapN5JlW1xWmlJhrPUH4aOoZYtguwrKxtXZfuS8uZTeLxOzkv/XA7dfis7wdx/r0PfWXyvUx7NdADcD2+ykk56bq/OLPbyD/M1JTP/UxbAOUS0YAfir0Nn3DqrVXFDDzt65AqZq/PzNPIe9kDTY+Vp/dROF3tM6+E4uKtVp7ozedBgDVeW4lakqe/RoxtcvpLIrpyP76QDZHD60R/RVgACYfj45WfzRkAAAAAElFTkSuQmCC) top left no-repeat; }
		.psd { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABHZJREFUeNq0lm1MW1UYx/9tb2mh0NJSeW2RktJhx8sICIqJGbqwzaigY1/8hLrIBzPfEmOIfpi6xBgT4z6YLMaERd0HlyWaGN9gGxgTBlIzXhwZIqMUBJECXSl9f/Hc0/ZK4bbc1vk0T07POc//3N99zrnnHFEkEsFOazn97mBFWelhpGmbTifr4+Rvm+XjM45kcbufx/AFPft0R7rPx+DoGPr/WDzErK/0kepTQnVi3EVrNJkAg7mz6cUzfXcF4OLwOLrOX8KH18aw6nLvO1hdoRJH62sRMZi7hUKIk80T64XKXGz7AhiYsaL70gC+mJjl+vY4+ZUo5WjVqdOCSJmBB416KGRZ/2Zkahan+0exHQjyxqtkDCrUCjyUBkRKAPbhnQ3VCW23HS68fu1XuHkgsiRiqLOjEEIzwT8FO/yZ5hoU5SkS+tc8Prx3/SbJRIiLo4OJRZAxUQhDDKK9vgbhFBCMkG/1lbYm9P54natrs2VY3HLjs6k59DRUxTRAv2USE3MLCJNKMBShgG63H2WSMGzyXBaC3R9e3TcD4UiiHyzW4klzJddvJxlgISbXHPjJ9jeNaa6rRXGZAaIcDSSKAsiUWmgKimDU69FecwCnjj0KsUh8KKMMsPZ8kxlT9juwbns5iAK5DAPzf+GgVgW1PAvlpSVp7x28GQgRAD5/h0xFS6l2x1ohoCLgq5nFpJqdLhyA5JTP5YwExyvLuDhPMERX4ILTjV/+tCfVxV0wQDAcTuomjRIdRh0X6w2FaDm8ZE+pY104QCic0o8bSmHMz03Q+AjIwO2VlDrBAAESvJ+fMOkhl0gSdDMbW1glizSZRjCAnwTv5wqGweOVpXu0847tpBrBG1Ey2t1WoVTg/iINffP4fqiRSwXrkwL4YgtLiNXfo6LO2rLLA6lYnJaeF8AbCCET05DDa5msAbVMimxGkjmAJ8lxK8TypBLMbrpQla/IHMD9HwBYk4qFj8EL4PIHaJlD3qZ8x5vYyAp3x6ZHmyODViHjbdOpsmn91pozQwBfFECfn4PXHq5OyEzv9xMwF6rQ84CRaz979TdMr97BUVMJTtTqE8Y6dXkUbn8wvcPIQTLAuiuWxscuDOHkxZ9JRhg0lxfQh389vUTbh212HKkqpvHxbTkez9oT5jJuPMEA614/dWdM1FVbjpN199L/c2SBvT04hSPGYnzXfRhjS+t46+okjaeHU0xv2/LgU8scOsw6bjzBU7Dh89FyKxAFeK4xehn5aGQGn0/OQ6fMgWVlA9VaJT5ob8DLrQdQde4buGMZiOt31zWCAbxRQTwDBe9fTui39BzDlzcX0HtlnN4Bzz5STzXeYJDTq8iO+FKLCects9x4ggEcMYHL50+oc3fEH8bQ19mKFxqjC/HcyC0a442tmfU3urjYN6/cgMMXSO8rCHs8tLyxsIK2T77l6nG7MDKNoRkbuX7nwbq5RdxF2/tGpzH4+yIXN0SO54w+Q3ii9z4HKYc2+L9lK+mzLtv3bcsI4L6QE/+LifY2/SPAABKNFpctNOXhAAAAAElFTkSuQmCC) top left no-repeat; }
		.py { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA4VJREFUeNq8lttP03AUx7+uXdsNEQe66QYqsInKRYlX4oOSKC9qTDT6YrwlPviiifjsg3+APqgxSmI0mmCMRk00EC9IYgQ1ireIQryAoEamwBjDXVhXf+0y3KXtWrZwkl+6/nrO7/fp95ydX6cJgoB4O3r6Smupw7YeOm14dAxDXt+lk4f37FfzS95Pmogf9acutwqTsHvP3wrHGq4JJP5iOoD4YUAWrap0HmyWvH3pIOItqwAFedNRU+7UBZFVgFyzCbaCmVgjQczQBEHr3cQ95EHL81cJczkmE0b8IThmW2CZMV2aW1PuwrPOTyIE1ApTF8CYP4CjJ85L12SzWCxYXb6QwLATc1ogDFoqNTZ6fw6AYViwbOqgKBoGwzQwNC1BiEpE0+FSTYcuBQwGA5zzHAlzfzxeaVAUhZddX/HlxwAiBJbneQTHw/AHQlJtDHp9IoSHhBxJC5DUKvDdPYizN5oxODIqC8YQBcRNyZui0DZL8QXO3XqwTJMCyd3q3edv8JEiYxlWVSExqsRu1VXUmlJAk7wyLJPWr6WjE3fbX6NuVRU2rqycPECyApSBAsdG355jjNI1EBqfeM4xDLkPSQqIfo/fdmPDiooMAJLuFy2wo+NzH4KhMKwkzwPDXlL5VII/y3Kqa2SkgFhc9Tvq0O8ewtOPPfD8DaRdOOXUywQgZoWk0zFffoDlONVFa6ucmQFEIsoBRqNRyjNDU8jPNSc8mzMzF0uKrFIzUltDQw0IqgAsFy3IYfLXnDgPWCMoAmUkQ9BcAUoK8Mr4ZrI5FwynzPPieZBjUo3VDqCSvxULbPjpGYN/PBHCYmaRn8OpxmZchDGbm2fOqPLTAvCRCKbKNAP0/3LjatOj/82puAhrqytw4WYzttauRRm5/xsI4kzjLWysWY7qxa7JfxNGIkLK8JHm09XTB9f8QnLizcb99pf48LVP+t3Y1CL53H70RPpYWVrmlF1DswJhGQViqmxaV0PO+CC6e/vxm3yeifdtb97jdmsbHj7tQP3enbLx+lLA8zKqRBc9ePyEdDWRv+PqysVgjDR2b6lDw/U7qF1VjdIiu2y8ToCIIsChXduibZlIbyIdUfStcBZLc5WuYtlY3QBhXi4F0RwWO+yKfqJPODsAvEwLplFC5A0ryCs+E33COuTXBWDNt+DA9s2KG4jPlGKzUgNT2ojuv3g3ZQD/BBgAc9DojqN2L1sAAAAASUVORK5CYII=) top left no-repeat; }
		.qt { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABC5JREFUeNq8V0tvG1UU/jx+ZBzH09px7EmbhqRxHLcJVZRCq1YipVICgQohVUIsumlXrFnBggVIrZT+gKpICBUhdQMLFhEIVEQpK6xSUoEMSZykuEmbxPGr9jjjjO0xd25jx49x7IkNVzrynTvn8Z3vnnvuWJfP51E6rruv3XUN869C4xBCAlKbwhcfLn10ZS+9yngGNaWJj6e0xsefXz/EY1/w8jSuoh6I0sGgVYMk1v/KUfSc6r08PXD1VnMAFJo0ikKs7QUbvG8eIyCONAzCUCv+fobFboH1EEfZEKNiQ9vRMgYUMZmN4JxWOL1OjF46iU63oy4TTAvjg9HrwFpMFISrQRCqAGQ5r1mU46XT6cAYmAoQY3uCUK+BXH5fp8A/48fKgxU6l3MyslIO6WQalq4OxIMxBUScaL5fF4Asy5rjuyeHEH8cq1rvIOLwkiN6zo170z+O/mcMGNoMcAx2abdTZSC3y0A6JtJf1mau62xjfp1WYzKcRDKUgLxTnZ5zXli7uP0BePCpD/zYIfRPuOlzRswg8eQZUhHS+yMphBbWsZ1KIxKMwMAakCZzZQsVoc9JEa6hbtIjOjQAyO4CYA+wWLoTwKNfliCltsG06YkwSIUFMKyeBtKTNc95Lw4cPghLpwVGswnSlkQrc5vY5ElCpT7rAshlc8V5GwFwsM+GrWgKFqeFAlAC9p7tB9fNkSPWhbnv/fBODZf5+GNmFi4PT2tDYbTUZ30GMrvKJs6EgQseLP6wAPfrHlXdvNILMrnqXkKyLly/le/3ZqBE2dzZjvXZpxDWkmXrlTWjvAsvbyL+JAZJlBAKrNFCzOdlnHhjtKZtXQA6PYPAt/PoPO5QdRKa2yDNJ4jg/UcQogKp/OcFyB/rpsUZXY3g1LtnNAKQdpUNZgPZRz2yYhax5SjdfyGUpE1HOXZKtoyJgcPthHfyOOx9DhhZY01/mgHoyVFStjGx+owGldISoTwHnZH0fCuLo+NudI8cbjhggwCyZc+Db3kRCYSxFU6BtZtpxrY+O1iOVdVvuhNWZqDQ7hxx1dVrGYDsdu2M7KQnZNJZJNeTzx2QLeJ4a5mOGE8TEZsAoEIpx3M4feU0DagMBYDvlg/t9nacvPRSme7i3UUEfg40ASBdDWBgfADhpTAefjVLWq0RZ947i96Xj2D+zgJmPpjB0KQHnokhOtcyGHUAmSrhh3kEfgpQahNrCeU/AA1YeF/o9Wq2BWmKgUK1K59dtDCN+jLdXEbe01bbFojVaDf+3oD7/CCWya344sUT6HBY8Pvt34q68s5lo2arHcBWdRb3P/fhtU+m0DPWU1wbI8Xn/8a/c9nINW33AaA6i82/Qrj9zpfgR3jyXSDRtbdvXCzqLnw3h3/uLavaagcg1Hay+utKcf7Z+M3iPC7EWteIEpsJ/F/jXwEGAJKrp7XHKu9HAAAAAElFTkSuQmCC) top left no-repeat; }
		.rar { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABCtJREFUeNrEVu1vU1Ucfrq+rC8r9oV2LYyuZTDLy7Yik/gyMmamMkI2EiXgFzMSQ/zqB43xm1/8G0xEJcTE+I0PJmbzw5ZJYmIKUQrDrYzbQTug23oHt7ftbtdezz233DVmvbdtGj3Jk57z63N+57nPedWJoojqMv2tfsblHz6FBkueW0Y+m7j67kflS2q8f49HA9WYutI2I2qUJw9mxWtf6CmmvnmLxu7f+lK88aNHJP2/1xJQjbYaNFXodICpXUdhMOqUeE/vBLzelye1RFSXNjRR2q0vYW/vMIVn38B2MksI4aOXGhJRQ0BZFSWBxfP0HEWO/UuOS3a2+6DvONKQiJ0FiGVVmMy74A+dpHD5++S4NDVGJ3GhuyJisi4RzTlQZMmX/0ZR4G5X4kSA3gqdyVMRcVQW4VEXYdjZgJKqbeaOLoRe+ZzWTdaAzBelxWmEzmCv+jIR4b5JIHaViFjATlvUsPMQ6gKKBQbrzFe0bnMPwd19gbqQiv+AzOM5Mu4WEVWAWOIhErfMVi8MeoaIEDYI8RNtAaK6AJNlL/zhT2ndaAlQfuDQZXCZWM0+3jAQnZqI1OcA+QJVB/IMWQdP0eGdwLPkFezqPE/OAxucna81vKVrrIGiaiedzobOw9/Jc23wafKb2IZFVZg7eskOiMpu5FY0+Qpa5YBUFuc+gN07TI5jO2yO/qYdqLEGBNVOW0UOHLtMcA2e4IQmv4k1oJ6QW7sDYVOu82xSk9+EA5uqnaQpEipj5vnnmvzGHSipf9FqMorgkQ8J3seN65c1+Y07QE4x1evY7MbaSgyrj6JkKkRNfssFBA4OIUvmnrn7C068/VnrBYgl7YSHjp9riN9SB1pZajjA/98CcvR35uevkV5Z2r7R9vRg5OzHtM4sRvHH7E/o7TuJY6+P09jdm9O4c/NXhW+zuyjfZnc2dhdIAqgIcs0GD0YwcmYSb45epGLSyXn6X4q5DYfbR35jCl8kt6gUk/gSpKcas/D7dr76BfAySAKey8gDV5ywWE30v/RjBpFXR8BnN8CuMvK0kROxuJlX+IJQgNVqVvLVvwhLWeVdkOM5JO6zJJmAU++Mw2bRI5WIoUiSo5yHw7UbifgtRAaH6J0gCHmkluexwa6T2BsIhkLb+ep3gKOQpqCbJBg9PQaT0UASx2l85eEi5c1OX8dGZo20H8h9yptwOJwYHTuDPV1dSCz9reSi+eoX8IyC3Hv0nDfoC+g/dhjxhXtg1x8hlXyIwRMRvHdhHGNnR8HzWRqX74Qt2ncgEibTwyF+78+qfHXvAlY+8QJO2GxG2vb7zOgfCGL1SQIHDnSStoXGLWbQeLGwht1uI2k7lfjxwR7kcpySrwEBaVnAPr305lHaPfvbX9wGBBnl7SrH5UeM26VX+H6fzH3RrlvA/NLSf3YQ/SPAAKb1rEjyCBGCAAAAAElFTkSuQmCC) top left no-repeat; }
		.rb { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA75JREFUeNrElltsDGEUx//b7qW1ut1d7ba2tm26aOtaJRFEqOBBKoQnkbhGhIQEiSfhxQMe3B88uMQ94cGDxIOWkqLUbVurLFalRVTRdt12Z2dnnPnotLYzzF7USb5k5vu+Pec35/uf861OFEX0tk0HTta483KmI0br+PwVnwJfPPRYsXv9kk61fdHx9Eqb1i6YHWt8XK5vhOdZc1lbR6Bm4/4Tf4TobSlIohXnO1GSP7iMHiUIa78D2DLMGO3OjwkiqQAZA9LhsGXGBJFUAJPJACtlIQpiT78ASOrWp6ZiQJqxB6KIQSyjLByLCUByFutgznQ6GPT6Hgi7BOFCiStXFUKfjK//HuLgef6K9QL/mzYIBBQRBITDPFsToYM5zSRBSKW54a8AYgzBg1wY+89dgiE1BcFgEKeu38KSypkYSiUZbYcuVJVpykB0t1KzEAWvuuullJswoaQIxy9WE0QIuZT6IqcD/7QPhCi91zw+3Gj0Ic8xCL6Wt3H5iSsDHB/BmSv1RC9i6thi3PA04VNXACMLh+CWp4tmRc1ZTFHTgNrQkdJrvX6kUc3PKC+F92UrExp+rXU7UPptwmUYpi+/86wVTa1tyLFZcLq6DqOozNJNRhnAnG76GVClVOMG4CMCrnib8fHzN5S7h6DR/5pVQG2DDxNHDWdCvNf0HCOo+fypV2gCoBLuM162B2CkJlPgsLLABTl2ef+1+15MGVPCIKQsiCo+tItQ4cTa6cvtA9Nx78VrdFHDEfgwSqnWOY6jhsOhqr4BFeUjae3LLw2I8VeBEOmLW5iVidt0/lw4IveAJ1R6we9B6EQB44qL0N4RwKyJZRiWn0f7+AQAFM4rJyMN88YP0+SU43lFHwn1AakC/oUpAkTUFPM/AVrfvcc36vPd5sp1MNV/7OzCh85An/mEAASh7xHsPHL2NwApyI4Nq1H74BGq6+7La1nWTGxds1QzhCIAr3IEldMmoXL6ZAoWxJZ9h/Hq7TsZ9tC2Tb/NDy90JXAEEWXB1TU8xtU7D+R3Zzbdgs0t7PlizU26E0KwZ1rYvJoPjQDKGbBZMuB0ZMHf8gabVyyC0WCQy62m/mFP0+ropCs6O/7bUOr70UMytysPy+fPweDsLOw6epbNdx/B9vWr2JAgPU/9qj60aUAlfQK7lCKYWzEZe0+cR4PPz+YkAW7cdVAWZ6m7QNVH3AArF1bSfwAjW3PYbVi3eCGbdwwaikJnrrzPahnIssAnWwNS0N5r3e+SWcxmTRrSDHD5bmO/dcIfAgwAVH1RCx1x63cAAAAASUVORK5CYII=) top left no-repeat; }
		.rtf { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAApVJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/7vWLThP1D/fEIOQMZMDFQE6nKSDOqyEgmEHIEMqOoAQT4eBl1lOQYFCRGiHUFVB/BycTKICQkwmGgoMUgKCxDlCKo6gIONlUGQhxvoCH4Ga101ohxBNQeAEhQLCzMDJwcb3BFWOoQdwURMSiUGgw1jZGRgY2UBOoId7AhxYcKOYKFmFJy5cY/h7tOXDP+ADvr79x/D7z9/GL79/AVMGxwMrz8wgRzxAaiskKAD/pNhuY2+BsOzN+9xyjsB8Yz1uw2ICgGM0orIBKgkJUayPqrmAnIA1UKAug4YDYGBdsC/fwMdBQwDHQJ//w2wA0Z8Ivz779/gccDbDx8Z3nz4BOeLCPAxCAvwY4iDgKyEGFj824+fKGJcwOqZxGyIiILD5y4zbNp/DEW+NDGC4eaDR1jFN+0/CpR7jCKmriBLmgP+IIUAyDFqQAOK4sPA/JaZixiu33vI4ONgxeBlZ8nQt3AVg5q8DJgPK8Z97C3hfHTziIuCv39REuTjF68Y+hasBPOfvHjNEOJqD1cDaxEh87ccPA7GqkCHFcSGkJEGkMoBUJbkZGdnkBQTYTh4+gJDarA3g5KMFEINUB7WAoLxzXU1Gcz0NBm4gPr+EihTsEfBX9QoEOLnZfB3tAE2OtgYlmzdw1ADdADIUbAgB6mB6QHxBfh4GRSlpTDMIrpB8gcYnDD8DxrEILaVoS7YETuOnITL/4eGAC4+MiYhBBCKDTRUGDQU5cBirMBmd0qID8OHT1/gakAJkYOdDSefvHIAKdj4uLnBGCaGzhcTEkTRg84nywG7Tl+iW0kIEGAAQRvYtNeeH38AAAAASUVORK5CYII=) top left no-repeat; }
		.sass { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RDBGNUFCRjVGMTE4MTFFMUI0NjU4ODVBMTM3NkU0NzIiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RDBGNUFCRjZGMTE4MTFFMUI0NjU4ODVBMTM3NkU0NzIiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpEMEY1QUJGM0YxMTgxMUUxQjQ2NTg4NUExMzc2RTQ3MiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpEMEY1QUJGNEYxMTgxMUUxQjQ2NTg4NUExMzc2RTQ3MiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pj/0bsEAAAZpSURBVHjarFd7UJRVFP99+37yWECEEFEeJiD46CH4KDQ0rUDURsyRRGVSRi0rp5maKaexqakpm+EPm9FxJksrMweVpkwdddTIVLAUDDRZEvCFvJaFfX+de/cBrMu463iZu9/97r3fOeee8zu/cxFEUcTg9lbFN9uyU0avUSsVJofTiWAa7RXa2jt1NY1N27QqVblcJoW/XG/7fP3yIe8y/w0SQVAVPfMU9BqVHiG02kYjrrfeWmtzONU2h1iqkMmGNWKIPv8JcdBvKK3fZsOk1CTkZqSs6LPYttscDgiCELoBD9scDhfUahVmZI/Hk2lJq8391h3BGCF7VAa4RBe0KiUiw/XInZDGpladbzQKUGKVQj58OB6dAS4RSrkcGjIiPEyH3Kw0Oj1W/tlgtNPymuGMCMkAp9OFtvZ7iNTroNOo/QxwQSZ3iwvXaulXQA55wiWKr51vMCpoYqVCLgsOA15L2dM77jaZsXbLl0jML8a+IyfvW+8y9cLucKdtuE6NxNgopI6Kxwu5kzF7cnophWi7ze54eA8cOlmNgyeq8V7ZMuTnPHHfehTFvqbBiAi9Fp1kDGUCGI9YbHYwxVqlYnWvxdZFWzc90ADR78lwTPmNrNSx2Lh8MaIiwphrffvZet6UTK6wbPNWvDjzabxaOIe8YoJEIoFKocDcnEn49vCpvKA8MDgEbg0CCZHT6fRc4JA1hg0aS2me8UBCdBiS46Opx9BKzBC5jJxC5gGpR+Gdji6Ek3t1GtWwe/VaDWIMkbjT2TNgHIHT/2BBg5ApZwK++rEKl642oaQwH3I6hdPl9AHQ25n7leSlskXz0djcgorvDlD8bZ4DiKEx4eDtDNlXm1tpUkBclMFDOu49/p21kdGRVE8k/BurB/ViqFTs9RSjUhb7jzaUIiUxDrt/PgYXiZNJpfd5gM2xtV0HjyAhNhqfbFxFfKDhMnyYCt6AARC6jVAgPTkJHd0mdHX3ctT7G8Dm7lHs73b0IDMlCRqlkn/rGsQVgUyQBKbVgafTOfARywBW63nxoXm2zrrDs4etSaUST2K6vxVdA/ICuWCYLPC6zP3nVi6g19xHYDRS6bVDLpf61tnYQvGuu9YMs7kfgk+KOERG8CHgJov8KXrMn5AyBjGREfhs516cqbkEBcOBZ52Nq2vrsPXrfTAQSWWkjvbJYd01SE5QRMTi5uIudtvc09uHUXEjUL60AFea/sPYhHieHd51Nk6Kj8Wa4gKMS0pA/Igo+qYf3quAIIiUuiGUY5b73u4FYw+5Py7GwBHeb7Xxdy9JsTFLvzEJI7kiE71zYPoscMsMuhxzD3j64NzsJk/4+J+Ee9fZmOGC9UDZJPjLenAWuDizsY8YuzF0sxM4PF5xIx18D+vsoCqlghvinWO8oKT09bKk2wNBGsAUuXhd16DtbgcvrRF0CbHSCZkSiSClp8TNEaRYqVDiesstfiNiSrVqNS/DLbfvIkyn5cxoJyNEBH0joupHRLLr4G+4UNeA2/c6sSh/Jorn5VGJNWPl+59i4XPTsfT5PDTeuIndVUdwq70DFsLGB+UreBnedeAwJ67EuFi8XbqEXfeDDwFzsclsxrYfKpFBrPby3GcJ1Wa++ezf9aitb8Dpmst877nLV7Cn6ihKCuYiK20sZYSdDDqKRuMNrHuliHuxo7sHzKdBVkORyMRCN1w1Pn69DH+QwjO1lzA+2Z3blcdO480VS/hpK4//jmXzZuGNksWo2LOfVz9GwRuWLSTDx6Bi937KDgPJUsFmsQdkooAhUFAs/6F8b6BTfLFpHXb8VEUE9D39C6biPBBOeLje0oYL9Vep/hv4PWDnh++gcP27aL3djoK8aVgwewadXofiTZuhIFwsyJseLAYEWJ0OulrrUf0XufvKNZ4FTOAvp84ik4pS+dIi1P9rxN5fj/PnoRNn6NlMp41CIe1rIuMqj53CxMdTMW3SBKqkCeggXIjBeUBEH/G5gWK3ZcNqnDh3kVCtwrSJmdwrhvAwsOt1VloyNyw+JgZTs9Nx8vxFzJ8xlZQ9hgwKV1L8SDS13sTyl+bwTOjp6Q3SA7RHrVJwLqD6g6JZ0zkfdBKQ0kkwZ0UCJMvzieNS0NtvgZzGJaSo32rl13dWNaekpyEnO4OzopWwoaWrXKBMkAXQbzleU8cukCbnMAUk1Ma4g7qemNTsv/a/AAMAS5llWrziVQIAAAAASUVORK5CYII=) top left no-repeat; }
		.scss { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6Q0EzRjZGMENGMTE4MTFFMTg3MTRCNzIzQjYyMkFEQTgiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6Q0EzRjZGMERGMTE4MTFFMTg3MTRCNzIzQjYyMkFEQTgiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpDQTNGNkYwQUYxMTgxMUUxODcxNEI3MjNCNjIyQURBOCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpDQTNGNkYwQkYxMTgxMUUxODcxNEI3MjNCNjIyQURBOCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pj+CKlwAAAZ4SURBVHjarFd7UJRV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) top left no-repeat; }
		.sql { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABHVJREFUeNq8V0tPG1cU/mw8Mx6/BhwS8wgQEaBQiIBYEXlIbUOVtF1VWWTBos9tpaoPqd31J2TRbFp10aqVEmVR0S7aKqEBNY8SFNImLUlT2kAIJAQDtjG2Bxt7pudem5QGO1zbqEc6YriPc797znfOPbaYpon18v7Jr4Z21/qeQ4ESWo4hGIl+ceLtV9940rrHz+MD6/W9j78cMouQsyM3zI8+O2PS/s83A7BerdhC6WpqQLW3/PXNQKyXLQVQ4XHhQEczgdCEQdgKPWQ2tIzphTAWI1FMB4KIxXW4VAWLoTCO+NuhuRwEogXDY+MMBDbjhLAH9GQKy3oSd+fDiMQTSKYMOFU7nA6VxhOI0pxdkVHhdsK3rZyDEPGEMIDpxQgmAyHYJRtcDgUy/bXLEqkM1a5AspVxddB3OQPh1YTCYRVh6lrqxBNJhGM6pVwcOn3ryVUkV1eRSqVhMEZbLByYk4EgPvi85ZuCsG0lCUdvT+DO/TkOJp02CFyKgCbgpjDNLy0zEGFa9u6mAMwcYx5VRpCKTT5RVRU37z1EkrzS09aIPbvr/jPfS/pJ/0CXkAc2VCsSyQI0VnowHggjQTfj6wy6qWFgNZ3mIQlH4zjqb0NzbRUaa3ZsbR1YiCUwE45BKbOSNxRYrZmtKXJ1gm5N+KA5VVy5NYHTQyPCYRP2AE9Funl8haVcAjGm9L1CN2exjlOsY/EVmtOxq2pbXhtiAEployluQzgN3XYJZRaLkNHWhpq8qVw0ANlq4SRUJAmyzUYcsPC8RxaUQYRMplZx7FA3uigDSgJAxN6g81kSqlIZKlwqr3iqXebVkBUfF5VlX4WGq3/exenBkZw2CuCAIAn13CRs2OHNa0MMgGGWxEwznw1RAIa50V9OxYaluBgJn6qrzmlDHEAO9BqVYpdSgWBUR3QlidlgBA7ZBp2yYyVhh+aogpdewacbqqHSs2yU4oG0kR+9lwjItL5Sy7vmSfsFPfCvgW+HLuP25DQqyzUcOehHfVWmxg8MX8Mvf/zFs+FQdwf2tjXz8Uu/jmH6YQB9L/UWX4jS/JExcfbnUZwjbW7YianZOZz67jwf7x+8hG9Id/q2Uxek4OSpftyauMfn5qk1m3ow98jGei04BCyOrNtp2VWH/Z3tGL4+xucu0y2Pv3AYB7vaH6XtwPAomuprYWTLsGgYcgJI0fPK5Nl9nTyvz/wwiJm5efT2dPO5xXCEd8Br67weN8ZDS/x/kw5mVW9trjgSpjPofxq9zhn9wZt9vLP58MSn2NfRyr2yQAcyrtwPLGB8aoaDYPtYN4RsR1SCBzKbo1TZLly7wQ8LLi1nGhObhGf8nfj6xwvY09yI38cnOLi3+o7xfSxsi7T2+4tXsr8V3AS6rbgQPL/fz295Z+YBeULBay+/CI/LycdlegNu/j2Jxroavrb//EW888pxaG4Xb0iZV5jUbK9Ed1tLcQCYHO7Zy/XxuQNESqZrwt4ENtfV2sQ1n72COFCISPREF7MvJ4BzV3/D/yX/CDAAE5LxKkkMp6oAAAAASUVORK5CYII=) top left no-repeat; }
		.tga { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABEpJREFUeNrEVktPG1cU/myPHwGbjJOSBAIKUKLiKKWxUpBAkWLDolWywFlkkUUre1+IUH4A7U8g3Re3UhZNFyWVWpAiSlaJUYtwWODABkJSAoiHCRgbezzTc+/4SY2ZAdoe6XjuPXPnnM/ndY9BURQUkuF+37j57FkPdFI6Hoe8uxvEw28D5c7ttyeUOuTo7NRrH/G5OUjz8/5UXy8OA1FIRpwgfdrQCIejyo++3qH/BcDH56rxmcsFuw4QJwqgznkaHfUX8bkOEILuZFteRnx8HAolXcWtWzBduJB756yoQL3TmduPRiL+nUNyoiQAuTBrEwkkp6f5U7h0CbHHP/K1pfUTGMi4XJDdNrMZ1Q47CvN8JDLjj5UBIZQrFXlrCzvBIDfI6flz9Wm2IBmJQFpagvXGDViuXlWVmUyotFpxLquHs8I9cRCIsgDYr7W7G8mJCcjv3//TUxSG+NOngM3Gv3nychp/LLxGWpaRlCRs7+1hYyeGagpNLBZjORGlz/oPD0G2WTgcSG9uckMg9zIy1dZCSSZh7eqCQgZ486qqgkAgRre3gb1kXpHFCpxhfAa2xkYkQqFrujzAKDUzw40bSYm8sQFzezvS794hGQrBdvt20XemggQ8Vh9gHsgyM27u6ID55k2+ZoknuN2Q02nshcNFZw9jzUnIYpj7Vx4PDHV1PB8E+sfZd6a2NkhUjob6ehhE8ci9w1gagJJj1F7MrZXTYn79QTV5owbS5GTR+XKsHYAia2LF1QIlFkN6blbTee2dUJa1+c/uAE6dAt68AaJbVDV2XjmwkcypLSylAVCClaUEld/qCvDXEtWgQZXtbKtMFcLJagOuuIDKyhMCEKUewvoBa0is3nkAybjRXFqzTDqoW6K1lTLWpBeAnFeytqZyIShBxx22sgqcP68XgKQ+19dVZm4WhKPVmaFAn+4QMKOV9uMNCUxHmZwqD4BlOOPjkm4AkoT/ig4E8DW12oG29iKxd3gY4fU1Lve3fASRbju2D4yNIcwSlWioq5vetSDw+xiCr14dcSZkAF68gOHhIJ69fYtvJkLqevE1Bq5fh6emBu5Hj7gsTFk+5O1SvUbso2s3SDdoT0NDTpZjzR5ISYV3s9oZMzK/6woCo6NY2Njk+8DISO6or7mZPwcn/8TUF19CNJoQzcwMOj2QyjMDwPpCZi/SyBXdjfH1+N27UB484Mz2PU1N5JEViIKZG/Y1NRbr0gwglcpzzgPqfnh2FvcpDGzt/eF73Pnpce4b3+XLlBcWDHR2IEpds+fD5mJd2kOQKg4BK6OMrP+3X/HzvXuY/6oXC9SePRTr4NQUfGRMpLHMO/QdojTE+lpc/JxIbTiaHWo1A0jm57r+X56oCjKyhdVVuAcHcY0SUaRb787yEv3bBBpoHHNTUkYzw+vw9Et42Z2RShbp0+2B8OJiySP75QzYfnpGc8LR+sAB8fo36G8BBgCR4ZBlNt6kMwAAAABJRU5ErkJggg==) top left no-repeat; }
		.tgz { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABApJREFUeNrEV1tPG0cYPb6D12DHdxyuAduAczEVtElLIIpUVelDkKpWldqHJlKVPvQpP6E/olIeSJqHSn1skvYB0geoi6qWooaGKBEOaR0VCCF015g1vqy929lZQjDgXS9C7UhHnvn2zOzZ8818GhskScLOdve6acLdNHwOOltu4ylyfOrmO5+Kl9V4u99HAzsxPmqckDQauzwrjY+ep/jt+6s0tvD7F9LUN14SM36lJWAnjFVoqhAKabCLCYrM2v3teGd4BH5f9JKWiJ3NiAM0m92J5sgwha/51KvF7MfQfeISERGpWUQVAaIqSkUOmdUERZb7Q4nLdtqCMDliRMTlmkUYqyRKFdY6J4LHhijcoZNKnKTAYHHBWN+mS8SBHBAFDptsgqKQ2XJAFmBkYLD6dIkw729AWdU2s60B3pZB2re7jit8SRZggcHcWPFl8p7A3E0iIon9jqh5/xSoC5BKLAQ+Qfslm7jFF7H0+GuwzxIyAZKYJ2EeEnGrzh6A2ZQiIoppQrxag4CSugNWB1xNbyonglgt81t7rmCDnas6x98NzIyPxGt0QF2AKLCwMl1w+EewvjhK+WYLgyOB07qP9P57QBTUZxnsCPTeUHJtDmrz9QrQcqDOEUWBnyf2RyGWcpp8/Q5IRc2JycRHaAicg8kgIdTz+SE7UIOl6dU5CqdvAKHolcNOgboDPJtEsaD0BUHU5B9gExZUJxXzpA5svZNbmdfk6y7F8oJq4NN/IxR+D8Mf3oFkcGjyX6L2FJQLGpVQQIZ9hiwRQoqxJv8AmzCvOqklfBbs83nM/vglIn0faPL174FyTnPiyTMf6+IfqgOH2aqcgtz/LKCcpb9/zc8glZypeNZ35iJc3hCSc1NYSj2gsaPtxxE5odwPJr67VsFvj/SjI9qvU0Bpk/76Ak1g7IO49+sYXO4gOsJx2O02TI1dR3aDQ+w15e/DdOIWsuvPET99AbG4IoRjVzD7yxjCvQPb6+lwgFduO/UWAj8sFisYhoHX70eW57D09BHeff8zMA4n5b198RNYrXV0nswRSJmcTnyLgcELCDW3bK+now7we29Icrkl8c3MCg0x9SY6nvzhDh27jngQ73+LlOYiJu/eJu4F0d7RsXetmipheaMCyhWrQPvORhvlrC4v0HFvrJukyYr0Pyt0PDs9SdPU/8bre9epPQXruy0hwQKNm8mHn+qL4eefJtDW3kafLi2S0nw0iMeP7iH15wK6Il14eF/ZvF6fBz6/W+8e4CrGra0usgcs2/HOTje8nhiWl9foeGgoBqerAWsvOPT0NpNInl5KlZpiIvMMegW8qBTQItNKFfHGBoKoY2sk34Dz8JAP9bgdu1Yr7VlPU8DDJ0/+s0L0rwADAAJRbvBnmfMWAAAAAElFTkSuQmCC) top left no-repeat; }
		.tiff { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA/VJREFUeNrEVktPFFkU/orqBw3dPLWNgFF34rBQIyJiZugEnc2YOOP8AOYHiPEXyPyE0ejCjbhzofERFxqd0C6MMMZYA6MgRqVBnhFtoN9djzn3FtXdRdPdVd0knuSm7jn3nHu/+u45p0rQNA25IlwYGHY2N/fCpijxONRYbAiXr/xRzG/zeY6tnHwnTtg9H/GpKaQ/feqXB86jFIhcqcI2Sue+ffD66voxcP7GdwHQ4ffj5/Z2WyC2FUBbQz2621ptgXDYPSQ1OYnkixd87gkE4CDaDWmsrcGexsaM/nhioj9SIie2BKDkZKq6uor02JheIfSG8fsP+Lz61CkIe/dmfFl2e5xO+H3e3JwvCaIoA8ryMqK3bkFLJLJGl4s/Es+eISVJcPf0wNXRoW8miqh1u+E31R3waHKiP1oAxJYAVOOt6DDP2bOIP3wILZnM94vFEH/yBEJ9PWfg/r9jeDkdgqKqSMoyIhSzEoliZ00NotEoy4kwhV0sCcBoFkJdHZLPn0OjDUH0MhH374e2vs6BqWtrOjDyE5ua8IiaEZKpHLbcQBMbTaimuMTIyCFLDBgA1PUI5LnPtJETVbSJ+vUrXER5+s1/SNIVuPv6THFidbXtyqkqlIR8RNb5mzt//AmO492UhA3QvF6Ih49AWQ0j9fZt1tfCsJyEKqOcMVFbC0ffaQi79LQSu3v0NYcD4tFjSL/8h4q/DQL5lStbApA3AMDj4cMAhJ07svOW3fxu5VevIJw8WTaAqkIMWBmgPqDRVaizs9b8LfeBAs55QgzASX3h/XtgcRGg/IDPR12KkpHypewroEIuHsVKb3YGWFri+cCFSpOPhQVdZyAOtAMl8qMAA8omQKSHqYfE4voh1IC4OF1F2qiqM/PDQcpesUwG0tRUqB2D6t8cJVq7IvYiLN6/yy4AWX9++wZQt8vQXK4Y+1kHsHEF7GCvt7LD2R6KUiaAjT5QsdgGIOuU9ba0mMxhyv5wKoUG+kpKKyt568H5eRxqbkYDfZINkb584TFlARj+5Uze0p/Ufi91HoNw7WreOrO9Pve7yRa4d5cDKwuAcPkv9La2Ypg2ZXMmg11dJp/AndsIzs2ZQY6OYHB0tIK/4nQ6O4z7y+hqVmcsETht4AIGj3ZmbJe6jnPb8K+/mfeyy4A+V8w2o01v6Bf/fgppaRnT9O9o2IbGx3GTRjiZMO9lGUAuWqOGDZtqZkCi+w2GQqbwEPWP4McPFXwLcgHIitmmbNLZ+mZ6FaUg5dYA5JSNNBtC4Pr1jG2IquDe+BjXmV1anDf5H6ZkDbO/6CKlZ4uBMM2Da+8y+jTr7UbdT73LC5VmZir/IyqVONsp/wswAIdBPQwwI8qgAAAAAElFTkSuQmCC) top left no-repeat; }
		.txt { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAo1JREFUeNpi/P//PwMyKJ68eL+7ub4DA4ngzpMXDHefvrwAZDr25cV9wKUO3T4WbIrczPQYyAFfvv0wePn+4/6iSYvwOgIZMDFQEajLSTJoykkaAJkgRwjQ3QGCfDwMuspyJDmCqg7g5eRgEBXiJ8kRVHUAOzsrgwAPN0mOoJoDQKmbhZmZgYuDHcURGgQcwUJMViEWMDEyMrCyQBwBA3oMciDK4Maj5+DcAWR/IOgAcsGZG/dAZQHYA3/+/WP4/fsPw/efv8By3BzsBl9//JwPZAYSDgEyLLfR12B49uY9XjUz1u8WoFkUcLCxMihJiZEebQwDDKiaCIeRA0Z8FACz8EBHwUCHwN9/A+yAEZ8N//77N3gccPPBYxQ5UFUrLMDP8PjFKwZ1BVmwGIgNq4LffPiEol5WQgyleibSAYgo6Jy7HLXhCbQ0KyKAYc7abQzWBtoMakB+z4KVDHUZ8QwXbtxm2HTgGIr6koRwBnVFOfJDYEZ9McMtYCj0LVwFZsNAqLsDw6KNOxjO37jD4GRuxCAlLgLGXvaWYLVq8jIMPg5WBKMUa2349+9fFPwPagCymK6qIoMQPx8wyD8yeNmao8iBEjEIo5tDfAiglQMIByDEv//8yfD24yeG7z9+Mjx6/pJBRU4GORuBs/JfIsoTrCHwB6gRGcPSBLLY1kMnGIT4eBnsTPQZ1u4+hCL3H+zo/xjmkOCAvygY5hMY/xEw1R86cxEYx9YMzhYmDO8+fmY4cPo8XP4/NATQzSE6CtAViwkLMqSG+MLFQS3f3OhgBklRYTA/JzqI4Qew8QmT97KzZOBgZ8NpKclpgJWFhUFOUhwuzsfNDca4+GJCgljNIdoBu05foltJCBBgAP9zlklycj+AAAAAAElFTkSuQmCC) top left no-repeat; }
		.wav { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABNBJREFUeNq8V9tTW0UY/+Vycg+Ek5ZwSbmUQqGUAYGKRcexddoX7ZNatcPYMuObOo5/hjO+VcdHXxxn2jp2Os7oiwpqLYWGS6egpEBJS7gFEi4JEMhJ4rdLE0tyDuRExzOz2W/3+3b39103q0kmk3j2a2r/vOdkY8krUPkFliIIBCIjRJ4ZHfxoVUku8zyNHICb376nGsC16/fxy09jCISkfUFknqdVwKm6sd/n291orLW2ENmTK3BZAMmE+sYQHGsow7nz9WhuKGg52Xb1q1wA6OUm44mEWg9w0xaXFsJ1yAyDQQ+9/uEV4CrIFd2qASQTybwAWCwGlB0RodPrIBh01B8MQhZAIk8LaLQaWKwGlJY7yAJaCAKzhHdfEPIuiOcBgKymYWml0cBkNnB36HQMhA56QacIQt4CcfUuSJAFfvxhHMPDc2mLSFIcG+FtaHR6iA7jFQpMlpqfHAhAysEC4fUogssRVB09hOmpZVgELXQ0X2QTuNbpr9gG1DjxQkcVPv3st5b/xAWPJgLo/XkcLW2VOFIpclOLTguWFiPwT6+gubUMrhI78k5DKRZXXNB3ewKDA9MwmYzc70w2FNrA1MQyqqpFAp/EkGcWpzsrYLMb8wQg7bWAp3+KTL6F+dlV0jIMo1lI1wsmK4oWrvHaWhQ6rRYOhwk+3yrqGw7nC2CvBTx9k5ijw81WIyxm4zPpuhtoYQq0xYUwxYNIIMNwVxRiKbABqVbMD0AswwVJhaRIUKwwWab91mYM6+vbKCTto1sS4lIia5/c60AWAHkEzAJMNhqNkcYRmKkSRqMSVURBdh8VFpCyclypYjLZWf8apR6loU5DQZngwExmfdY+uQPIiIGLXad5H6K8H7jzCJtbO+mCxWTZ4ZvkgnA4BpfLilBwCyWl1qx9cgewI7/QXmDGy2ePY+zBHDc5ywImqyXN7QUGfruvrGzDbhdgteoV98kBwP6ma2wqw8yTld1AI9m1VaqKwSjVBiq5oonS0HjgHvsC2Nk+eLHLZU/L2kjbggI7rwG5rv9XFpANSCkJCj/V67RKWeAoMsH8NJJZY7nOFGQ069k4JZeiM+Uy53IGsEMWuNTVge73X+S0o8iMN95qRV29i49Zz8YpudcvNHEZNmbzp+jmYzRrlVUi54fDURUAYjHcuO5J02XlhZx2ux18fPbVegz0T6P6qJPPe70LuNzdyXnf3xrBsdpiTrN2orEURpPA6dxdQEE0+XCR01aqbufON+LLL3pQd7yE8/iVPBnAmxfbcffOFP4cnePVkPEG7/lgNOpRU3OYglLD17C1MYXAVHABFZX1TU7X1rl4P/N4mfdMOz6eCfI+GIwgEmEpKOxqTmsHPT68c6njH1lay+ZVX0a//+rFaxea4aH7n81d+6YfXZc7Oa+ictf8jM+rJP0nqD9RivsjTzBwdwqtbVVooHfCrZtD+15KikHImnd8no9Hhh/z8fCQj4/76YC33+1Ab89f+PiDr3n77sY9tJ+q5nK+6WXKIIEKVjn6/phI7yf3yb4NX3rOxt+GZrrV3G4Rfn+IX7cplzCzO522PfMpXmpOdFr5O8E/s5Lm3x6O9D7wfHjm4Er41F+sX1vd3MMbG/XzfmE+++2Z4inxc46BmcUY/q/vbwEGAPq/yA7qiPz6AAAAAElFTkSuQmCC) top left no-repeat; }
		.xls { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABENJREFUeNrEl21oW1UYx//3JbnpTdKkvbcu2WadXbHrJq5v85Mvq4iw6ZDqhO2D+IIO/WbFL4LzpfugiPhFQVRQQdgEcSCTWcSxVabD4bYy1q3LCtO2prRN29slTW6Sm3s952TJbmuSJlnYHnhIzrnPOed3nrfccJZlwS5PH2g/7m1wbkeFkkxkkNIz3xx+e/SFUnYrzxMLGd3T2Vjp+Zi6GsO1aev5pwY2YTUIu/CooXS19SCgBijE17cFIOhfj+0dvRVB1BTALyu4S22tCKKmALLkhlq/hkE8vLU8CL5wplahZJ1DdMLj8jGIDU3XIZTSEGI5N9PjBjKGlS2h/KEW+5TqBIgOHomYAZ7jIYkSQCDy0gEMDR8nEChYHWUBaJEkFmZ0pFMmzIyJTMYiQCY5XESg2Q3Z68DCbAInR05gdGIEpkVtDCTTOmLJKHxuHyLaLPWERrbrrxiAHtK0TsbVEQ1xclPAZNFr2eKH1+9kNq33NeLobz8TWw/Wb6y/sVjKaoviQ+jcfEd5HrD+PyXwHO7e7EdoeJ54AgxiciyK9m4FBvFGaHgOSlDGpi61osQVy2mX+YwlKbuuxYPxUJR5IJkwMPVPDNpcEk6XSA5Xiq6tWRlSl6sBF4HhwAs8pieWkEmb2LJNgShWXtVisTIsJSpxdVRLI6ln2B0cToHBVHj56vsAJ3Cs9GhIqCcoCPXEauvKBzCtkjpDDuM4wKdIeYjZcIL0gnTJdTXxgDars6ajkrKkWe90CXmI8N8xGCQfbtoDJqEtpDEthUWS8Y2BOhJ3nnmhaa2cTUiyU0o3MT0ZL7q+/CQ0l4/TqQz0JQPR+RSLPem4zCa3qUCz3zBZA4kupFjb9qsSZI+juipYSRtfTLM2TN1N5RrxghIU2Nwi6wFk3hJIC74eb9Kq56YScG0UqyzDFQCeBmdBG1HkoJBwFH3/M63qAOiPza2S2w5QuAoIAFW/FMCBJ35BS2MnJN7Dvm9e8yAbf9T3R94up9137mQ29NmrD3zK1tifVwwQiYYxeOFLPLn1NTzW/iLGZs7g/MRQPklXAuzteQsH/xzAm4cfRYMcRE/zjlUBCobAMG4YH7v4HbZteBz3E33/6LPsWS5EdjsqV6bPoq+zH+cnh/DtqQE2rioEtI5z6uTdqHN4Uef0QhI8bC53G7sd1Y8HX8GRc18wu30PfYgd97607HnZAPQFI6e9bXsQJ69Vv144hGd6+tlcbjO7XbC+FZ89dxqj//6Fr4bexe9XfkLrHV3LbMoPQTpr3Ky2YVfXPrzz/V5EYmG8t/sQHmnfg/FIiD1/Y+fn+TUf/PgyTl4+gv19BzExF0JbsBufDL6e36uYcIX+nLr9EvtzqtavhUxcPx65DPuYJieFswu9eQ5alrJrqOfssqQlT/yw/1Lvqh5IpbLU4cjksnn7WIueLnijsfClm29Ehm7cskb0nwADAEWS1VX7MfgcAAAAAElFTkSuQmCC) top left no-repeat; }
		.xlsx { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABGNJREFUeNrEV12IG1UU/mYymWQmGTebTdyku7audtdsy6ppVywo6IIFKViL+1IfLC0tgiJUpAhisfjzpj7UB6X7oviHCIpVkILKttAilK2KEprgz3a73d12f5rN5j8zmfHem2Y2WSfJBHbxwOHOufe793733HPOzHCGYaBWRt8cHFc6xUfRphTzZZQK5Y++fi1+qBlu7X6CFWgg6m93f8xNZqCm3AefeiOCViRqhcc6yvYtQxjYspWS+PB/IRDy9WB4YFdbJIT1JODzdMGndFRNQgItr2NdCUguGcHbQrVdLUkI1pHa/uZ0itMhwuNW1g41JWHLA4WchrJmVIgZlda41ToEDm5ZQD6jged4iIILcMM2CVsElheLSM4XoJZ06GWDqA6NEBKcHHwBN4I9MpILeZyPnUV8OkaIkXFdQ1EtIltIk0h3QpYklqJEDrVNILTZwzaZjC0jR05Kk0eAjv77/FB8IsNEdgbw/U9nICsCtg0HVye7KtrrlzD9p3bQHgGLGHDwHPq2+fD3H0kUSNWjJKbiKQySjcllIPHbEjwdTkQfDhHPWGe3QK7LZhBaRyFP1u25y4vJyyvM1lQD8zNZ5LMaHA4eQ7uCLCYazTfWoxDJihOBsETIcOAdHG5M55BOlrB1yEdO2H5d4xtSbaLdvTIkr2CSoFkge5wt51m5wJKAbrRW0eVgm1MSxUIZM+Rl1GqObQ8YBN1M56ezKJLa0Bl0mSRukjTNpErN59omYDTWzHKJFZ3wnV4SCzLc0qon6CtZ04yGc63uoC0P0BMuXc+j83Y3nCLP+rpoQJLsoiTo5nNXMg3nW7nAMg11vd5WS2UUSKqlb5YqgUdSzcSQRV2SQL6ISIEiJLIrKq79kyEV0gXZ62yZhkIjD9RKLqWyMiy6HcxeWSoS9ztYX4o809NLJAt049ZpSbmmnuLCHLuiZgwaeKAe6e0ULTG06HSFpKZ5XreWXQLlsoGNENtXoG8UAYsSbesKNtIFlmlIPz6ovjv6M1OR95rPO+/Yw9oqhuruyOH/YCkupPTXYW0XIprPVF/47EFmP/3AcdZS2yeF6zB9XVE8vv0ITv7wHMbjX6DPH8WJb/axvn33v4ixcy+bWMO2B0gMVPX4V0/i3t5H8PGF15ldTdHq+NRCgtlHd3+Akch+LKzMYp5o4vol+OUwfp06a2KtGFgTUHVT3xo9jcTcJRx46ASzqwFaHd/U0Y/3fzyGo5+M4NtfxvDqE59C5DwY6N6BXDGNuwNRE2voNj2gEjDVMFmcyntnXmLtyOB+M0DHDk8wvZGcwfOPvcN0745ncXriFE4+M47z8e/w+YW3cWzPKTh5D1vPKgs4q59Tj8/Ffk77woNIZ1NYJC6VXQq6/b3MDiibTHx8ZoKNbQ7cw058dTGBSM8w66dC+xfTs2yskFHx5SsxrmUaltSKrxJXYzV9KSxnUux5dunaGjwdu2jav19Zff5r7nLNd4bNOqDRF8tGlAGL+vKvAAMAJXfR/4Li3LAAAAAASUVORK5CYII=) top left no-repeat; }
		.xml { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABC9JREFUeNrElvtPFFcUx7/gAhb2xUNWF9nFfbA8DKzSqNtGKyYmhjRNmiY16Q+NTRp/Mibq7038A9of2h9q0jQ1bdKmTVObNGqkRalFBaHIYmFFYVmWRxdBFlgEFtmZnnvdmS52XGbIht7km7lz5twzn7n3zLk3SxRFpLZzn31zw1lmOQyNLRp7ipn5hYufnH7/g3R+L76PG1J19tOvb4gbaNc6/OJHX3wv0viv1gNIVTYy2LwuO3YUmU6sB5HaMgpQaNTDt7tSE0RGAQz5r8BSZCIIt2qIjAJszcuFmWbBUmRWDZFRgBzdFhRszdMEka0mU9UqOysLuTk6TRC6TM5A14MghsYnIRBMIiFg5dkqluJx6Ck3dHMxBjFLbmfWBRA38PKD9VUYn46m9blw6Vevqhn4T7VSaAuLS/zLWJt8EoWluBAOa6lmcE1JOBWdww/XWnHy/McIjUdk+/W797iNPWM+WpqqGeh9GMQdfz+6A4+SliwY9QWyn217Kbf91t7NtbfaDV99DeoqHRsEIC3HV3C7pw9dfQ8xMxfj9oqyHRTchVdrPSgyGeRc8Xlr4dllI98BghxEcCzCdbWtk3wrcWT/Hu0zEJmewa17ffzeuq0YR19rQNWu8pfOEivDR30NXA+GR/FL6x3EV57xGI37vNoBWFVjpZUFWVqOo7XTzxNvt6sCebk5isHmF57ir0ch3B8MQRRE5Op0tFT5aZNaEUAQgBKzGafeext9QyH0D41gLDKF1rt+rlqCOFBXzfPgeXLOot0fwGB4XI7htu9EDfm5yq08nsYc+Je4xmnnYl/XQ+sbCIYRIKBqhw0G+jrWhsITCI5OwGwoILsd1U6bDCeuU1WUAQRRYafLx8GGOi4GwJZH8rOWFqPp0H446WvTxVC/BKKQdpDHUb7GjwGoGachB0RsVlMESAjCpgFkK8+AIOtSyx8ITUR4nxWam3/28j67smeSX+o9+2skv1SpBkjQEkhiy/Ht5RbEFpfx5U9XUGgycntb93003+5CP/0V7P67Ky34+fot3g8Mj/DnqXESL1lWRYBVopXU9IYP07NzOP/5RdR7XHDZyridhXNXlNNvOUIQI7wvjWXQ4gtxVjXNQCKxRo379uDJ7DwOU0mVbKy6uQlmIDSKgeGwvPFIz0R+KFkbRwOAIIuV38s322nzMeLH5t9lO72B7xGLVKZHI4/h3GmVx7ITEZKnolSpXwJylsRebt1WgnMnjvNy3DMwyO1iMlckCKNeL49lS8BsfHZI4b8fc7vq33A1OV3R+RjGJqdw/NgROvHq8O6xRnT0BmhXtGN7STFy6AC6t8aDialpPsZBlZBdTQY98mgzu9rWkdxNS/BW4+vaAQwF+fjwnTdlG9vzmVi/6dAB2d9utXAb82VXb5WLSymmukKUEP7fStjc2btpAP8IMAAuNAN4g8DU6gAAAABJRU5ErkJggg==) top left no-repeat; }
		.yml { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAphJREFUeNpi/P//PwMyKJ68eL+ytLgDA4ng/eevDO8+fVnQlxeXiE8dun1gAWRcNGnR/v9kgJ0nL/6vm73yP1D/fEIOQMZMDFQEBiryDJLCAgmEHIEMqOoAQT4eBisdVQZJIX6iHUFVB/ByczKICQkwWOmqEe0IqjqAg42NQZCXmyRHUNUBrCzMDFwc7HBHWOoQdgQTMSmVWMzEyMjAxsoCd4S4MMgR+NMECzVD4MyNewx3n75k+Ad0zN+//xh+/fnD8P3HLwZeLk6G1x8/gxzxAaiskKAD/pNhuY2+BsOzN+9xyjsB8Yz1uw2ICgGM0oqoBMjKoCQlRrI+qiZCcgDVQoC6DhgNgYF2wL9/Ax0FDAMdAn//DbADRnwi/EvHVMiEywEgvH7vYYZrdx/C+YfOXgJjkBhI7tW792BxdD4yGxmT4ID/cDx33TaGL9++Mzx49hLMFuTjZbh+7yHDrmNnGA6fvQxWs2HfETD/5bsPUP5ROBsZkxwC3vaWDBwcbGDDV2zfx+BkbsSgIi8DTqSqCrIMNx48Bqt79OIVg4yEGLD8+A/3KYxNXgj8/QvHsb5uDFsOHmd4+/4jg5etOVgMlEhlxEQYHgMtvgEMDX01ZbDYP5BFQHmIA/6hmAMTJy4RIpUDkiLCDKpy0mCfs7GyguVg2VRPVYnh+MWrDCpyMgxvL32EWvoPyQH/yEuEf4AakfF/aJDC+CA2CCvJSjFcunWPQUtZAazmL1QNCDx68ZrhJjCKQPgzMA39weEYFuwOQA0uCWAo8PPywMVBbBDQUFJg0Hr4BNgaZgGrYQU2SEFqIA67C8Yg4OdozSAlKkK+A7zsLFDEDTRU4HLBrvZgcWQ1KcE+BM0kOg0MSEm46/QlujkAIMAA7+kxX/dcFXgAAAAASUVORK5CYII=) top left no-repeat; }
		.zip { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA9dJREFUeNqsV0tPE1EU/voUGQstpbRFRSUEeQnF9zOIT2Ki/gGNLlwYd2pITFxoYnRr4soYXRhXbmWhaJT4SFDxgVEWqI3EGCtqaWttpdN2xjt32mkLzO2t9iaHzj1zzpnvfufcey4GWZaRP+5eM521ObvOWKx2lDKSYhjR4JtR8ti7+4gU1rOb+T3zXEYt6y7C4e1hfvDG6ZzrwfMphAIP4R8+5PsVnRwavCoyQeQPow7OomKdZ9Akq7PZW9HRvs9nNlsJCKP9PwAUHwubezTRglldqHZvKwmEWZ8BiekY/f4obyapPpYqGIXlBATQAfjejd0qmg4dACSgzAbgWbYlD69EfQwmAcaKhVTFC8KsX6lsAPHQ4wLAio/BaCVpcGpaFYRMQAzogpibATlNFpVmAli28lQeAWnqA5hUFizIAyGjMf7L9/7DPaUmeokqzJcCsAEEP13Qnr2t/dQn9O0R/K9BfWVJJJhi5ICIQEoBguD0xWLBIfKym4MBKbMi/eFt6S9gzOHegMbOE7r2rmZgfOS0j7MGCGRFWDUwNQjX8ov4MX4czqUnqc7hXs/ecmSXlFADSWawutZrqKjqpr/FbAu3NxcAhQF2UINsUqsllQCsZQagpKDYqvzPjqHKsxuxqadoWnuJ8/tSKQyI7O6XlPDp1Tn63LRGLHcKknQbsUZ4chypZHZhvAB4GVDolxPsXRCL5vpCcAy2miYOAmS+bigrDKRFXYlHPpMPdmHHgQeo8WyCGJ9i2mtSEgPStP5C0glEgt/w88sI+Q3AZLYy7UsvQiWnjICVQjU61h3G2PB1uBevRrWjvrwAZClBVskO6F3SRUVlZLrcRSjyrajUwc/AtNrJyg4gzcuAkoK4Nv0e8GNo4HKBSe/eoxh7eQ913ka0r9qFm1dy3VGw1WDzrkOwO+uLpsCoXwNxTex2B3r3HKZisVbAXuOhOtq0lGM7A9a3vo/aCAuq8O7FnYIYVPhT8KcgBWbSd2rr6jDy5DY9Tjdu2090aTVg9uKR2b70IkLvE6nZaeRPwR9yqfldoJrwj2Pi41ts3NoHYb5JfZ/tGRnb0ef31RQssMHX0zcrxly3rLkZoJTljtpwKITRF4/RtqIT9fWu3Dt6b0ho857tO+Fye/LiRGc3OT4GYsQ5ok3fvBwm//uJ+DEZwEMiyujsbid/U7RnZG3lGX7/vAtkKUpsTdq8ocGB2tr5BTYWY4zqBcFCbENobVuEyookfdbvRUneFIQIW7lu2LBI2SyVM6wiGT0putQkWporqU5OsRjgBDDxNUCq3FD2cyg2PbsG/gowAPGGQK+e//OKAAAAAElFTkSuQmCC) top left no-repeat; }
		.7z { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA3XAAAN1wFCKJt4AAA7pGlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMToxOCswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDE6MTgrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6YjVjNjgyOTctYTBhMS00ZDNlLWE3ZmYtZGI0ZDk0NWE3MTQzPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD5hZG9iZTpkb2NpZDpwaG90b3Nob3A6YWMxZGQ3ODctYWMwOC0xMTc4LWEwNzItYmY3ZmM5MTk3NTBiPC94bXBNTTpEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06T3JpZ2luYWxEb2N1bWVudElEPnhtcC5kaWQ6NDI2MjBkMjMtMDk0Ni00MzAzLTkwNzktZmEzZmEyYzRiNTJjPC94bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ+CiAgICAgICAgIDx4bXBNTTpIaXN0b3J5PgogICAgICAgICAgICA8cmRmOlNlcT4KICAgICAgICAgICAgICAgPHJkZjpsaSByZGY6cGFyc2VUeXBlPSJSZXNvdXJjZSI+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDphY3Rpb24+Y3JlYXRlZDwvc3RFdnQ6YWN0aW9uPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6aW5zdGFuY2VJRD54bXAuaWlkOjQyNjIwZDIzLTA5NDYtNDMwMy05MDc5LWZhM2ZhMmM0YjUyYzwvc3RFdnQ6aW5zdGFuY2VJRD4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OndoZW4+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3N0RXZ0OndoZW4+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpzb2Z0d2FyZUFnZW50PkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE0IChNYWNpbnRvc2gpPC9zdEV2dDpzb2Z0d2FyZUFnZW50PgogICAgICAgICAgICAgICA8L3JkZjpsaT4KICAgICAgICAgICAgICAgPHJkZjpsaSByZGY6cGFyc2VUeXBlPSJSZXNvdXJjZSI+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDphY3Rpb24+c2F2ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDpiZGY2YWIzNS1jNWY3LTRkYjEtOTZkMS0yMTVkZmZmYjBkNGU8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMTAtMDVUMTc6MDE6MTgrMDE6MDA8L3N0RXZ0OndoZW4+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpzb2Z0d2FyZUFnZW50PkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE0IChNYWNpbnRvc2gpPC9zdEV2dDpzb2Z0d2FyZUFnZW50PgogICAgICAgICAgICAgICAgICA8c3RFdnQ6Y2hhbmdlZD4vPC9zdEV2dDpjaGFuZ2VkPgogICAgICAgICAgICAgICA8L3JkZjpsaT4KICAgICAgICAgICAgICAgPHJkZjpsaSByZGY6cGFyc2VUeXBlPSJSZXNvdXJjZSI+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDphY3Rpb24+c2F2ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDpiNWM2ODI5Ny1hMGExLTRkM2UtYTdmZi1kYjRkOTQ1YTcxNDM8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMTAtMDVUMTc6MDE6MTgrMDE6MDA8L3N0RXZ0OndoZW4+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpzb2Z0d2FyZUFnZW50PkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE0IChNYWNpbnRvc2gpPC9zdEV2dDpzb2Z0d2FyZUFnZW50PgogICAgICAgICAgICAgICAgICA8c3RFdnQ6Y2hhbmdlZD4vPC9zdEV2dDpjaGFuZ2VkPgogICAgICAgICAgICAgICA8L3JkZjpsaT4KICAgICAgICAgICAgPC9yZGY6U2VxPgogICAgICAgICA8L3htcE1NOkhpc3Rvcnk+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgICAgIDx0aWZmOlhSZXNvbHV0aW9uPjkwMDAwMC8xMDAwMDwvdGlmZjpYUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6WVJlc29sdXRpb24+OTAwMDAwLzEwMDAwPC90aWZmOllSZXNvbHV0aW9uPgogICAgICAgICA8dGlmZjpSZXNvbHV0aW9uVW5pdD4yPC90aWZmOlJlc29sdXRpb25Vbml0PgogICAgICAgICA8ZXhpZjpDb2xvclNwYWNlPjY1NTM1PC9leGlmOkNvbG9yU3BhY2U+CiAgICAgICAgIDxleGlmOlBpeGVsWERpbWVuc2lvbj4zMjwvZXhpZjpQaXhlbFhEaW1lbnNpb24+CiAgICAgICAgIDxleGlmOlBpeGVsWURpbWVuc2lvbj4zMjwvZXhpZjpQaXhlbFlEaW1lbnNpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAg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) top left no-repeat; }
		.bz2 { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA3XAAAN1wFCKJt4AAA4HmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMjoyMyswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDI6MjMrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6ODQzZDEwYTYtMWFhYi00OWYxLTk3YjMtNzIzOTZiYmEzMjIyPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOjg0M2QxMGE2LTFhYWItNDlmMS05N2IzLTcyMzk2YmJhMzIyMjwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjg0M2QxMGE2LTFhYWItNDlmMS05N2IzLTcyMzk2YmJhMzIyMjwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDo4NDNkMTBhNi0xYWFiLTQ5ZjEtOTdiMy03MjM5NmJiYTMyMjI8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDMtMjNUMDM6NDQ6NTBaPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj45MDAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjkwMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAg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) top left no-repeat; }
		.csv { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA3XAAAN1wFCKJt4AAA4HmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMjoxNSswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDI6MTUrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6YjcxOTFmM2EtMWY3NS00ZGUwLThkZmUtZmMwNGNmYmU5OWMyPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOmI3MTkxZjNhLTFmNzUtNGRlMC04ZGZlLWZjMDRjZmJlOTljMjwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOmI3MTkxZjNhLTFmNzUtNGRlMC04ZGZlLWZjMDRjZmJlOTljMjwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDpiNzE5MWYzYS0xZjc1LTRkZTAtOGRmZS1mYzA0Y2ZiZTk5YzI8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDMtMjNUMDM6NDQ6NTBaPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj45MDAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjkwMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAg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) top left no-repeat; }
		.docx { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA3XAAAN1wFCKJt4AAA4HmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMjowOSswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDI6MDkrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6MWQ3YzQ5NzgtYThjYy00NGU5LTk5ZTktM2I0ZmQyZGQxMjA2PC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOjFkN2M0OTc4LWE4Y2MtNDRlOS05OWU5LTNiNGZkMmRkMTIwNjwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjFkN2M0OTc4LWE4Y2MtNDRlOS05OWU5LTNiNGZkMmRkMTIwNjwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDoxZDdjNDk3OC1hOGNjLTQ0ZTktOTllOS0zYjRmZDJkZDEyMDY8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDMtMjNUMDM6NDQ6NTBaPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj45MDAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjkwMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAg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) top left no-repeat; }
		.flac { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA3XAAAN1wFCKJt4AAA4GGlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMiswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDIrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6MjgxMmZjNGMtOTJlZi00NmRiLWJiYzgtMWUxZjJhYTk2MTEzPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOjI4MTJmYzRjLTkyZWYtNDZkYi1iYmM4LTFlMWYyYWE5NjExMzwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjI4MTJmYzRjLTkyZWYtNDZkYi1iYmM4LTFlMWYyYWE5NjExMzwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDoyODEyZmM0Yy05MmVmLTQ2ZGItYmJjOC0xZTFmMmFhOTYxMTM8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDMtMjNUMDM6NDQ6NTBaPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj45MDAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjkwMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAg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) top left no-repeat; }
		.gz { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA3XAAAN1wFCKJt4AAA4HmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMTo1MyswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDE6NTMrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6NGU3OGY0NDUtNTA2Ni00ZTFiLWExZDgtMTA1MzFmMDZjMzEwPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOjRlNzhmNDQ1LTUwNjYtNGUxYi1hMWQ4LTEwNTMxZjA2YzMxMDwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjRlNzhmNDQ1LTUwNjYtNGUxYi1hMWQ4LTEwNTMxZjA2YzMxMDwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDo0ZTc4ZjQ0NS01MDY2LTRlMWItYTFkOC0xMDUzMWYwNmMzMTA8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDMtMjNUMDM6NDQ6NTBaPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj45MDAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjkwMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAg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) top left no-repeat; }
		.svg { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAA3XAAAN1wFCKJt4AAA4HmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMTozOSswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDE6MzkrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6OGU5MjE5OGYtOTJiMy00ZGE2LWJiNzYtNDczMjllMDZiY2RkPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOjhlOTIxOThmLTkyYjMtNGRhNi1iYjc2LTQ3MzI5ZTA2YmNkZDwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjhlOTIxOThmLTkyYjMtNGRhNi1iYjc2LTQ3MzI5ZTA2YmNkZDwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDo4ZTkyMTk4Zi05MmIzLTRkYTYtYmI3Ni00NzMyOWUwNmJjZGQ8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDMtMjNUMDM6NDQ6NTBaPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj45MDAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjkwMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAKPD94cGFja2V0IGVuZD0idyI/PnDLOkoAAAAgY0hSTQAAeiUAAICDAAD5/wAAgOkAAHUwAADqYAAAOpgAABdvkl/FRgAABu1JREFUeNqUl1uMXVUdxn9rrb33uc6cMzOdztAyjlUEWohNSwMEiIZWKgmRB1OMxgcTg8RIoi+GB42+GOOTRl80BhIfeDDxQohcDDFqgkCVAtKbTVtKr0ynM3TOZZ9z9tl7r7X+Ppwp7ZSZ9nS97qy1vvX9v+///7YSEa5cx3d991/B+MgD4WStJbllmKUCg4uTWud/Z/8h4r9mArMgHlAwOF1QaEDY8tYzK/YGVx8W3TQ2MrJ1E7W7P1Nz3f6lE665TKVI97058hMLO/Ne/y/e+91K6fYw4D8GQKzDpzmul+KSdCgAaHBJSjRSoRiV7onbzee88o8prRs3DsCDXwbh+/m1dwsoowf0Og+jRYqlCpxlV9xqvuy87FZaxepGACACzuNzhzi/NgPLl/vc0Tw+h51voWtlzOQYRecRkXvjdus5L/4xpXRzeAbEI84jucVbtzYABUFoaBw5y/y/jxJmlslttxLMrkOco6QUnOMLnXbrJaf9w1qZeDgGPIjzeOuRzK0tvCggPr3Ahf8cRWUW+jm6WiC4eRxJMiyKkggo7ovbzT8Lfo9Sqj1UCVYwsFYFRECEic0z5I0uyaGz6LEKhTtuHhBUCrGhoRwY1Bkeiputl7z2DwPd62vAeyT3YP3aAKyjNDFKcXyUPO5RiCKSYx9w8YU3cY0uvtFBWgk+6SORJojCBzKbPwd88TouELzzeJvj12hESil0IaR/MaZ9+Az0cqqfnsb1Uzp/2ovpW0ytgrp9GrO+CkoRhQHdg6fvGUKEl0rgkVUYUEaB0fTOL9E8dBppJYRa09t/isrWWQr33YY6NIe+ZQq5ZRKcgAIdGvon5m/Ahtbi7UoGlNaApn1yjvjYHCrJKJQLBFGEySxuIUZv2Qh3bsCWC0g7AS+gFCrQ+Dwf1gWCZMs6+Ohyhc8trVPzdE9dINCG8sYJjPOoTobRAbpexjsH1cKgMaX+Us2WtTVUH1gugbXIlQwYg+2nJOeXUFaIRkIqW2bwjS7uwBlMrQLVApLmg1dfOeSUAgziZQgGEMQ5JHfIlTb0Ak4IwwAnKWG9gm90cKcvopWGahHRQJZ/vHldYkD8EACcIE5w1q0QoQoUtpfiOymFepXi+hr58QvoToYpR0i1gM/d6tZVCiWDc4crgfcDBnK3wnp5swvWUfrEJG4hhnafoBBBuYCLDPTtSuqvZMDrVb+t3gnt8jCyl0Xkkwz7YZvK7Hp0Pyf/YIlg3SiuVkJK4bUHlwLl/XAaEM9AwVdoQAWavNHBlItE1SL2xCJ6qo6bqEKgBl7P3TUikwLNkCJcdoG39iMA4hQ4T3G8il2M8euqqHoZrINcuG5qUYDSAyFf3wX+sgYulUAEXS7gUosPDKocIUk2XFpaZkCUY7VGsHoich6f2wFlTvBpBtahwgBVLuDbPfCCKkaD1+UOn+XoUgRaI0mG5HaQlooRGA0K/CoM6LVasVjBpzm22cGMVih8agMYjV2K0aUiKgzwaYbkDm8dplpGRJEvtsBook03oetVbDPGJ+nAgkOVwA804JIUnGNsz4OMfH4beEcwPsrCb54nnB5j5P7Pcu5Hz5CeW2TiKzupf+l+zj71ayp3b2bqyT2DKF4I6b19lMWnX8DFvVUB6FWDhvf4bkI4PcH6Jx6l984xTj35C9r/fJtgfIT0/XlKd2wi2rQB1+wyunM7kuWYkTIbf/xN+ifnOPeD33Lx2VeIZqYwk3V8koEfSgOCXx6h+YUGvQMnqD20AxUZ4jcO0nnjEMoY+u/PUdl+K93/HiXcMMn8L/9IefttSG6Z+8nvsEsxaE1y9Ax2sYkKQySzQ2gAAefAGLKFJie/83M+/P3fiWammPnpE2z84TfIF5q0XtlHafMstZ07kNwSv3YAM1rFpxn5YotwcpzJbz3KzM++Tf2R+7DNDuLcEACWE5FtdSltnmXqyS/TePE1jj7yFM0X9zL64HaC9XWWnn8Vs26U6e9/lfj1g/RPnqe7/zjhujrrvr6b9OwC53/1ByTNCKfG8Fk+3CxAlgdS7sgvtqlsu5XarrtIDp+itHmWxst7cXFCdqFBdnKe4ic30PrbW6gopP3qARaffYXp7z3G6M7toBSu26fx1zcRL6z2g6Ku/jk9vOPxd0WpraI0PknR5SKjn9tKOD1G/8gZOvuOgDbgPdHMJOH0BL397w2Ysw5JM6r3bqF0+yxuKSbee5h8oYmplVHiW3fue7p+bQB3Pf6uF7Z6GUQpSVJsp4eIoI3BjJRRhRBQ+HYXn2QE4yMQmgF7ucO2u4izoDSmVEBXioDCKFp3vvNM/ZolUIpYIyhogUApIihGCIJS6nKzQjDVElItDQLPpYeEGj0+sjIMDTZVlFKtq+/7/wBf9gU7xjLu2gAAAABJRU5ErkJggg==) top left no-repeat; }
		.tgz { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAAsTAAALEwEAmpwYAAA4HmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0wMy0yM1QwMzo0NDo1MFo8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxNS0xMC0wNVQxNzowMTozMCswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDE6MzArMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6MDRjYzMyMzQtMWEwYS00ODE0LWJiYzYtYWJlMDMxZmI4ZDhhPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOjA0Y2MzMjM0LTFhMGEtNDgxNC1iYmM2LWFiZTAzMWZiOGQ4YTwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjA0Y2MzMjM0LTFhMGEtNDgxNC1iYmM2LWFiZTAzMWZiOGQ4YTwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDowNGNjMzIzNC0xYTBhLTQ4MTQtYmJjNi1hYmUwMzFmYjhkOGE8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTUtMDMtMjNUMDM6NDQ6NTBaPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj43MjAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjcyMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxYRGltZW5zaW9uPgogICAgICAgICA8ZXhpZjpQaXhlbFlEaW1lbnNpb24+MzI8L2V4aWY6UGl4ZWxZRGltZW5zaW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAg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) top left no-repeat; }
		.mov { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,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f3d7ZKbl/zee6tEAAOAAcAAAEjsBIjsB9nkCGAKA4lqACE+AGE9AbW3lz9+Tm2ZrM+VFbQPAl09OvosHIJoCfOufnJxom5z8VguAPQDo/DDVzQEAlA4B7FtjE2gXemX80dp/78j/AGMEBZGSUTncAAA6MGlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgICAgICAgICB4bWxuczpzdEV2dD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlRXZlbnQjIgogICAgICAgICAgICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICAgICAgICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0xMC0wNVQxNzoxMDoyMyswMTowMDwveG1wOkNyZWF0ZURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MTA6MjMrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDx4bXA6TW9kaWZ5RGF0ZT4yMDE1LTEwLTA1VDE3OjEwOjIzKzAxOjAwPC94bXA6TW9kaWZ5RGF0ZT4KICAgICAgICAgPHhtcE1NOkluc3RhbmNlSUQ+eG1wLmlpZDpkMTllYTZhNy1mYTAzLTRiZmYtYTVkZS1jNTY2NTBlYmVkODI8L3htcE1NOkluc3RhbmNlSUQ+CiAgICAgICAgIDx4bXBNTTpEb2N1bWVudElEPmFkb2JlOmRvY2lkOnBob3Rvc2hvcDowMjExNTE3YS1hYzBhLTExNzgtYjEzNC1mMWY0ZWE5MmRiYWI8L3htcE1NOkRvY3VtZW50SUQ+CiAgICAgICAgIDx4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ+eG1wLmRpZDo4YzExNmU1Ny01YWE5LTRlZGEtYTdhNC1jNjA0YjY1NTU1OTE8L3htcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOkhpc3Rvcnk+CiAgICAgICAgICAgIDxyZGY6U2VxPgogICAgICAgICAgICAgICA8cmRmOmxpIHJkZjpwYXJzZVR5cGU9IlJlc291cmNlIj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OmFjdGlvbj5jcmVhdGVkPC9zdEV2dDphY3Rpb24+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDppbnN0YW5jZUlEPnhtcC5paWQ6OGMxMTZlNTctNWFhOS00ZWRhLWE3YTQtYzYwNGI2NTU1NTkxPC9zdEV2dDppbnN0YW5jZUlEPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6d2hlbj4yMDE1LTEwLTA1VDE3OjEwOjIzKzAxOjAwPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPnNhdmVkPC9zdEV2dDphY3Rpb24+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDppbnN0YW5jZUlEPnhtcC5paWQ6ZDE5ZWE2YTctZmEwMy00YmZmLWE1ZGUtYzU2NjUwZWJlZDgyPC9zdEV2dDppbnN0YW5jZUlEPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6d2hlbj4yMDE1LTEwLTA1VDE3OjEwOjIzKzAxOjAwPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OmNoYW5nZWQ+Lzwvc3RFdnQ6Y2hhbmdlZD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3BuZzwvZGM6Zm9ybWF0PgogICAgICAgICA8cGhvdG9zaG9wOkNvbG9yTW9kZT4zPC9waG90b3Nob3A6Q29sb3JNb2RlPgogICAgICAgICA8cGhvdG9zaG9wOklDQ1Byb2ZpbGU+RGlzcGxheTwvcGhvdG9zaG9wOklDQ1Byb2ZpbGU+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgICAgIDx0aWZmOlhSZXNvbHV0aW9uPjcyMDAwMC8xMDAwMDwvdGlmZjpYUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6WVJlc29sdXRpb24+NzIwMDAwLzEwMDAwPC90aWZmOllSZXNvbHV0aW9uPgogICAgICAgICA8dGlmZjpSZXNvbHV0aW9uVW5pdD4yPC90aWZmOlJlc29sdXRpb25Vbml0PgogICAgICAgICA8ZXhpZjpDb2xvclNwYWNlPjY1NTM1PC9leGlmOkNvbG9yU3BhY2U+CiAgICAgICAgIDxleGlmOlBpeGVsWERpbWVuc2lvbj4zMjwvZXhpZjpQaXhlbFhEaW1lbnNpb24+CiAgICAgICAgIDxleGlmOlBpeGVsWURpbWVuc2lvbj4zMjwvZXhpZjpQaXhlbFlEaW1lbnNpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgIAo8P3hwYWNrZXQgZW5kPSJ3Ij8+/ZCTiwAAACBjSFJNAABuJwAAc68AAPamAACJjwAAeXAAAPCEAAAv1QAAE+VcWq1dAAAGoUlEQVR42rSXW2wcVxnHf2cuu2vvzbte3xPHaZ12baeNiqOkIFpRSlWECg0qSCCQeAOJhogXJF554AlxeQL6wAviBQmpSNBEKg1SSbmJECWQkIa4OHGS9fq+O3uZy5lzDg/rOjhx4rVUPmm0mpmdc37nf/7fN98IYwz3xk8nfzjXN1koGHX/vR1DCGJfIpvRjRdf+8xn8xN9C7ItQQCGzu9mFA4Utz+6E8Cvv/hL75nvfiIr/Xj3uQFhC0Iv5OzXf4OddK4894NPfqrvkcKCbMm7f3oAgLXToEYZGQcKFcS7HvHmEdVDxj68n6kTUzNnT505U/vPxribdnddgPXAZXGvMg/fDmMMwhLMnnya8ony9NlTp09vQZi9ApgHUj00tDIg4OjJpym/PDVz9tTpM7tBWHyQIUDHGoDZk8cpvzw1vRvEBwuwpYRGCMHsyeNMvQ8xvzHu9rr/RwADwrKwMwncTAI75eBkkxz79jM8+aUj029/683T3q36/nsfc9h1u83u+2/AStjUb25w6bW/bR/KsRCuhWxGM7979Y2ff+UvX3tud4A9ms9oQzKfZPLENK3lJsKyttEJS3Dk1ePMvf6vo90pYLZPLCyB0btXxZFj+7AcC2EJhOhUSIFAxQon5bB2ZSnqDuB/c1FA7EvspIOwxNY1YQmEbWFZAmNARYpgwydqhgR1nziKUVGM1prMUI7B8hBaavbkAWFbhOttLv/sAuUvH6F3KI2OFDrWBLUAf71N/dYGzUqDRrVOUAuII0kUSNx0At9rI1XE9EtPMnx4lI4sXSuwuX+ORe36Ohd+9CfsHgdjDI1Fj1R/D82lBumRLFppSlODTDzXT08pTTKTQDgW1Ut3GDs6jrdYR8VqxzrgPMx8RhuclMOBFydpr7VQUpEspMhPFslPFOjp76XvYIHVq8v0lwfpKfSilQZjkO2InkIv6VIGv+bvtPjdTGi2zDg4O0Iil6S12GDoQ2MYrTfrf0dVYQtkOyLRmwABltPJAq0NdsLBcsXdMfeiQCePBTrWGG3QSqNkvM1MTtLBStjYCRtjDM1qg8Dz8W7XWbm+hDGanmIaLLEHBTqdAgiwXZtgzWflH1UKj/WDEJ1U28wA6Uta1Sa33pnHq3i011pgg9ubQLiC1fllnvrCMSzb6h7AGIOWBi11J/UE2CmHuC2JvIDYj/HX21QvVli5UqW12iQ7mmN0dh/FQyXSAxnc3gS2Y6OVxmiNbEUY3SWAjjWyERIHMcoXJPuSzJ/5N3EYM/fbdwkbIWEzJNWXYuCJYaY/f4T8eN/WhDrWqLYkNhIh3vdKiIriLgGkJtoEEICTctn/sYPU5taIpaKYTZAZyZI/UMBNJzDKEGz42zx2bztotEZFqlsAReSFxL7EKIOwBZmhDJnhTOfcEpuNaEzkhRjduWbZ1lZPICyxVTmNMsRhjArj7l7HWmrCegAIkvkURhvaqy1kq1OSlVS0V1oE623cHpfcvjzJXJKgFhA1I5K5FEYZQi8krIcIW+D2uqhQdQkQK/zVNsXJIuVXZhh8YpjmYpPS1ADlV2YoPT5Ae7nJ6NF9lD93mKGnRjj0UpmJjz9CWAsYf3aCkaNj+GttIi/g4AuTlMqDyGbUvQKyEW3le2Y4A0qTHsoAEAcxqb4exj4yzntvXOOv33uHy7+4yMDhIXJjOaoXKhQeLaIjheXaZIayLF2o7OiBBwJEXohRmvZyC8uxyY7lcHpcWtUGKozJjuWQrYiFt2+gQkX1fAXvZo3iYyUqf76Fm06QyveQHcsRNSOWL1VBd1kJlVSE9QAdG1pLTQAOPP8owYaPCuKtVDPKENYCMKaTspFC2BZrV1fwbtYYnh2ldyBN9e93aFY8lOxSASM1UT1ES0XsS1YvL1GaHmT93VXCRggGli8uksglKU0P0lpskh3LUTjUz/LFRfzVNjfeeo/9z05QfLzE/JtzyKbcgwmlIqyHm32+oHq+AsDS+QposGyLO+cWuParK8x+4zgv/PjTfPQ7z3P73E2uv34VDCz8fh7LtmhVGiydr6D8GL0DwI7fhj+Z+P5abiRfzIxmsZM2jdse/VMl1q+tkR7KYIzBu1HDaEP/zCD5iT7aS02WL1YxxmDZFsYYiuUSsiXxbtawEw6NRW/9q3Pf7N+9EEXajRuStX+ugDEI1+LWWzewEjbtSscTdsLGaFj8420qf1hAWAI76WzrYSvnbiNEp2PWlkIFsduVCaUvl71KXW5viO8rruz4/X3fO//uPaXijXvn+u8AneCQzlW9CMkAAAAASUVORK5CYII=) top left no-repeat; }
		.ogg { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,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f3d7ZKbl/zee6tEAAOAAcAAAEjsBIjsB9nkCGAKA4lqACE+AGE9AbW3lz9+Tm2ZrM+VFbQPAl09OvosHIJoCfOufnJxom5z8VguAPQDo/DDVzQEAlA4B7FtjE2gXemX80dp/78j/AGMEBZGSUTncAAA6MGlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwMTQgNzkuMTU2Nzk3LCAyMDE0LzA4LzIwLTA5OjUzOjAyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgICAgICAgICB4bWxuczpzdEV2dD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlRXZlbnQjIgogICAgICAgICAgICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICAgICAgICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8eG1wOkNyZWF0ZURhdGU+MjAxNS0xMC0wNVQxNzowOTo1MiswMTowMDwveG1wOkNyZWF0ZURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTUtMTAtMDVUMTc6MDk6NTIrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDx4bXA6TW9kaWZ5RGF0ZT4yMDE1LTEwLTA1VDE3OjA5OjUyKzAxOjAwPC94bXA6TW9kaWZ5RGF0ZT4KICAgICAgICAgPHhtcE1NOkluc3RhbmNlSUQ+eG1wLmlpZDplOTFhZGRiYy1hNmVmLTRhNWUtODM2Zi0xZDczMGRkYjY0YTk8L3htcE1NOkluc3RhbmNlSUQ+CiAgICAgICAgIDx4bXBNTTpEb2N1bWVudElEPmFkb2JlOmRvY2lkOnBob3Rvc2hvcDplZjhiYTkxYS1hYzA5LTExNzgtYjEzNC1mMWY0ZWE5MmRiYWI8L3htcE1NOkRvY3VtZW50SUQ+CiAgICAgICAgIDx4bXBNTTpPcmlnaW5hbERvY3VtZW50SUQ+eG1wLmRpZDpmNWM1MDE4My0yMmQzLTRlMDgtYjM3Yy0wNGMyYjlkZGNjZTA8L3htcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOkhpc3Rvcnk+CiAgICAgICAgICAgIDxyZGY6U2VxPgogICAgICAgICAgICAgICA8cmRmOmxpIHJkZjpwYXJzZVR5cGU9IlJlc291cmNlIj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OmFjdGlvbj5jcmVhdGVkPC9zdEV2dDphY3Rpb24+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDppbnN0YW5jZUlEPnhtcC5paWQ6ZjVjNTAxODMtMjJkMy00ZTA4LWIzN2MtMDRjMmI5ZGRjY2UwPC9zdEV2dDppbnN0YW5jZUlEPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6d2hlbj4yMDE1LTEwLTA1VDE3OjA5OjUyKzAxOjAwPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPnNhdmVkPC9zdEV2dDphY3Rpb24+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDppbnN0YW5jZUlEPnhtcC5paWQ6ZTkxYWRkYmMtYTZlZi00YTVlLTgzNmYtMWQ3MzBkZGI2NGE5PC9zdEV2dDppbnN0YW5jZUlEPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6d2hlbj4yMDE1LTEwLTA1VDE3OjA5OjUyKzAxOjAwPC9zdEV2dDp3aGVuPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6c29mdHdhcmVBZ2VudD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNCAoTWFjaW50b3NoKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgICAgPHN0RXZ0OmNoYW5nZWQ+Lzwvc3RFdnQ6Y2hhbmdlZD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3BuZzwvZGM6Zm9ybWF0PgogICAgICAgICA8cGhvdG9zaG9wOkNvbG9yTW9kZT4zPC9waG90b3Nob3A6Q29sb3JNb2RlPgogICAgICAgICA8cGhvdG9zaG9wOklDQ1Byb2ZpbGU+RGlzcGxheTwvcGhvdG9zaG9wOklDQ1Byb2ZpbGU+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgICAgIDx0aWZmOlhSZXNvbHV0aW9uPjcyMDAwMC8xMDAwMDwvdGlmZjpYUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6WVJlc29sdXRpb24+NzIwMDAwLzEwMDAwPC90aWZmOllSZXNvbHV0aW9uPgogICAgICAgICA8dGlmZjpSZXNvbHV0aW9uVW5pdD4yPC90aWZmOlJlc29sdXRpb25Vbml0PgogICAgICAgICA8ZXhpZjpDb2xvclNwYWNlPjY1NTM1PC9leGlmOkNvbG9yU3BhY2U+CiAgICAgICAgIDxleGlmOlBpeGVsWERpbWVuc2lvbj4zMjwvZXhpZjpQaXhlbFhEaW1lbnNpb24+CiAgICAgICAgIDxleGlmOlBpeGVsWURpbWVuc2lvbj4zMjwvZXhpZjpQaXhlbFlEaW1lbnNpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgIAo8P3hwYWNrZXQgZW5kPSJ3Ij8+cBpp+wAAACBjSFJNAABuJwAAc68AAPamAACJjwAAeXAAAPCEAAAv1QAAE+VcWq1dAAAGnElEQVR42rSXW2xcRxnHfzPnnN313ry7cWzHufnWxE7jpE0CaZEitaXQKqA2gFBRQfBYQRACCSFe+gK8IS4SQlAkHhBPPBEuDUhthFJCFSk0F1Dk4Dixk42zjm/r3fXu2T1zZoaHde042cQOtCMd6ZwzM2d+3/983zfzCWst97ZfDv5kPDOQzVpzf1/LJgRhXaEqwcQLb7x0rL03c0vVVMuh2d7c2qmtAE68+rvykR98IqX8uz9iAbE68a630hE0yg1Ofe1POFH338/++MWjmf7sLVVV6wLIVpRWWxXWFboe3nXpNc/h8vX+fVBusPXp7Qwf2zNy6hsnTy5eL27zEl6T8CFN8gE1ayxCCg5+/TDDx4Y3DNEawP5vEGbZZw4ef2oVYmIZ4sNW4H1wow0IOHD88P0QdqMA4v/jMNoghODA8cMMvXzP7/jQFLAW4QicZAQvGcGJubipKIe/e4R9X9w/cvo7b50s50tb753mflA+ICMOpclFLr1xbq2YrkR4knCpMfLW8Td/85Wzrz2/PsCjGq8tsfY2Bj+zh+rMEkLKNdYIKdl3/DDjvx99amMK3OsSUrBeVrRYtnx0G9KVCCkQopkhBQITamTMZf7yncbGAMTa+9BXOFEXIcXKOyEF0mkuZi3oQFMv1giWAuolnzAI0UGIMYZkV5rOoS6Mut+Ih/qAcAX1eZ/Lvz7P0Jf2E+9KYAKNCQ31xTr+QpVyvkTldonKdIV6yUc1FKqu8JIR/FINpQP2fHof3Xu3tFzq4QpYkJ7D4tUFzv/0XZw2F2stlUKZ2KY2lqYrJHpSGG3oGO6kd2CAeEeCSDKCcB0Kl6bYdmg75UIJHZpHAFimsMbiRB12vjCAP18jVJpoNkb7YI5MX5ZYLk6mL8vc6AwdQ53EsnFMaACLqgXEs3ESHUn8Rb/pE9hHAbBgm2J0Huwhko5SLVToPNADyw5pLQgBwhEoX+HFQxAgXbmSkJyIi/TkA0N73TwgXIkJDdZYjDYYpTFqVU4n6iI9B+k5WGtZWvaF8u0Ss2N3sBjacolVB96wDyyPdzyH+kKN2X9Nk921CSFEM9QciZRNy6t3lrj5jwkqhTK1uSo4Ai/uITzJ3MQlnvzCRxCO3DiANWCUaeZ03aRxYy5hTdEo1QnrIf5CjemLt5m9PE11tkpqa4qeJ7exaVcH8c1JvLYIjudgdFM9VQ1odfhpCWBCjaoEhHVF6EiimRgTfxkjbISM//kKjaUGjUqDaCZG50g3j39+P+kdGRy3uaAJDdpXhDWFEKu+oht6gwBKE5Qb6EbYHBR12P5MH8XxebTS5FIRkltStPdm8RJRbGhoFH2sfdiBxTwKgCGoLAPYpgWJzgSpntSaE1BYC1FLAQJx3xbehLErfVYbTBA+ggKVBmFNNXNBzEVVLTrQOBEHBM14txYn6mK1QQdmtS/QzVQdcTDKYLUhEhp0sFEFQkNjwcdp8xg4uotUTwqtNNPnC9w+m8daQ7wjSd8nB0l0JtCBpvDeFIVzU1htyA7k2PFsP9FUlKAakH9nktKNxZa/oGVsmMAQVBWDn9pNYnOc8TfHuHOhQO9z/WQHcqglxe7P7sGNOFz94xUW/jNH3/ODxNqjSEey+3N7qc/XGDsxSqNY57GXhhFCEPpqYwqouiLWHiPTl+W9n51l5mIBBMSybXQf6MGfrxHvSHDme3+jkl/kVsQl//dJKlMV+l98jNBXXPzVP1G1gOlzU6S2pakX/TUJ7KEAuq6RTjN+yjcWMdoS1kMqN0t0Hewhmo6hqgGlySKZ/hx7v/wEqhow+fY13JiLP1+jcqvMwNFd7HyuH1VTXDs5RqtC5QGFiaGSL4MQxHJtVG6WaBR90jvaqc/7zI/O4iUjxFJRZi4UuPzbi7TvzJDoSlIcmyPZnUI6kvzpSa7+4Qodj3fiRl1alWsP3IxmLk2TPz3JoW8+TaonTWp7ms37ujnz+immztxk5kKBj73+DGMnRkl0J/ESEfyZGhN/HWPw5WGOfP/j3Dh1nc37ugCo3q5gWkRBy9rwF70/mk9vSeekKxl6ZS/dh7YSVAPGT4wy9W4e6Ui8hMfQKyN0PtGNqgXcePs6+XduEPqK1NY0w6+O0N6XxZ+rcf3kGLMX71CeLi+8du1bm9YF+HnPD8vJXDJltEH7IcIRK6WXG3OxywdR3VAIKVdyvBvzEBK0aiYdIeXKPK/No1qsVr469e30+lHgq5lyoazu359Fi0r5LgNK9Qec6wUs+miti/eu9d8BAIS3fnNiQwjXAAAAAElFTkSuQmCC) top left no-repeat; }
		.dir { width: 32px; height: 32px; display: inline-block; background: transparent url(data:image/png;base64,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) top left no-repeat; }
		.preview_icon { width: 16px; height: 16px; display: inline-block; background: transparent url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAfwAAAH8BuLbMiQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAEFSURBVDiNldKxSgNBFIXhb4MpUogkpS+QUrGyEKzNG+QR8kABHyBFKkkTbNLYaAJiwNrKLkUawUKIjs1dXONsMAvDMpx7/rlz5kopSSlBAwNMscIrbtAva3KrNB/jDinWGm+V/QTtLCBOLs236IZQ4BTz0MZ1gEFpriloYhk1vRxgGmK39p5cRs0wB1hhvTMoWthgsa018IFmURSF+u8gsvrcFhp4xCFOdgAuItTnnNiP+83RzLR/hBd84bxuDiYBWUZgrejqKswJo12D1MbYz+Bs4sQU/xHe8YDOH0Cl3R6GWOAe12XbYU54qkJqny6TRSfMvyD/BtRB9gJkILO9ARXIDGffL2Cu91euBg0AAAAASUVORK5CYII=) top left no-repeat; }
	</style>
	<?php if ($listing->enableMultiFileUploads): ?>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script>
			$('button[name=add_file]').on('click', function(e) {
				e.preventDefault();
				$('.upload-field:last').clone().insertAfter('.upload-field:last').find('input').val('');

			});
		</script>
	<?php endif; ?>
</body>
</html>
