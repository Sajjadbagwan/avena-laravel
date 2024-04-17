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



    /*$service->setConsumerKey('mfb3a2v63wnn2urt8c8291xophfzr203');
    $service->setConsumerSecret('pa4iqy5ipxr2pz0b52t7mz6e4pi0mgzl');
    $service->setAccesToken('c3i046u1wwbyfvkwdgcbx5l3gmykznn3');
    $service->setAccesTokenSecret('s3186ppzwc0qtk8jhd1bce3myiwee6jw');*/

    

    $service->setUrl('https://magento244.dev-box.me/index.php/rest/%storecode/V1/');

    // OPTIONAL > default = all
    $service->setStoreCode('default');

    return $service;
}
function configMagentoUpdate()
{
    $service = new \Experius\Magento2ApiClient\Service\RestApi();

    // Oauth Signing
    $service->setConsumerKey('w9f0vidhbl7dboy2f2qrvc6243bm618y');
    $service->setConsumerSecret('efwo6iaxfsm0vwn9ukt0ug1y8zty2wz8');
    $service->setAccesToken('m998dqcngdsshiau1mdztzdygyp3lp5c');
    $service->setAccesTokenSecret('3z2s6ulyajdy9oir5fo3phksvwy85pea');

    $service->setUrl('https://magento244.dev-box.me/rest/%storecode/V1/');

    // OPTIONAL > default = all
    $service->setStoreCode('default');

    return $service;
}