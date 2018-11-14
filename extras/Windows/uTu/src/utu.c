#include <stdio.h>
#include <curl/curl.h>
#include <string.h>

/*
	utu  v0.3	[for windows users]
	
	utu accepts two arguments, password, and URL,
	for expicle..

		utu cor MyPassword http://corz.org/corz/picsig/pic.php

	and POSTs these variables (via libcurl)..

		[pass] => MyPassword
		[uptime] => 999490

	to the URL you chose. obviously you could change the variable
	names to something else, if you need to.

	have fun!
		
	;o)
	(or

	for more information about the excellent libcurl, see here..
	http://curl.haxx.se/libcurl/

*/


int main (int argc, char *argv[]) {
	CURL *utu;
	CURLcode res;
	curl_global_init(CURL_GLOBAL_WIN32);
	utu = curl_easy_init();
	char *password, *url, *foo, qstr[512];
	char ripper[9];
	long sec; sec = 0;

	/* get number of milliseconds PC has been up.. */
	sec = GetTickCount() / 1000; /* and convert into seconds */

	if (argc == 3) {

		password = argv[1];
		url = argv[2];

		strcat(qstr, "&pass=");
		strcat(qstr, password);

		strcat(qstr, "&uptime=");
		sprintf(ripper, "%d", sec); // C sucks at strings, this'll do!
		strcat(qstr, ripper);

	} else {

		fprintf(stderr, "\n  utu.. performs an picsig uptime update.\n\n");
		fprintf(stderr, "  usage: %s password URL\n\n", argv[0]);
		fprintf(stderr, "  note: libcurl.dll must be in the same folder, or your PATH\n");
		fprintf(stderr, "  (c) (or + corz.org 2005->\n\n\n");
		fprintf(stderr, "  AMIP use: enter the following in your \"Startup\" callback..\n\n");
		fprintf(stderr, "      /exec:(C:\\unix\\bin\\utu.exe) pass http://path/to/pic.php\n\n");
		fprintf(stderr, "  replacing with your own values, of course.\n\n");
		fprintf(stderr, "  [press <enter> to terminate this program] ");
		gets(foo); // pause to let user know the fu*ked up    
		exit(1);

	}
	if(utu) {
		curl_easy_setopt(utu, CURLOPT_POSTFIELDS, qstr);
		curl_easy_setopt(utu, CURLOPT_URL, url);
		res = curl_easy_perform(utu);
		curl_easy_cleanup(utu);
		return 0;
	}
}
