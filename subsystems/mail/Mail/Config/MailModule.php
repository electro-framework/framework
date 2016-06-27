<?php
namespace Electro\Mail\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Swift_Mailer;

class MailModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->delegate (Swift_Mailer::class, function () use ($injector) {
        $transport = new \Swift_SmtpTransport(
          env ('EMAIL_SMTP_HOST', 'localhost'),
          env ('EMAIL_SMTP_PORT', 25)
        );
        if (env ('EMAIL_SMTP_AUTH') == 'true')
          $transport
            ->setUsername (env ('EMAIL_SMTP_USERNAME'))
            ->setPassword (env ('EMAIL_SMTP_PASSWORD'));

        return new Swift_Mailer ($transport);
      });
  }

}
