# Evoluted-Directory-Listing-Script v5

The PHP Directory Listing Script is a highly configurable script, allowing you to simply upload one file into a web-accessible directory, and it’ll be turned into a well formatted, mobile friendly directory browser.

## Features ##

* Full mobile browser support.
* The ability to upload multiple files and restrict the allowed file-types.
* Support for restricting access to the script by either password or IP Address whitelisting (ideal if you want only yourself and clients to have access!).
* Support for creating new directories and sub-directories.
* Upload zip files and extract them automatically, with the option to delete the zip file after it’s been extracted.
* Optionally hide certain file types, names or extensions, as well as directories.
* Sort file listings by name, size or last modified date.

All of the features can be enabled and disabled individually, so whether you’re looking for a full file manager, or a simple list of downloads, the PHP Directory Listing script has you covered.

## System Requirements ##

You will need to be running at least PHP 7.1 (tested up through 8.2) and GD2 library is still required. Optionally, if you wish to enable the unzip support, you’ll also need the ZipArchive php extension installed.

### Notes ###
* I have made a few minor improvements:
  * The file listing sorting was changed to a more human-friendly order over the machine order php provides by default.
  * The filter was fixed to work in subdirectories as well now.
  * Embedded icons were added for .7z and .mkv files. 
  * Some small CSS changes to better handle long file names.
* I am not the author of this script, this is a mirror of a very useful script that is difficult to find elsewhere on the web.
