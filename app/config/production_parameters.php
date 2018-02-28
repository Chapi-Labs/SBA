<?php
    $container->setParameter('secret', getenv('SECRET'));
    $container->setParameter('locale', 'es');
    $container->setParameter('api_email_key',getenv('API_EMAIL'));
    $container->setParameter('mailer_transport', 'smtp');
    $container->setParameter('mailer_host', getenv('MAILER_HOST'));
    $container->setParameter('mailer_user', getenv('MAILER_USER'));
    $container->setParameter('mailer_password', getenv('MAILER_PASSWORD'));
