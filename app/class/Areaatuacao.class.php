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

class AreaatuacaoServiceProvider implements ControllerProviderInterface {
    protected $con;
    protected $app;

    public function connect(Application $app){
        $this->con = $app['controllers_factory'];
        $this->app = $app;

//        $app['twig']->addGlobal('ERRO', $app['session']->get('ERRO'));

        $this->con->get('/', function() use($app){

            $token = $app['security.token_storage']->getToken();
            $loged = empty($token) ? false : true;
            $areas = $this->getTabela(null, $app);

            $area = [
                'id' => '',
                'titulo' => '',
                'texto' => '',
                'status' => ''
            ];

            return $app['twig']->render('admin/pages/areaatuacao.twig', [
                'token'         => $token,
                'loged'         => $loged,
                'last_username' => $app['session']->get('_security.last_username'),
                'area'          => $area,
                'areas'         => $areas,
                'MENU' => 'escritorio'
            ]);
        });

        $this->con->get('/{id}', function($id) use($app){

            $token = $app['security.token_storage']->getToken();
            $loged = empty($token) ? false : true;
            $areas = $this->getTabela(null, $app);

            $sql = <<<DML
                select
                    id,
                    titulo,
                    texto,
                    status
                from areaatuacao where id = {$id} 
DML;
            $area = $app['db']->fetchAll($sql)[0];

            return $app['twig']->render('admin/pages/areaatuacao.twig', [
                'token'         => $token,
                'loged'         => $loged,
                'last_username' => $app['session']->get('_security.last_username'),
                'area'          => $area,
                'areas'         => $areas,
                'MENU' => 'escritorio'
            ]);
        });

        $this->con->post('/', function(Request $request) use($app){
            $req = $request->request->all();

            if(strlen($req['texto']) > 500) {
                echo <<<SCRIPT
                    <script>
                        alert('O campo "texto" deve conter até 500 caractéres.');
                    </script>
SCRIPT;
                return false;

            }

            if(empty($req['id'])) {
                $this->insertAreaAtuacao($req, $app);
                $app['session']->set('ERRO', array(
                    'titulo' => 'Sucesso',
                    'msg'    => 'Área de atuação salva com sucesso',
                    'tipo'   => 'success'
                ));
            } else {
                $this->alteraAreaAtuacao($req);
                $app['session']->set('ERRO', array(
                    'titulo' => 'Sucesso',
                    'msg'    => 'Àrea de atuação alterada com sucesso',
                    'tipo'   => 'success'
                ));
            }

            return new RedirectResponse(RAIZ."admin/areaatuacao");
        });

        $this->con->post('/status', function(Request $request) use ($app){
            $req = $request->request->all();

            $this->alteraStatusAreaAtuacao($req['id'], $req['status'], $app);

            return 'Status alterado com sucesso';
        });

        $this->con->post('/apagar', function(Request $request) use ($app){
            $req = $request->request->all();

            $this->apagarAreaAtuacao($req['id']);

            return 'Area de atuação apagada com sucesso';
        });

        return $this->con;
    }

    public function getTabela($areaid = null, Application $app){
        $where = empty($areaid) ? '' : "where id = {$areaid}";
        $sql = <<<DML
            select
                id,
                titulo,
                texto,
                case
                    when status = 'A' then 'Ativo'
                    when status = 'I' then 'Inativo'
                end status
            from areaatuacao;
            {$where}
DML;
        return $app['db']->fetchAll($sql);
    }

    public function insertAreaAtuacao($dados = [], Application $app){
        if(!empty($dados)){
            $sql = <<<DML
                insert into areaatuacao (
                    titulo,
                    texto,
                    status
                ) values (
                    '{$dados['titulo']}',
                    '{$dados['texto']}',
                    '{$dados['status']}'
                )
DML;
            $app['db']->exec($sql);
        }
    }

    public function alteraAreaAtuacao($dados = []){
        $app = $this->app;
        if(!empty($dados)){
            $sql = <<<DML
                update areaatuacao set
                    titulo = '{$dados['titulo']}',
                    texto = '{$dados['texto']}',
                    status = '{$dados['status']}'
                where id = {$dados['id']}
DML;
            $app['db']->exec($sql);
        }
    }

    public function alteraStatusAreaAtuacao($id, $status, Application $app){
        if(!empty($status)){
            $sql = <<<DML
                update areaatuacao set
                    status = '{$status}'
                where id = {$id}
DML;
            $app['db']->exec($sql);
        }
    }

    public function apagarAreaAtuacao($id){
        $app = $this->app;
        $sql = <<<DML
            delete from areaatuacao where id = {$id} 
DML;
        $app['db']->exec($sql);
    }

}