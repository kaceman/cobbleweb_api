<?php

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AWSService
{
    private S3Client $s3Client;
    private string $awsS3Bucket;

    /**
     * AWSService constructor.
     *
     * @param string $awsAccessKey AWS access key.
     * @param string $awsSecretKey AWS secret key.
     * @param string $awsRegion    AWS region.
     * @param string $awsS3Bucket  AWS S3 bucket name.
     */
    public function __construct(
        string $awsAccessKey,
        string $awsSecretKey,
        string $awsRegion,
        string $awsS3Bucket
    ) {
        // Initialize the S3 client with provided AWS credentials and region
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

    /**
     * Save an uploaded file to AWS S3.
     *
     * @param UploadedFile $file      The uploaded file instance.
     * @param string       $directory The target directory within the S3 bucket.
     *
     * @return string|null The S3 object URL if successful, otherwise null.
     */
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
