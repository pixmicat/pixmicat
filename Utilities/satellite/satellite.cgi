#!/usr/bin/perl -w

##
# Pixmicat! PIO 公用程式 - FileIO Satellite Perl
#
# 利用此一放置於外部空間的衛星程式，可以讓 FileIO 利用外部空間存放圖檔。
#
# @package PMCUtility
# @version $Id$
# @date $Date$
#

require './cgi-lib.pl';

$cgi_lib'maxdata = 2097152; # 上傳檔案大小上限
$TRANSPORT_KEY = '12345678'; # 傳輸認證金鑰
$USER_AGENT = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0'; # Just for fun ;-)
$STORAGE_DIRECTORY = 'src/'; # 圖檔儲存目錄

&ReadParse;
$EOL = "\015\012";
$BLANK = $EOL x 2;

sub parse_url {
    local($url) = @_;

	$ftp_port = 21;
	$http_port = 80;
	$gopher_port = 70;
	$telnet_port = 23;
	$wais_port = 210;
	$news_port = 119;

    if ($url =~ m#^(\w+):#) {
	$protocol = $1;
	$protocol =~ tr/A-Z/a-z/;
    } else {
	return undef;
    }

# URL of type: http://host[:port]/path[?search-string]

    if ($protocol eq "http") {
		if ($url =~ m#^\s*\w+://([\w-\.]+):?(\d*)([^ \t]*)$#) {
		    $server = $1;
		    $server =~ tr/A-Z/a-z/;
		    $port = ($2 ne "" ? $2 : $http_port);
		    $path = ( $3 ? $3 : '/');
		    return { protocol => $protocol, host => $server, port => $port, path => $path }; # Return by reference
		}
	return undef;
    }

}

if ($ENV{'REQUEST_METHOD'} eq 'POST') {
	$mode = defined($in{'mode'}) ? $in{'mode'} : ''; # 要求模式
	$Tkey = defined($in{'key'}) ? $in{'key'} : ''; # 對方送來傳輸金鑰
	$imgname = defined($in{'imgname'}) ? $in{'imgname'} : ''; # 圖檔名稱
}

if ($mode eq 'init') { # 初始化
	DoConstruct() ? DoOK() : DoError();
} elsif ($mode eq 'transload') { # 遠端抓取
	DoTransload($imgname) ? DoOK() : DoError();
} elsif ($mode eq 'upload') { # 上傳檔案
	DoUpload($imgname) ? DoOK() : DoError();
} elsif ($mode eq 'delete') { # 刪除檔案
	DoDelete($imgname) ? DoOK() : DoError();
} else {
	DoNotFound();
}

### 初始化
sub DoConstruct{
	return undef if $Tkey != $TRANSPORT_KEY; # 金鑰不符

	if(! -d $STORAGE_DIRECTORY){ mkdir($STORAGE_DIRECTORY); chmod($STORAGE_DIRECTORY, 0777); }
	return 1;
}

### 進行遠端抓取檔案並儲存
sub DoTransload{
	use Socket;
	my $imgname=$_[0];
	$imgurl = defined($in{'imgurl'}) ? parse_url($in{'imgurl'}) : undef; # 圖檔遠端URL位置
	if(! -d $STORAGE_DIRECTORY){ DoConstruct(); }

	my ($remote,$port,$doc) = ($$imgurl{'host'},$$imgurl{'port'},$$imgurl{'path'}); # $$var = dereference
	$sockaddr = 'S n a4 x8';

	if ($port =~ /\D/) { $port = getservbyname($port, 'tcp'); }
	return undef unless $port;
	$thataddr = gethostbyname($remote);
	$that = pack($sockaddr, AF_INET, $port, $thataddr);
	$proto = (getprotobyname('tcp'))[2] || 6;
	socket(SOCK, PF_INET, SOCK_STREAM, $proto) || return undef;
	connect(SOCK, $that) || return undef;
	binmode(SOCK);
	$ofh = select(SOCK); $| = 1; select($ofh); # flush buffer on every write

	print SOCK "GET $doc HTTP/1.1".$EOL.
	           "Host: $remote".$EOL.
	           "User-Agent: $USER_AGENT".$BLANK;

	vec($rin='', fileno(SOCK), 1) = 1;
	select($rin, undef, undef, 20) || return undef; # no response from server

	while( <SOCK> ) {
		s/\r\n/\n/g;
		s/\r/\n/g;
		if ( /HTTP([\/\.\d]+)\s+(\d+)\s+(.*)\n/i ) { $status         = $2; }
		if ( /Content-Length: (\s*)(\d+)\n/i )     { $content_length = $2; }
		last if $_ =~ /^$/;
	}

	$content='';
	if ($content_length) {
		read(SOCK, $content, $content_length);
	} else {
		while ( <SOCK> ) { $content .= $_; }
	}
	close(SOCK);
	select($ofh);

	return undef if $status ne "200"; # 檔案不存在或伺服器出現問題

	open(FS,">$STORAGE_DIRECTORY$imgname") || return undef;
	binmode(FS);
	print FS $content;
	chmod($STORAGE_DIRECTORY.$imgname, 0666);
	close(FS);

	return 1;
}

### 接受上傳檔案並儲存
sub DoUpload{
	my $imgname=$_[0];
	$imgfile = defined($in{'imgfile'}) ? $in{'imgfile'} : undef;
	if(!$imgfile){ return undef; }
	if(! -d $STORAGE_DIRECTORY){ DoConstruct(); }

	open(FS,">$STORAGE_DIRECTORY$imgname") || return undef;
	binmode(FS);
	print FS $imgfile;
	chmod($STORAGE_DIRECTORY.$imgname, 0666);
	close(FS);

	return 1;
}

### 刪除檔案
sub DoDelete{
	my $imgname=$_[0];
	return undef if $Tkey != $TRANSPORT_KEY; # 金鑰不符

	return unlink($STORAGE_DIRECTORY.$imgname);
}

### 阻止閒雜人士進入
sub DoNotFound{
	print "Status: 404 Not Found".$EOL.
	      "Content-type: text/html".$BLANK.
"<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n".
"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n".
"         \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n".
"<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n".
" <head>\n".
"  <title>404 - Not Found</title>\n".
" </head>\n".
" <body>\n".
"  <h1>404 - Not Found</h1>\n".
" </body>\n".
"</html>";
}

### 操作成功，回傳成功訊息
sub DoOK{
	print "Status: 202 Accepted".$EOL.
	      "Content-type: text/plain".$BLANK.
	      "Succeed.";

}

### 操作失敗，回傳錯誤訊息
sub DoError{
	print "Status: 403 Forbidden".$EOL.
	      "Content-type: text/plain".$BLANK.
	      "Failed.";
}

__END__
