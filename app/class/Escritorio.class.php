<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 03/01/17
 * Time: 22:23
 */

namespace App;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EscritorioServiceProvider implements ControllerProviderInterface {
    protected $con;

    public function connect(Application $app){
        $this->con = $app['controllers_factory'];

        $this->con->get('/', function() use($app){
            
            $token = $app['security.token_storage']->getToken();
            $loged = empty($token) ? false : true;
            $arquivo = $_SERVER['DOCUMENT_ROOT'] . RAIZ . 'web/arquivos/escritorio.html';
            $escritorio = file_get_contents($arquivo);
            return $app['twig']->render('admin/pages/escritorio.twig', [
                'token'         => $token,
                'loged'         => $loged,
                'last_username' => $app['session']->get('_security.last_username'),
                'escritorio'    => $escritorio,
                'MENU' => 'escritorio'
            ]);
        });

        $this->con->post('/', function(Request $request) use($app){
            $req = $request->request->all();

            $req['escritorio'] = str_replace('../http:', 'http:', str_replace('../../web/images/editor/', 'http://localhost/amaurynunes/web/images/editor/', $req['escritorio']));

            $arquivo = $_SERVER['DOCUMENT_ROOT'] . RAIZ . 'web/arquivos/escritorio.html';

            file_put_contents($arquivo, $req['escritorio']);
            return new RedirectResponse(RAIZ."admin/escritorio");
        });

        return $this->con;
    }
}