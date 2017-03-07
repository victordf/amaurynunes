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

            $idms = $this->getArrayIdioma();
            $not  = $this->getArrayNossoTime();
            
            return $app['twig']->render('admin/pages/time/cadastro.twig', [
                'token'         => $token,
                'loged'         => $loged,
                'last_username' => $app['session']->get('_security.last_username'),
                'idiomas'       => $idms,
                'nossotime'     => [
                    'id' => '',
                    'nome' => '',
                    'funcao' => '',
                    'resumo' => '',
                    'curriculo' => '',
                    'foto' => ''
                ],
                'tabela'        => $not,
                'MENU' => 'nossotime'
            ]);
        });

        $this->con->get('/{id}', function($id) use($app){
            $token = $app['security.token_storage']->getToken();
            $loged = empty($token) ? false : true;

            $idms = $this->getArrayIdioma(null, $id);
            $not  = $this->getArrayNossoTime();
            $time = $this->getArrayNossoTime($id)[0];

            return $app['twig']->render('admin/pages/time/cadastro.twig', [
                'token'         => $token,
                'loged'         => $loged,
                'last_username' => $app['session']->get('_security.last_username'),
                'idiomas'       => $idms,
                'nossotime'     => $time,
                'tabela'        => $not,
                'MENU' => 'nossotime'
            ]);
        });
        
        $this->con->post('/', function(Request $request) use($app){
            $req  = $request->request->all();
            $file = $request->files->get('foto');
//            $fle =  $request->files->get('imagem');

            $id        = $req['id'];
            $nome      = $req['nome'];
            $funcao    = $req['funcao'];
            $resumo    = $req['resumo'];
            $curriculo = $req['curriculo'];
            $idiomas   = $req['idioma'];
            $arquivo   = '';

            if (!empty($id)) {
                $sql = <<<SQL
                    update nossotime set
                        nome = '{$nome}',
                        funcao = '{$funcao}',
                        resumo = '{$resumo}',
                        curriculo = '{$curriculo}'
                    where id = {$id}
SQL;
                $app['db']->exec($sql);
            } else {

                $sql = <<<SQL
                    insert into nossotime (nome, funcao, resumo, curriculo, foto) values (
                        '{$nome}',
                        '{$funcao}',
                        '{$resumo}',
                        '{$curriculo}',
                        '{$arquivo}'
                    );
SQL;
                $app['db']->exec($sql);
                $id = $app['db']->lastInsertId();
            }


            if(!empty($file)){
                $fotoNome = $file->getClientOriginalName();
                $dir      = RAIZ . 'web/images/time';
                $dirF     = __DIR__ . '/web/images/time';
                $arquivo  = $dir . $fotoNome.'_'.$id;
                $arquivoF = $dirF . $fotoNome.'_'.$id;

                if(is_writable($dirF)){
                    if(!move_uploaded_file($file->getPathname(), $arquivoF)){
                        throw new Exception('Erro ao salvar o arquivo');
                    }
                } else {
                    throw new Exception('Diretório inválido');
                }
            }

            $sql = <<<SQL
                update nossotime set
                    foto = '{$arquivo}'
                where id = {$id}
SQL;
            $app['db']->exec($sql);

            if(is_array($idiomas)){
                $sql = <<<DML
                    delete from idiomatime where idtime = {$id}            
DML;
                $app['db']->exec($sql);

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

        $this->con->post('/delete', function(Request $request) use($app){
            $req = $request->request->all();
            $id = $req['id'];
            try {
                $sql = <<<SQL
              delete from idiomatime where idtime = {$id};
              delete from nossotime where id = {$id};
SQL;
                $this->app['db']->exec($sql);

                return 'Membro apagado com sucesso';
            } catch (Exception $e){
                return $e->getMessage();
            }
        });

        return $this->con;
    }
    
    /**
     * Função que retorna um array com os idiomas
     * 
     * @param type $id - Parâmetro que filtra os idiomas pelo id
     * @param type $idtime - Parâmetro que filtra os idiomas pelo id do time
     * @param Array $opcoes - Parâmetro que recebe as condições do SQL. Exemplo:
     *      - ["nome = 'teste'"]
     *      - ["tipo = 123", "nome = 'teste'"]
     * 
     * @return Array
     */
    public function getArrayIdioma($id = null, $idtime = null, $opcoes = []){
        $opcoes[] = !empty($id) ? " and id = {$id}" : "";
        $where = implode(' and ', $opcoes);
        if(!empty($idtime)){
            $join = "left join idiomatime idt on idi.id = idt.ididioma and idt.idtime = {$idtime}";
            $col = ",idtime as time";
        } else {
            $join = "";
            $col = ",'' as time";
        }
        $sql = <<<SQL
                select 
                    id, 
                    nome, 
                    imagem
                    {$col}
                from idioma idi
                {$join}
                where 1=1 
                {$where}
SQL;
        return $this->app['db']->fetchAll($sql);
    }
    
    /**
     * Função que retorna um array com as informações dos membros do time
     * 
     * @param type $id - Parâmetro que recebe o ID do time
     * @param array $opcoes - Parâmetro que recebe as condições do SQL. Exemplo:
     *      - ["nome = 'teste'"]
     *      - ["tipo = 123", "nome = 'teste'"]
     * @return type
     */
    public function getArrayNossoTime($id = null, $opcoes = []){
        $opcoes[] = !empty($id) ? " and id = {$id}" : "";
        $where = implode(' and ', $opcoes);
        $sql = <<<SQL
            select
                id,
                nome,
                funcao,
                resumo,
                curriculo,
                foto
            from nossotime
            where 1=1
            {$where}
SQL;
        return $this->app['db']->fetchAll($sql);
    }

    /**
     * Função que apaga todos os idiomas vinculados ao nosso time.
     *
     * @param $id
     */
    public function apagaIdiomasNossoTime($id){
        $sql = <<<SQL
          delete from idiomatime where idtime = {$id} 
SQL;
        return $this->app['db']->exec($sql);
    }
}