<?php
# src/Command/SendMailCommand.php
# php bin/console app:send-mail

namespace App\Command;

use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(name: 'app:send-mail')]
final class SendMailCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new MailtrapEmail())
            ->from(new Address('hello@example.com', 'Mailtrap Test'))
            ->to(new Address('roudayna.zini@esprit.tn'))
            ->subject('You are awesome!')
            ->category('Integration Test')
            ->text('Congrats for sending test email with Mailtrap!')
        ;

        $response = MailtrapClient::initSendingEmails(
            apiKey: 'a95ee42c23887f0ea7e6f7db1a1f6ae2',
            isSandbox: true,
            inboxId: 4240691
        )->send($email);

        $output->writeln('Email sent successfully!');
        $output->writeln(print_r(ResponseHelper::toArray($response), true));

        return Command::SUCCESS;
    }
}
