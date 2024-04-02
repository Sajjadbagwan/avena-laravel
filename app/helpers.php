<?php 
/**
 * magento account configuration
 */
function configMagento()
{
    $service = new \Experius\Magento2ApiClient\Service\RestApi();

    // Oauth Signing
    $service->setConsumerKey('w9f0vidhbl7dboy2f2qrvc6243bm618y');
    $service->setConsumerSecret('efwo6iaxfsm0vwn9ukt0ug1y8zty2wz8');
    $service->setAccesToken('m998dqcngdsshiau1mdztzdygyp3lp5c');
    $service->setAccesTokenSecret('3z2s6ulyajdy9oir5fo3phksvwy85pea');

    $service->setUrl('https://magento244.dev-box.me/index.php/rest/%storecode/V1/');

    // OPTIONAL > default = all
    $service->setStoreCode('default');

    return $service;
}
