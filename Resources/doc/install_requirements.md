Installing the requirements
===========================

>These instructions are for Ubuntu.

### Php gd extension
```
sudo apt-get install php5-gd
```

### 1) ImageMagick
```
sudo apt-get install imagemagick
```

### 2) avconv
```
sudo apt-get install libav-tools
```

For h264 mp4 video encoding:
```
sudo apt-get install libavcodec-extra-53
```

### 3) PHP PECL MailParse

The MailParse extension is can be downloaded from here: [http://pecl.php.net/package/mailparse](http://pecl.php.net/package/mailparse)

To install mailparse you'll need the php-devel package.
```
sudo apt-get install php5-dev
```
This will give you the commands that allow compiling PHP PECL extensions.

Then follow the directions on [installing a PHP PECL extension through the pecl command](http://php.net/manual/en/install.pecl.phpize.php).

Basically you want to download the latest version of mailparse:

eg:
```
wget http://pecl.php.net/get/mailparse-2.1.6.tgz
```
2.1.6 is the latest stable as of this writing. You'll need the latest version from: [http://pecl.php.net/package/mailparse](http://pecl.php.net/package/mailparse)

Extract the archive:
```
tar -xzvf mailparse-2.1.6.tgz
```
change to the directory you just decompressed:
```
cd mailparse-2.1.5
```
Then build and install it through:
```
phpize
./configure
make
sudo make install
```
Add to php.ini with a new file: ``/etc/php5/conf.d/mailparse.ini``
The contents of which are:
```
; configuration for mailparse module
extension=mailparse.so
```

### 4) Install & configure postfix to receive the emails

See [https://help.ubuntu.com/community/PostfixBasicSetupHowto](https://help.ubuntu.com/community/PostfixBasicSetupHowto)

Add a .forward file to the user that will receive the emails that forwards to the app:
```
| "php /var/www/app/console cctv:inbox"
```


