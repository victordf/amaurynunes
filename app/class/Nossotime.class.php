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
use Symfony\Component\Config\Definition\Exception\Exception;
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
            $req  = $request->request->all();
            $file = $request->files->get('file');

            $nome      = $req['nome'];
            $funcao    = $req['funcao'];
            $resumo    = $req['resumo'];
            $curriculo = $req['curriculo'];
            $idiomas    = $req['idioma'];
            $arquivo   = '';

            if(!empty($file)){
                $fotoNome = $file->getClientOriginalName();
                $dir      = RAIZ . '/web/images/time';
                $dirF     = __DIR__ . '/web/images/time';
                $arquivo  = $dir . $fotoNome;
                $arquivoF = $dirF . $fotoNome;

                if(is_writable($dirF)){
                    if(!move_uploaded_file($file->getPathname(), $arquivoF)){
                        throw new Exception('Erro ao salvar o arquivo');
                    }
                } else {
                    throw new Exception('Diretório inválido');
                }
            }

            $sql = <<<DML
                insert into nossotime (nome, funcao, resumo, curriculo, foto) values (
                    '{$nome}',
                    '{$funcao}',
                    '{$resumo}',
                    '{$curriculo}',
                    '{$arquivo}'
                );
DML;
            $app['db']->exec($sql);

            $id = $app['db']->lastInsertId();

            if(is_array($idiomas)){
                foreach ($idiomas as $idioma) {
                    $sql = <<<DML
                        insert into idiomatime (ididioma, idtime) values (
                            {$idioma},
                            {$id}
                        )
DML;
                    $app['db']->exec($sql);
                }
            }

            $app['session']->set('ERRO', array(
                'titulo' => 'Membro salvo com sucesso',
                'msg' => '',
                'tipo' => 'success'
            ));

            return new RedirectResponse(RAIZ."admin/nossotime/");
        });

        return $this->con;
    }
}