<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:test-email')]
class TestEmailCommand extends Command
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Tentative d\'envoi d\'email de test...');

        try {
            $email = (new Email())
                ->from('troudi111salim@gmail.com')
                ->to('salimtroudi17@gmail.com
') // Utilisez votre email pour le test
                ->subject('Test d\'envoi d\'email depuis Symfony')
                ->text('Si vous recevez cet email, le système fonctionne correctement.')
                ->html('<p>Si vous recevez cet email, le système fonctionne correctement.</p>');

            $this->mailer->send($email);
            $output->writeln('<info>Email envoyé avec succès!</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur lors de l\'envoi de l\'email: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}