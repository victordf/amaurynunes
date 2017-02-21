<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 06/02/17
 * Time: 20:40
 */

namespace App;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NossotimeServiceProvider implements ControllerProviderInterface{
    protected $con;
    protected $app;

    public function connect(Application $app){
        $this->con = $app['controllers_factory'];
        $this->app = $app;

        $this->con->get('/', function() use($app){

            $token = $app['security.token_storage']->getToken();
            $loged = empty($token) ? false : true;

            $sql = <<<DML
                select id, nome, imagem from idioma
DML;
            $idms = $app['db']->fetchAll($sql);


            return $app['twig']->render('admin/pages/time/cadastro.twig', [
                'token'         => $token,
                'loged'         => $loged,
                'last_username' => $app['session']->get('_security.last_username'),
                'idiomas'       => $idms,
                'MENU' => 'nossotime'
            ]);
        });

        $this->con->post('/', function(Request $request) use($app){

        });

        return $this->con;
    }
}