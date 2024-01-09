<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;

class SendNewsletterCommand extends Command
{
    protected static $defaultName = 'app:send-newsletter';
    protected static string $defaultDescription = 'Send newsletter to active users created in the last week';

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
        $weekAgo = new \DateTime('-1 week');
        $users = $this->userRepository->findActiveUsersCreatedSince($weekAgo);

        if (count($users) === 0) {
            $output->writeln('No active users created in the last week.');
            return 0;
        }

        foreach ($users as $user) {
            $this->sendEmail($user->getEmail(), $user->getFullName());
        }

        $output->writeln('Newsletter sent successfully.');

        return 0;
    }

    private function sendEmail(string $recipientEmail, string $recipientName): void
    {
        $senderName = 'Cobbleweb';
        $subject = 'Your best newsletter';
        $message = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id interdum nibh. Phasellus blandit tortor in cursus convallis. Praesent et tellus fermentum, pellentesque lectus at, tincidunt risus. Quisque in nisl malesuada, aliquet nibh at, molestie libero.';

        $email = (new TemplatedEmail())
            ->from($this->parameterBag->get('MAILER_FROM'))
            ->to($recipientEmail)
            ->subject($subject)
            ->htmlTemplate('emails/newsletter.html.twig')
            ->context([
                'message' => $message,
            ]);

        $this->mailer->send($email);
    }
}
