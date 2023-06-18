<?php

declare(strict_types=1);

namespace edydeyemi\S3Test;

if (session_id() == '') {
    session_start();
}

define('ROOT', dirname(__FILE__, 1));
include ROOT . '/vendor/autoload.php';

use Exception;
use Throwable;
use kwiq\Helper;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(ROOT . '/.env');

class FileHandler
{
    private $region;

    private $key;
    private $secret;
    private $bucket;
    private $sdk;
    private $client;
    private $options;

    private $filename;
    private $path = ROOT;
    public $msg;

    public function __construct()
    {
        if (!is_dir($this->path . '/tmp/')) {
            mkdir($this->path . '/tmp/', 0755, true);
        }

        $this->region = $_ENV['AWS_REGION'];
        $this->key = $_ENV['AWS_KEY'];
        $this->secret = $_ENV['AWS_SECRET'];
        $this->bucket = $_ENV['AWS_BUCKET'];

        $this->client = $this->setClient();
    }

    private function setOptions()
    {
        return $this->options = [
            'region'            => $this->region,
            'version'           => 'latest',
            'signature_version' => 'v4',
            'credentials' => [
                'key'    => $this->key,
                'secret' => $this->secret,
            ],
        ];
    }

    private function setClient()
    {
        $this->sdk = new \Aws\Sdk($this->setOptions());
        return $this->sdk->createS3();
    }


    /**
     * Retrieves file from form POST and uploads to a local folder
     * $item - Name of field holding file data - derived from field name in submitted form 
     * 
     * @param   string     $item        Form field that contains the file 
     */
    private function uploadFile(string $item): array
    {
        if (empty($_FILES["$item"])) {
            return ["status" => "FAILED", "msg" => "No file found"];
        }

        $file_name = $_FILES[$item]['name'];
        $file_size = $_FILES[$item]['size'];
        $file_tmp = $_FILES[$item]['tmp_name'];
        $ext = (explode('.', $file_name));
        $file_ext = strtolower($ext[1]);

        // generate random name for file
        $file_name = uniqid() . "." . $file_ext;
        try {
            move_uploaded_file($file_tmp, $this->path . $file_name);
            return ["status" => "SUCCESS", "msg" => "File uploaded successfully", "path" => $this->path, "file_name" => $file_name];
        } catch (Exception $e) {
            return ["status" => "FAILED", "msg" => "An error occured. Please try again.", "response" => $e];
        };
    }

    /**
     * uploadBucket
     * 
     * Uploads file to AWS bucket
     *
     * @param string $item
     * @return array
     */
    public function uploadBucket(string $item): array
    {
        $fileupload = $this->uploadFile($item);
        if ($fileupload['status'] != "SUCCESS") {
            return $fileupload;
        }

        if ($fileupload['status'] == "SUCCESS") {
            $file =  $fileupload['path'] . "" . $fileupload['file_name'];

            try {
                $this->client->putObject([
                    'Bucket' => $this->bucket,
                    'Key' => basename($file),
                    'SourceFile' => $file,
                ]);
                return $fileupload;
            } catch (S3Exception $e) {
                echo $e->getMessage() . "\n";
                unlink($file);
                return ["status" => "FAILED", "msg" => "An error occured. Please try again.", "response" => $e->getMessage()];
            }
        } else {
            return $fileupload;
        }
    }

    public function deleteFileS3($file)
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $file
            ]);
        } catch (S3Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    private function downloadBucket($file)
    {
        try {
            $this->client->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => basename($this->path . $file),
                'SaveAs' => $this->path . $file
            ));
            echo "file downloaded";
            return true;
        } catch (S3Exception $e) {
            echo $e;
            unlink($this->path . $file);
            return false;
        }
    }

    public function downloadFile(string $filename)
    {

        $file = $this->path . $filename;

        if ($this->downloadBucket($filename)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            unlink($file);
        } else {
            echo "File not found";
        }
        exit;
    }
}
