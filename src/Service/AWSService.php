<?php

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AWSService
{
    private S3Client $s3Client;
    private string $awsS3Bucket;

    public function __construct(
        string $awsAccessKey,
        string $awsSecretKey,
        string $awsRegion,
        string $awsS3Bucket
    ) {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $awsRegion,
            'credentials' => [
                'key' => $awsAccessKey,
                'secret' => $awsSecretKey,
            ],
        ]);
        $this->awsS3Bucket = $awsS3Bucket;
    }

    public function saveFileToS3(UploadedFile $file, string $directory): ?string
    {
        $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = '/' . $directory . '/' . $fileName;

        $result = $this->s3Client->putObject([
            'Bucket' => $this->awsS3Bucket,
            'Key' => $filePath,
            'Body' => fopen($file->getPathname(), 'rb'),
            'ACL' => 'public-read',
        ]);

        return $result->hasKey('ObjectURL') ? $result->get('ObjectURL') : null;
    }


}
