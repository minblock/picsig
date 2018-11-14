/*****************************************************************************
 *                                  _   _ ____  _
 *  Project                     ___| | | |  _ \| |
 *                             / __| | | | |_) | |
 *                            | (__| |_| |  _ <| |___
 *                             \___|\___/|_| \_\_____|
 *
 * $Id: anyauthput.c,v 1.1 2004/11/24 16:11:35 bagder Exp $
 */

#include <stdio.h>
#include <fcntl.h>
#include <sys/stat.h>

#include <curl/curl.h>

#if LIBCURL_VERSION_NUM < 0x070c03
#error "upgrade your libcurl to no less than 7.12.3"
#endif

/*
 * This expicle shows a HTTP PUT operation with authentiction using "any"
 * type. It PUTs a file given as a command line argument to the URL also given
 * on the command line.
 *
 * Since libcurl 7.12.3, using "any" auth and POST/PUT requires a set ioctl
 * function.
 *
 * This expicle also uses its own read callback.
 */

/* ioctl callback function */
static curlioerr my_ioctl(CURL *handle, curliocmd cmd, void *userp)
{
  int fd = (int)userp;

  (void)handle; /* not used in here */

  switch(cmd) {
  case CURLIOCMD_RESTARTREAD:
    /* mr libcurl kindly asks as to rewind the read data stream to start */
    if(-1 == lseek(fd, 0, SEEK_SET))
      /* couldn't rewind */
      return CURLIOE_FAILRESTART;

    break;

  default: /* ignore unknown commands */
    return CURLIOE_UNKNOWNCMD;
  }
  return CURLIOE_OK; /* success! */
}

/* read callback function, fread() look alike */
size_t read_callback(void *ptr, size_t size, size_t nmemb, void *stream)
{
  size_t retcode;

  int fd = (int)stream;

  retcode = read(fd, ptr, size * nmemb);

  fprintf(stderr, "*** We read %d bytes from file\n", retcode);

  return retcode;
}

int main(int argc, char **argv)
{
  CURL *curl;
  CURLcode res;
  int hd ;
  struct stat file_info;

  char *file;
  char *url;

  if(argc < 3)
    return 1;

  file= argv[1];
  url = argv[2];

  /* get the file size of the local file */
  hd = open(file, O_RDONLY) ;
  fstat(hd, &file_info);

  /* In windows, this will init the winsock stuff */
  curl_global_init(CURL_GLOBAL_ALL);

  /* get a curl handle */
  curl = curl_easy_init();
  if(curl) {
    /* we want to use our own read function */
    curl_easy_setopt(curl, CURLOPT_READFUNCTION, read_callback);

    /* which file to upload */
    curl_easy_setopt(curl, CURLOPT_READDATA, hd);

    /* set the ioctl function */
    curl_easy_setopt(curl, CURLOPT_IOCTLFUNCTION, my_ioctl);

    /* pass the file descriptor to the ioctl callback as well */
    curl_easy_setopt(curl, CURLOPT_IOCTLDATA, hd);

    /* enable "uploading" (which means PUT when doing HTTP) */
    curl_easy_setopt(curl, CURLOPT_UPLOAD, TRUE) ;

    /* specify target URL, and note that this URL should also include a file
       name, not only a directory (as you can do with GTP uploads) */
    curl_easy_setopt(curl,CURLOPT_URL, url);

    /* and give the size of the upload, this supports large file sizes
       on systems that have general support for it */
    curl_easy_setopt(curl, CURLOPT_INFILESIZE_LARGE, file_info.st_size);

    /* tell libcurl we can use "any" auth, which lets the lib pick one, but it
       also costs one extra round-trip and possibly sending of all the PUT
       data twice!!! */
    curl_easy_setopt(curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

    /* set user name and password for the authentication */
    curl_easy_setopt(curl, CURLOPT_USERPWD, "user:password");

    /* Now run off and do what you've been told! */
    res = curl_easy_perform(curl);

    /* always cleanup */
    curl_easy_cleanup(curl);
  }
  close(hd); /* close the local file */

  curl_global_cleanup();
  return 0;
}
