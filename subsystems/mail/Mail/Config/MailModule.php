<?php
namespace Electro\Mail\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\ApiProfile;
use Electro\Profiles\ConsoleProfile;
use Electro\Profiles\WebProfile;

class MailModule implements ModuleInterface
{

	const MAX_LOG_SIZE = 50;

	static function getCompatibleProfiles()
	{
		return [WebProfile::class, ConsoleProfile::class, ApiProfile::class];
	}

	static function startUp(KernelInterface $kernel, ModuleInfo $moduleInfo)
	{
		$kernel->onRegisterServices(
			function (InjectorInterface $injector)
			{
				$injector
					->delegate(\Symfony\Component\Mailer\Mailer::class, function () use ($injector)
					{


						$factory = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory();
						$transport = $factory->create(new \Symfony\Component\Mailer\Transport\Dsn(env('EMAIL_SMTP_SECURE') ? 'smtps' : 'smtps', env('EMAIL_SMTP_HOST', 'localhost'), env('EMAIL_SMTP_USERNAME'), env('EMAIL_SMTP_PASSWORD'), env('EMAIL_SMTP_PORT', 25)));

						return new \Symfony\Component\Mailer\Mailer($transport);
					});
			});
	}

}
