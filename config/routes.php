// config/routes.php
return [
    'GET /' => 'PontoController@index',
    'GET /ponto/{id}' => 'PontoController@show',
    'GET /pre-selecao' => 'PreSelecaoController@form',
    'POST /pre-selecao' => 'PreSelecaoController@generate',
    'GET /login' => 'AuthController@showLogin',
    'POST /login' => 'AuthController@login',
    'POST /logout' => 'AuthController@logout',
];