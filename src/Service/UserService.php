<?php

namespace App\Service;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    private $entityManager;
    private $passwordEncoder;
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
    }

    public function registerUser(array $data): array
    {
        $user = new User();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);

        $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));

        $this->generateFullName($user);
        $this->handleAvatar($user, $data['avatar']);

        // Handle user photos
        $photos = $data['photos'] ?? [];
        $this->handlePhotos($user, $photos);

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return ['success' => false, 'errors' => $errorMessages];
        }

        $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));

        // Set other user properties as needed

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return ['success' => true, 'message' => 'User registered successfully'];
    }

    private function generateFullName(User $user): void
    {
        $user->setFullName($user->getFirstName() . ' ' . $user->getLastName());
    }

    private function handleAvatar(User $user, ?string $avatar): void
    {
        if ($avatar) {
            $avatarPath = $this->saveAvatar($avatar);
            $user->setAvatar($avatarPath);
        } else {
            $defaultAvatarPath = '/uploads/avatars/default.png';
            $user->setAvatar($defaultAvatarPath);
        }
    }

    private function handlePhotos(User $user, array $photos): void
    {
        if (count($photos) < 4) {
            throw new \InvalidArgumentException('At least 4 photos should be uploaded.');
        }

        foreach ($photos as $photo) {
            $this->handlePhoto($user, $photo);
        }
    }

    private function handlePhoto(User $user, UploadedFile $photo): void
    {
        $photoEntity = new Photo();
        $photoEntity->setName($photo->getClientOriginalName());
        $photoEntity->setUrl($this->savePhoto($photo));
        $photoEntity->setUser($user);

        $this->entityManager->persist($photoEntity);
    }

    private function savePhoto(UploadedFile $photo): string
    {
        $uploadDirectory = 'uploads/photos';
        $photo->move($uploadDirectory, $photo->getClientOriginalName());

        return '/' . $uploadDirectory . '/' . $photo->getClientOriginalName();
    }

    private function saveAvatar(string $avatar): string
    {
        $uploadDirectory = 'uploads/avatars';
        $avatarFileName = 'avatar_' . uniqid() . '.png';
        file_put_contents($uploadDirectory . '/' . $avatarFileName, base64_decode($avatar));

        return '/' . $uploadDirectory . '/' . $avatarFileName;
    }
}