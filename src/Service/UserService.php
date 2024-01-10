<?php

namespace App\Service;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserPasswordEncoderInterface $passwordEncoder;
    private ValidatorInterface $validator;
    private AWSService $awsService;
    private string $AWS_FLAG;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        ValidatorInterface $validator,
        string $AWS_FLAG,
        AWSService $awsService
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
        $this->awsService = $awsService;
        $this->AWS_FLAG = $AWS_FLAG;
    }

    /**
     * Register a new user.
     *
     * @param array $data User data including first name, last name, email, password, avatar, and photos.
     *
     * @return array Registration result, including success status and messages.
     */
    public function registerUser(array $data): array
    {
        // Create a new User entity
        $user = new User();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);

        $errorMessages = [];

        $user->setPassword($data['password']);

        // Generate full name for the user
        $this->generateFullName($user);
        $this->handleAvatar($user, $data['avatar']);

        $errors = $this->validator->validate($user);

        $errorMessages[] = $this->handlePhotos($user, $data['photos']);

        // Set and hash the user's password
        $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));

        // If there are validation errors, return an error response
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return ['success' => false, 'errors' => $errorMessages];
        }

        $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return ['success' => true, 'message' => 'User registered successfully'];
    }

    private function generateFullName(User $user): void
    {
        $user->setFullName($user->getFirstName() . ' ' . $user->getLastName());
    }

    private function handlePhotos(User $user, array $photosBase64): ?string
    {
        if (count($photosBase64) < 4) {
            return 'At least 4 photos should be uploaded.';
        }

        foreach ($photosBase64 as $photoBase64) {
            $photoFile = $this->base64ToUploadedFile($photoBase64);
            $this->handlePhoto($user, $photoFile);
        }
        return null;
    }

    private function handlePhoto(User $user, UploadedFile $photo): void
    {
        $photoEntity = new Photo();
        $photoEntity->setName($photo->getClientOriginalName());
        if ($this->AWS_FLAG) {
            $photoPath = $this->awsService->saveFileToS3($photo, 'photos');
        } else {
            $photoPath = $this->saveFile($photo, 'uploads/photos');
        }
        $photoEntity->setUrl($photoPath);
        $photoEntity->setUser($user);

        $this->entityManager->persist($photoEntity);
    }

    private function handleAvatar(User $user, ?string $avatarBase64): void
    {
        if ($avatarBase64) {
            $avatarFile = $this->base64ToUploadedFile($avatarBase64);
            $avatarPath = $this->saveFile($avatarFile, 'uploads/avatars');
            $user->setAvatar($avatarPath);
        } else {
            $defaultAvatarPath = '/uploads/avatars/default.png';
            $user->setAvatar($defaultAvatarPath);
        }
    }

    /**
     * Handle the conversion of a base64-encoded string to an UploadedFile instance.
     *
     * @param string $base64 Base64-encoded string representing the file.
     *
     * @return UploadedFile The converted UploadedFile instance.
     */
    private function base64ToUploadedFile(string $base64): UploadedFile
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'base64tofile');
        file_put_contents($tempFilePath, base64_decode($base64));

        return new UploadedFile($tempFilePath, 'uploaded_image.png', null, null, true);
    }

    /**
     * Save the uploaded file to the specified directory and return its path.
     *
     * @param UploadedFile $file      The uploaded file instance.
     * @param string       $directory The target directory.
     *
     * @return string The file path.
     */
    private function saveFile(UploadedFile $file, string $directory): string
    {
        $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);

        return '/' . $directory . '/' . $fileName;
    }
}
