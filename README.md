# MassFetcher

MassFetcher is a multithreaded HTTP GET request utility. Give it a path to 
request, and a giant list of domains to request it from. Retrieved files are 
saved to disk (subject to configuration parameters). You may find MassFetcher 
useful if you want to perform various types of web analysis:

* Gauge the average size of web index pages

* Determine the popularity of specific code libraries, meta tags, etc. 

* Inspect lots of `ads.txt` files looking for new ad networks to block

* Find out how quickly (or not) a proposal like `./well-known/security.txt` is 
being implemented

MassFetcher will go get the data; doing something with it is up to you.

## Requirements

* PHP >= 7.1, with

* The `pthreads` extension, either compiled-in or enabled as a module, and

* The `curl` extension, either compiled-in or enabled as a module

* Composer

## Installation

Clone this repository to a new directory and then run `composer install`. This 
will pull in the dependency (a logger) and set up the autoloader.

Copy `config.php-dist` to `config.php`.

## Usage

Configure your settings inside `config.php`. Here you can set the target URI 
path you want to request, along with a bunch of options to modify MassFetcher's 
behavior. The options are explained in the comments.

Supply your list of target hosts in a file called `domains.txt`. The 
[Alexa Top 1M list](http://s3.amazonaws.com/alexa-static/top-1m.csv.zip) may 
come in handy, but do some small test runs first!

Run `php fetcher.php` to execute MassFetcher.

Retrieved files will be saved to a directory (defaults to `data`) in a series of 
hierarchical subdirectories.

The repository ships with a sample `domains.txt` containing 100 hostnames, a 
a config that will request `/ads.txt` from all of them, and the logger set to 
debug level. You should probably run once using these defaults, then examine 
the `output.log` file to see what's going on under the hood.

## Resources and Performance

Performance will vary depending upon your hardware, internet connection, and 
configuration settings. Broadly speaking, with 64 threads I've averaged around 
1,000 requests per minute from various commodity cloud instances.

MassFetcher may use significantly more bandwidth and disk space than you expect. 
Due to error pages, redirects, and oddly-configured servers, you're going to get 
plenty of junk data. 

For instance, suppose you request `/ads.txt`:

* telegram.org replies with "200 OK" but sends their index page instead.

* booking.com properly sends a 404 response, but it weighs in at a hefty 300KB.

* whatsapp.com redirects to its 600KB index page.

Some of MassFetcher's settings can help mitigate junk data. In particular, the 
strict filename matching option will only write a fetched file to disk if the 
final destination URI, after all redirects, has the same base filename that you 
requested.

You should do some small test runs whenever you change configuration, before 
launching into an enormous fetch job. 
