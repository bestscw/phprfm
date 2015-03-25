<?php
class SFTPConnection
{
    private $connection;
    private $sftp;

    public function __construct($host, $port=22)
    {
        $this->connection = @ssh2_connect($host, $port);
        if (! $this->connection)
            throw new Exception("Could not connect to $host on port $port.");
    }

    public function login($username, $password)
    {
        if (! @ssh2_auth_password($this->connection, $username, $password))
            throw new Exception("Could not authenticate with username $username " .
                                "and password $password.");

        $this->sftp = @ssh2_sftp($this->connection);
        if (! $this->sftp)
            throw new Exception("Could not initialize SFTP subsystem.");
    }
    public function scpFile($local_file,$remote_file)
    {
		$ret = ssh2_scp_send($this->connection, $local_file, $remote_file, 0777);
		if (!$ret)
				throw new Exception("Could not send data from file: $local_file.");
    }

	public function receiveFile2($remote_file, $local_file)
    {
        $sftp = $this->sftp;
        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'r');
        if (! $stream)
            throw new Exception("Could not open file: $remote_file");
        $size = $this->getFileSize($remote_file);            
        $contents = '';
        $read = 0;
        $len = $size;
        while ($read < $len && ($buf = fread($stream, $len - $read))) {
          $read += strlen($buf);
          $contents .= $buf;
        }        
        file_put_contents ($local_file, $contents);
        @fclose($stream);
    }
	public function receiveFile($remote_file, $local_file)
    {
        $sftp = $this->sftp;
        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'rb');
        if (! $stream)
            throw new Exception("Could not open file: $remote_file");
		$handle = fopen($local_file,"a");

		$beginTime = time();
		while (!feof($stream)) {
            if (fwrite($handle, fread($stream, 8192)) === FALSE) {
                   // 'Download error: Cannot write to file ('.$file_target.')';
                   return true;
               }
        }
		$costTime = time()-$beginTime;
		@fclose($handle);
        @fclose($stream);
		return true;
    }

    public function getFileSize($file){
      $sftp = $this->sftp;
        return filesize("ssh2.sftp://$sftp$file");
    }

	public function remoteCommand($command)
	{
		$stream = ssh2_exec($this->connection, $command);
  		stream_set_blocking($stream, true);
  
  		// The command may not finish properly if the stream is not read to end
  		$output = stream_get_contents($stream);
		return $output;
	}
	
	public function remoteCreateDir($remote_dir)
	{
		return ssh2_sftp_mkdir($this->sftp, $remote_dir,0777,true);
	}

    public function uploadFile($local_file, $remote_file)
    {
		$remotePath = dirname($remote_file);
		$command = "if [ ! -d \"$remotePath\"]; then mkdir \"$remotePath\" fi";
		//$this->remoteCommand($command);
		
		if(!$this->remoteCreateDir(dirname($remote_file)))
		{
            	//	throw new Exception("Could not create dir: ".dirname($remote_file));
		}
		

        $sftp = $this->sftp;
        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'w');

        if (! $stream)
            throw new Exception("Could not open file: $remote_file");

        $data_to_send = @file_get_contents($local_file);
        if ($data_to_send === false)
            throw new Exception("Could not open local file: $local_file.");

        if (@fwrite($stream, $data_to_send) === false)
            throw new Exception("Could not send data from file: $local_file.");

        @fclose($stream);
    }
}
?>
