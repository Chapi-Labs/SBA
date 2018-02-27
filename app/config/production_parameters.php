<?php
    $container->setParameter('secret', getenv('SECRET'));
    $container->setParameter('locale', 'es');
    $container->setParameter('api_email_key',getenv('API_EMAIL'));
