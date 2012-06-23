lastfm-to-xbmc
==============

Transfer play counts from your Last.FM account to your XBMC database.

This is currently a work in progress and doesn't yet work!

Getting Started
===============

Windows
=======

Get an API Key from Last FM http://www.last.fm/api/account (the secret isn't needed).

Download and install XAMPP (these instructions assume you installed to c:\xampp) - No need to install anything as a service

Edit c:\xampp\php\php.ini - 
	remove the ";" in front of "extension=php_curl.dll"
	Change "date.timezone" to be the correct timezone - e.g. "Europe/London" for England.

Download this repository from github and extract to somewhere easy to remember (e.g. c:\lastfm2xbmc\)

Start > Run > Cmd [ENTER]
c:\xampp\php\php.exe -c c:\xampp\php\php.ini -f c:\lastfm-to-xbmc\last_fm_to_xbmc.php 
(or if you placed in c:\lastfm-to-xbmc\ double click runme.bat)

