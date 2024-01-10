<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class SendNewsletterCommand extends Command
{
    // Command name and description
    protected static $defaultName = 'app:send-newsletter';
    protected static string $defaultDescription = 'Send newsletter to active users created in the last week';

    // Dependencies
    private UserRepository $userRepository;
    private MailerInterface $mailer;
    private ParameterBagInterface $parameterBag;

    public function __construct(UserRepository $userRepository, MailerInterface $mailer, ParameterBagInterface $parameterBag)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->parameterBag = $parameterBag;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Find active users created in the last week
        $weekAgo = new \DateTime('-1 week');
        $users = $this->userRepository->findActiveUsersCreatedSince($weekAgo);

        // If no active users found, display a message and exit
        if (count($users) === 0) {
            $output->writeln('No active users created in the last week.');
            return 0;
        }

        // Send newsletter to each active user
        foreach ($users as $user) {
            $this->sendEmail($user->getEmail(), $user->getFullName());
        }

        // Display success message
        $output->writeln('Newsletter sent successfully.');

        return 0;
    }

    // Helper method to send an email to a user
    private function sendEmail(string $recipientEmail, string $recipientName): void
    {
        // Email details
        $senderName = 'Cobbleweb';
        $subject = 'Your best newsletter';
        $message = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id interdum nibh. Phasellus blandit tortor in cursus convallis. Praesent et tellus fermentum, pellentesque lectus at, tincidunt risus. Quisque in nisl malesuada, aliquet nibh at, molestie libero.';

        // Create a templated email
        $email = (new TemplatedEmail())
            ->from(new Address($this->parameterBag->get('MAILER_FROM'), $senderName))
            ->to($recipientEmail)
            ->subject($subject)
            ->htmlTemplate('emails/newsletter.html.twig')
            ->context([
                'message' => $message,
            ]);

        // Send the email
        $this->mailer->send($email);
    }
}
