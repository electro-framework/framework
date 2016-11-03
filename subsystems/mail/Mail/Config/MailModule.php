<?php
namespace Electro\Mail\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Swift_Mailer;
use Swift_Plugins_LoggerPlugin;
use Swift_Plugins_Loggers_ArrayLogger;

class MailModule implements ModuleInterface
{
  const MAX_LOG_SIZE = 50;

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
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

            $mailer = new Swift_Mailer ($transport);
            $logger = new Swift_Plugins_Loggers_ArrayLogger (self::MAX_LOG_SIZE);
            $mailer->registerPlugin (new Swift_Plugins_LoggerPlugin ($logger));
            // Create run-time custom property to allow easy access to the logger.
            $mailer->logger = $logger;
            return $mailer;
          });
      });
  }

}
