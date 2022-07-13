<?php

require "../inc/bootstrap.php";

use Mst\Api;
$Api = new Api();

$router = new AltoRouter();
$router->setBasePath(FRONT_DOCROOT);

/*
$router->addMatchTypes([
    'x' => '[a-zA-Z0-9-_]+', 
]);
*/



/**
 * ---------------------
 * GET
 * ---------------------
 */

// esame get
$router->map( 'GET', '/', function() {
    print 'ping';
});

// esame get
$router->map( 'GET', '/channels', function() {
    Api::list_channels();
});

$router->map( 'GET', '/channels/[i:id_ch]', function($id_ch) {
    
    Api::get_channel($id_ch);
});

$router->map( 'GET', '/channels/[i:id_ch]/documents', function($id_ch) {
    Api::list_documents($id_ch);
});

// 
$router->map( 'GET', '/documents/[i:id_doc]', function($id_doc) {
    Api::get_document($id_doc);
});

$router->map( 'GET', '/documents/[i:id_doc]/comments', function($id_doc) {
    Api::list_comments($id_doc);
});


/**
 * ---------------------
 * POST
 * ---------------------
 */

$router->map( 'POST', '/channels/new', function() {
    Api::create_channel();
});

$router->map( 'POST', '/channels/[i:id_ch]/new', function($id_ch) {
    Api::create_document($id_ch);
});


/**
 * ---------------------
 * PUT
 * ---------------------
 */
$router->map( 'PUT', '/channels/[i:id_ch]', function($id_ch) {
    Api::update_channel($id_ch);
});

$router->map( 'PUT', '/documents/[i:id_doc]', function($id_doc) {
    Api::update_document($id_doc);
});

$router->map( 'PUT', '/comments/[i:id_comm]', function($id_comm) {
    Api::update_comment($id_comm);
});



/**
 * ---------------------
 * DELETE
 * ---------------------
 */
$router->map( 'DELETE', '/channels/[i:id_ch]', function($id_ch) {
    Api::delete_channel($id_ch);
});

$router->map( 'DELETE', '/documents/[i:id_doc]', function($id_doc) {
    Api::delete_document($id_doc);
});

$router->map( 'DELETE', '/comments/[i:id_comm]', function($id_comm) {
    Api::delete_comment($id_comm);
});








// match current request
$match = $router->match();

$NOT_FOUND = false;

if( $match && is_callable( $match['target'] ) ) {
    try{
        $call = call_user_func_array( $match['target'], $match['params'] ); 
        if ($call === FALSE){
            throw new RouterException(
                sprintf("Routing error on %s", print_r($match['target'], true))
            );
        }
    } catch (RouterException $e) {
        $e->setLog(\Monolog\Logger::EMERGENCY);
        exit;
    }
}
else {
    // no route was matched: 404
    $NOT_FOUND = true;
}

if($NOT_FOUND){
    Common::send_404();
}
