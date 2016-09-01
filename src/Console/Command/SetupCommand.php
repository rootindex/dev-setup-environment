<?php
/**
 * Copyright (c) 2016 Francois Raubenheimer.
 */

namespace FR\Console\Command;

use Apache\Config\Directive;
use Apache\Config\VirtualHost;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetupCommand
 * @package FR\Console\Command
 */
class SetupCommand extends Command
{
    const HTTP_PORT = 8080;
    const HTTPS_PORT = 8443;
    const HOSTING_PATH = '/Users/fran/Sites';
    const LOG_PATH = '/usr/local/var/log/apache2';
    const SSL_PATH = '/Users/fran/Sites/ssl/intermediate';
    const VHOST_PATH = '/Users/fran/Sites/conf';

    /**
     * @var array
     */
    protected $domains = ['dev', 'm1', 'm2'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Setup VHost')
            ->addArgument('domain', InputArgument::REQUIRED, 'Provide domain name');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var string $answer */
        $answer = $input->getArgument('domain');

        /** @var array $domainArr */
        $domainArr = explode('.', $answer);



        if (count($domainArr) !== 2 || !in_array($domainSuffix = array_pop($domainArr), $this->domains)) {
            throw new \RuntimeException(
                'Please provide a valid domain name in the range: ' . implode(', ', $this->domains)
            );
        }
        $domainPrefix = array_shift($domainArr);

        $virtualHost = new VirtualHost('*', self::HTTP_PORT);
        $virtualHost->addDirective(new Directive('ServerName', $answer));
        $virtualHost->addDirective(
            new Directive('DocumentRoot', rtrim(self::HOSTING_PATH, '/') . '/' . $domainSuffix . '/' . $domainPrefix)
        );
        $virtualHost->addDirective(
            new Directive('ErrorLog', rtrim(self::LOG_PATH, '/') . '/' . $domainPrefix . '-error_log')
        );
        $virtualHost->addDirective(
            new Directive('CustomLog', rtrim(self::LOG_PATH, '/') . '/' . $domainPrefix . '-access_log common')
        );



        $sslVirtualHost = new VirtualHost('*', self::HTTPS_PORT);
        $sslVirtualHost->addDirective(new Directive('ServerName', $answer));
        $sslVirtualHost->addDirective(
            new Directive('DocumentRoot', self::HOSTING_PATH . '/' . $domainSuffix . '/' . $domainPrefix)
        );

        $sslVirtualHost->addDirective(new Directive('SSLEngine', 'on'));
        $sslVirtualHost->addDirective(new Directive('Protocols', 'h2 h2c http/1.1'));
        $sslVirtualHost->addDirective(
            new Directive('SSLCertificateFile', self::SSL_PATH . '/certs/' . $answer . '.cert.pem')
        );
        $sslVirtualHost->addDirective(
            new Directive('SSLCertificateKeyFile', self::SSL_PATH . '/private/' . $answer . '.no-key.pem')
        );
        $sslVirtualHost->addDirective(
            new Directive('SSLCertificateChainFile', self::SSL_PATH . '/certs/ca-chain.cert.pem')
        );

        $sslVirtualHost->addDirective(new Directive('ErrorLog', self::LOG_PATH . '/' . $domainPrefix . '-e.log'));
        $sslVirtualHost->addDirective(
            new Directive('CustomLog', self::LOG_PATH . '/' . $domainPrefix . '-a.log common')
        );

        // save conf files
        $virtualHost->saveToFile(self::VHOST_PATH . '/' . $answer . '-http.conf');
        $sslVirtualHost->saveToFile(self::VHOST_PATH . '/' . $answer . '-https.conf');

        $sslBinTemplate = file_get_contents(__BASE__ . '/ssl-bin.tmpl');
        $sslBinary = str_replace('{{ DOMAIN }}', $answer, $sslBinTemplate);

        file_put_contents(dirname(self::SSL_PATH) . '/bin/' . $answer . '.sh', $sslBinary);

        $output->writeln(
            'cd ' . dirname(self::SSL_PATH)
            . ' && chmod +x bin/' . $answer . '.sh '
            . '&& bash bin/' . $answer . '.sh'
            . '&& brew services restart httpd24'
        );
    }
}