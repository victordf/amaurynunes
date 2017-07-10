<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 07/09/16
 * Time: 17:58
 */

ini_set('session.save_path',realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../tmp'));

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Email;
use App\UserProvider;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Doctrine\DBAL\Exception\SyntaxErrorException;
//use App\EscritorioServiceProvider;

require_once 'vendor/autoload.php';
require_once 'app/class/Email.class.php';
require_once 'app/class/UserProvider.php';
require_once 'app/class/Escritorio.class.php';
require_once 'app/class/Areaatuacao.class.php';
require_once 'app/class/Nossotime.class.php';
require_once 'web/_funcoes.php';

$app = new Application();
$email = new Email();
$encoder = new BCryptPasswordEncoder(4);

$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'      => 'pdo_mysql',
        'host'        => "an_sistema.mysql.dbaas.com.br",
        'dbname'      => "an_sistema",
        'user'        => "an_sistema",
        'password'    => "novocpc01"
//        'charset'     => 'utf8mb4'
    )
//    'db.options' => array(
//        'driver'      => 'pdo_mysql',
//        'host'        => "localhost",
//        'dbname'      => "amaury",
//        'user'        => "root",
//        'password'    => "123456",
//        'charset'     => 'utf8mb4'
//    )
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => 'app/view'
));

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'admin' => array(
            'pattern' => '^/admin',
            'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
            'logout' => array('logout_path' => '/admin/logout', 'invalidate_session' => true),
            'users' => function() use ($app) {
                return new UserProvider($app['db']);
            },
        )
    )
));

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\RoutingServiceProvider());

$app['twig']->addFunction(new \Twig_SimpleFunction('path', function($url) use ($app) {
    return $app['url_generator']->generate($url);
}));

$app['twig']->addGlobal('RAIZ', '/amaurynunes/');
//$app['twig']->addGlobal('RAIZ', '/');
//$app['twig']->addGlobal('RAIZ', '/an/');
$app['twig']->addGlobal('URL', "http://" . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI']);
$app['twig']->addGlobal('TITLEFB', "Amaury Nunes, advogados associados");

define('RAIZ', '/amaurynunes/');
//define('RAIZ', '/');
//define('RAIZ', '/an/');
define('URL', "http://" . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI']);
define('TITLEFB', "Amaury Nunes, advogados associados");

define('CONTRUCAO', false);
define('DIR', __DIR__);

$app['twig']->addGlobal('TITLE', 'Amaury Nunes');
$app['twig']->addGlobal('bgcolor', '#ffffff');
$app['twig']->addGlobal('username', $app['session']->get('_security.last_username'));
$app['twig']->addGlobal('ERRO', $app['session']->get('ERRO'));

$app['twig']->addGlobal('userid', $app['session']->get('userid'));
$app['twig']->addGlobal('username', $app['session']->get('username'));
$app['twig']->addGlobal('DIR', __DIR__);

$app->error(function(\Exception $e, $code) use($app){
    switch ($e){
        case $e instanceof NotFoundHttpException:
            return new RedirectResponse(RAIZ);
            break;
//        default:
//            return $app['twig']->render('pages/construcao.twig');
//            break;
    }
});

$app->after(function() use ($app) {
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    if ($loged) {
        $email = $token->getUser()->getUsername();
        $sql = "select id, nome From usuario where email = '{$email}'";
        $res = $app['db']->fetchAll($sql)[0];
        $app['session']->set('userid', $res['id']);
        $app['session']->set('username', $res['nome']);
    }
});

$app->before(function() use ($app){
    // Limpa o erro da sessão
    $app['session']->set('ERRO', '');
});

$app->mount('admin/escritorio', new \App\EscritorioServiceProvider());
$app->mount('admin/areaatuacao', new \App\AreaatuacaoServiceProvider());
$app->mount('admin/nossotime', new \App\NossotimeServiceProvider());

$app->post('areaatuacao/gettexto', function(Request $request) use($app){
    $req = $request->request->all();
    if($req['tipo'] == 0) {
        $sql = "select texto from areaatuacao where id = {$req['id']}";
    } else {
        $sql = "select concat(SUBSTRING(texto, 1, 247), '...') texto from areaatuacao where id = {$req['id']}";
    }
    $tex = $app['db']->fetchAll($sql)[0]['texto'];
    return '<p>'.$tex.'</p>';
});

$app->get('grupo-detalhe/{id}', function($id) use($app){
    $sql = <<<SQL
      select id, nome, funcao, resumo, curriculo, curriculolotes, email from nossotime where id = {$id}
SQL;
    $usuario = $app['db']->fetchAll($sql)[0];

    $sql = <<<SQL
                SELECT
                  nome,
                  imagem
                from idiomatime idt
                inner join idioma idi on idt.ididioma = idi.id
                where idtime = {$id}
                order by nome
SQL;
    $idiomas = $app['db']->fetchAll($sql);

    return $app['twig']->render('pages/home/sections/grupo-detalhe.twig', [
        'usuario' => $usuario,
        'idiomas' => $idiomas
    ]);
});

$app->get('nossotime/curriculo/{id}', function($id) use($app){
    $sql = <<<SQL
                select
                  id,
                  nome,
                  funcao,
                  resumo,
                  curriculo,
                  foto,
                  curriculolotes
                from nossotime
                where id = {$id}
SQL;
    $time = $app['db']->fetchAll($sql)[0];

    $sql = <<<SQL
                SELECT
                  nome,
                  imagem
                from idiomatime idt
                inner join idioma idi on idt.ididioma = idi.id
                where idtime = {$id}
                order by nome
SQL;
    $idiomas = $app['db']->fetchAll($sql);

    return $app['twig']->render('pages/home/curriculo.twig', [
        'dados' => $time,
        'idiomas' => $idiomas
    ]);
});


$app->get('/', function() use($app){
    $sql = "select bcrimg, bcrtitulo, bcrprincipal, bcrsubtitulo, bcrtembotao, bcrtxtbotao, bcrfuncbotao from carrossel";
    $bcr = $app['db']->fetchAll($sql);
    $sql = "select bpcdsc, bpcvalor from portfolio_categoria";
    $bpc = $app['db']->fetchAll($sql);
    $sql = "select bpcvalor, bpturl, bpttitulo, bptsubtitulo, bptlink From portfolio bpt inner join portfolio_categoria bpc on bpc.bpcid = bpt.bpcid";
    $bpt = $app['db']->fetchAll($sql);
    $sql = <<<DML
        select 
            usu.id,
            usu.nome, 
            usu.cargo, 
            usu.descricao, 
            case
                when usu.foto is not null then usu.foto
                else 'web/images/usuarios/padrao.jpg'
            end as foto, 
            usu.facebook, 
            usu.linkedin, 
            usu.googlep, 
            usu.twitter
        from usuario usu 
        where advogado = true    
DML;
    $adv = $app['db']->fetchAll($sql);
    $sql = <<<DML
        select 
            art.id, 
            art.titulo, 
            art.autor,
            art.resumo, 
            art.url,
            date_format(art.datacriacao, '%d/%m/%Y') as datacriacao,
            usr.nome as username,
            art.tipoartigo,
            art.link
        from artigo art
        inner join usuario usr on art.userid = usr.id
        where publico = true 
        order by datacriacao desc, id limit 3
DML;
    $art = $app['db']->fetchAll($sql);
    $sql = <<<SQL
        select 
            id,
            nome,
            funcao,
            resumo,
            curriculo,
            foto,
            curriculolotes
        from nossotime
        where status = 'A'
SQL;
    $not = $app['db']->fetchAll($sql);

    $arquivo = $_SERVER['DOCUMENT_ROOT'] . RAIZ . 'web/arquivos/escritorio.html';
    $escritorio = file_get_contents($arquivo);
    $sql = <<<DML
        select
            id,
            titulo,
            concat(SUBSTRING(texto, 1, 247), '...') texto
        from areaatuacao
        where status = 'A'
DML;
    $areas = $app['db']->fetchAll($sql);


    if(!CONTRUCAO) {
        return $app['twig']->render('pages/home/home.twig', array(
            'carrossel' => $bcr,
            'categoria' => $bpc,
            'portfolio' => $bpt,
            'artigos'   => $art,
            'advogados' => $adv,
            'escritorio'=> $escritorio,
            'areas'     => $areas,
            'nossotime' => $not,
            'cont'      => 5,
            'todosArtigos' => 1
        ));
    } else {
        return $app['twig']->render('pages/construcao.twig');
    }
});

$app->get('escritorio', function() use ($app){
    return new RedirectResponse(RAIZ);
});

$app->get('construcao', function() use ($app){
    return $app['twig']->render('pages/construcao.twig');
});

$app->get('artigo', function() use($app){
    return $app['twig']->render('pages/home/artigo.twig', [
        'artigo' => [
            'titulo' => '',
            'resumo' => '',
            'texto' => ''
        ]
    ]);
});

$app->get('artigo/{id}', function($id) use($app){
    $sql = <<<DML
        select titulo, resumo, texto From artigo where id = {$id}
DML;
    $res = $app['db']->fetchAll($sql)[0];
    $app['twig']->addGlobal('TITLEFB', "ARTIGO: ".$res['titulo']);
//    define('TITLEFB', "ARTIGO: ".$res['titulo']);
    return $app['twig']->render('pages/home/artigo.twig', [
        'artigo' => [
            'titulo' => $res['titulo'],
            'resumo' => $res['resumo'],
            'texto' => $res['texto']
        ]
    ]);
});

$app->get('artigos',function() use($app){
    $sql = <<<DML
        select 
            art.id, 
            art.titulo, 
            art.autor,
            art.resumo, 
            art.url,
            date_format(art.datacriacao, '%d/%m/%Y') as datacriacao,
            usr.nome as username,
            art.tipoartigo,
            art.link
        from artigo art
        inner join usuario usr on art.userid = usr.id
        where publico = true 
        order by datacriacao desc, id
DML;
    $art = $app['db']->fetchAll($sql);

    return $app['twig']->render('pages/artigo/artigos.twig', [
        'artigos'   => $art,
        'todosArtigos' => 0
    ]);
});

$app->get('admin', function() use ($app){
    return new RedirectResponse(RAIZ."admin/artigos");
});

$app->get('admin/artigo/cadastro', function() use($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    return $app['twig']->render('admin/pages/artigos/cadastro.twig', [
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'artigo' => [
            'id' => '',
            'titulo' => '',
            'autor' => '',
            'resumo' => '',
            'texto' => ''
        ],
        'MENU' => 'artigos'
    ]);
});

$app->get('admin/artigo/cadastro/{id}', function($id) use($app){
    $sql = <<<DML
        select titulo, autor, resumo, texto From artigo where id = {$id}
DML;
    $res = $app['db']->fetchAll($sql)[0];
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    return $app['twig']->render('admin/pages/artigos/cadastro.twig', [
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'artigo' => [
            'id' => $id,
            'titulo' => $res['titulo'],
            'autor' => $res['autor'],
            'resumo' => $res['resumo'],
            'texto' => $res['texto']
        ],
        'MENU' => 'artigos'
    ]);
});

$app->post('admin/artigo/cadastro', function(Request $request) use($app){
    $req = $request->request->all();
    $publico = empty($req['publico']) ? 'false' : 'true';
    $userid = $app['session']->get('userid');
    $server = $_SERVER['SERVER_NAME'] . RAIZ;
    $req['texto'] = str_replace('../http:', 'http:', str_replace('../../web/images/editor/', 'http://'.$server.'/web/images/editor/', $req['texto']));
    if(empty($req['id'])) {
        $sql = <<<DML
        insert into artigo (titulo, autor, resumo, texto, publico, arquivo, url, userid) values (
            '{$req['titulo']}',
            '{$req['autor']}',
            '{$req['resumo']}',
            '{$req['texto']}',
            {$publico},
            '',
            '',
            {$userid}
        )
DML;
    } else {
        $sql = <<<DML
        update artigo set
            titulo = '{$req['titulo']}',
            autor = '{$req['autor']}',
            resumo = '{$req['resumo']}',
            texto = '{$req['texto']}'
        where id = {$req['id']}
DML;

    }
    $app['db']->exec($sql);

    $app['session']->set('ERRO', array(
        'titulo' => 'Sucesso',
        'msg'    => 'Artigos salvos com sucesso',
        'tipo'   => 'success'
    ));

    return new RedirectResponse(RAIZ."admin/artigo/cadastro");
});

$app->post('editor/imagem', function(Request $request) use($app){
    $file = $request->files->get('file');

    $numero = rand(0, 9999);

    $arquivo = $file->getClientOriginalName();
    $fotoExtesao = $file->getClientOriginalExtension();
    $url = RAIZ."web/images/editor/imgeditor_" . $numero . '.' . $fotoExtesao;
    $urlF = DIR.'/web/images/editor/imgeditor_' . $numero . '.' . $fotoExtesao;
//    $file->move($urlF);
    move_uploaded_file($file->getPathname(), $urlF);

    return $app->json(['location' => $url]);
});

$app->get('admin/artigos', function() use ($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    $sql = "select id, titulo, resumo, arquivo, publico from artigo";
    $artigos = $app['db']->fetchAll($sql);
    return $app['twig']->render('admin/pages/artigos.twig', array(
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'artigo' => array(
            'id' => '',
            'titulo' => '',
            'resumo' => '',
            'arquivo' => '',
            'publico' => ''
        ),
        'artigos' => $artigos,
        'MENU' => 'artigos'
    ));
});

$app->get('admin/artigos/{id}', function($id) use($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    $sql = "select id, titulo, resumo, arquivo, publico from artigo where id = {$id}";
    $artigo = $app['db']->fetchAll($sql)[0];
    return $app['twig']->render('admin/pages/artigos.twig', array(
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'artigo' => $artigo,
        'artigos' => '',
        'MENU' => 'artigos'
    ));
});

$app->post('admin/artigos', function(Request $request) use($app){
    $req = $request->request->all();
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;

    $where = [];
    if(!empty($req['titulo'])){
        $where[] = "and titulo like '%{$req['titulo']}%'";
    }
    if(!empty($req['resumo'])){
        $where[] = "and resumo like '%{$req['resumo']}%'";
    }
    if(isset($req['publico'])){
        $where[] = "and publico = 1";
    }
    $whereSQL = implode(' ', $where);

    $sql = <<<DML
        select
            id, 
            titulo, 
            resumo, 
            arquivo, 
            publico
        from artigo
        where 1=1
        {$whereSQL}
DML;
    $artigos = $app['db']->fetchAll($sql);
    return $app['twig']->render('admin/pages/artigos.twig', array(
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'artigo' => array(
            'id' => '',
            'titulo' => $req['titulo'],
            'resumo' => $req['resumo'],
            'arquivo' => '',
            'publico' => isset($req['publico']) ? true : false
        ),
        'artigos' => $artigos,
        'MENU' => 'artigos'
    ));
});

$app->post('admin/artigos/publicar', function(Request $request) use($app){
    try {
        $req = $request->request->all();
        if(empty($req['publico'])){
            throw new Exception('Informação inválida');
        }
        if(empty($req['id'])){
            throw new Exception('Artigo não informado');
        }
        $sql = <<<DML
            update artigo set
                publico = {$req['publico']}
            where id = {$req['id']}
DML;
        $app['db']->exec($sql);
        return "Artigo alterado com sucesso";
    } catch(Exception $e){
        return $e->getMessage();
    }
});

$app->post('admin/artigos/deletar', function(Request $request) use($app){
    try{
        $req = $request->request->all();
        if(empty($req['id'])){
            throw new Exception('Artigo não informado');
        }
        $sql = "delete from artigo where id = {$req['id']}";
        $app['db']->exec($sql);
        return "Artigo deletado com sucesso";
    } catch (Exception $e){
        return $e->getMessage();
    }
});

$app->get('admin/teste', function() use ($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    return $app['twig']->render('admin/layout/admin.twig', array(
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username')
    ));
});

$app->get('admin/idiomas', function() use($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    $sql = <<<DML
        select * from idioma order by nome
DML;
    $idiomas = $app['db']->fetchAll($sql);

    return $app['twig']->render('admin/pages/idiomas.twig', array(
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'idiomas'       => $idiomas,
        'idioma'        => [
            'id'        => '',
            'nome'      => '',
            'imagem'    => ''
        ],
        'MENU'          => 'idiomas'
    ));
});

$app->post('admin/idiomas/delete', function(Request $request) use($app){
    $req = $request->request->all();
    $sql = <<<DML
        delete from idiomatime where ididioma = {$req['id']};
        delete from idioma where id = {$req['id']};
DML;
    $app['db']->exec($sql);
    return 'Idioma deletado com sucesso';
});

$app->get('admin/idiomas/{id}', function($id) use($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    $sql = <<<DML
        select * from idioma where id = {$id}
DML;
    $idioma = $app['db']->fetchAll($sql)[0];

    $sql = <<<DML
        select * from idioma order by nome
DML;
    $idiomas = $app['db']->fetchAll($sql);

    return $app['twig']->render('admin/pages/idiomas.twig', array(
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'idiomas'       => $idiomas,
        'idioma'        => $idioma,
        'MENU'          => 'idiomas'
    ));
});

$app->post('admin/idiomas', function(Request $request) use($app){
    try {
        $req = $request->request->all();
        $fle = $request->files->get('imagem');


        if(empty($req['id'])) {
            if(!empty($fle)) {
                $nome = $fle->getClientOriginalName();
                $fotoExtesao = $fle->getClientOriginalExtension();
                $dir = RAIZ . 'web/images/idiomas/';
                $dirF = DIR . '/web/images/idiomas/';
                $arquivo = $dir . $req['nome'] . $fotoExtesao;
                $arquivoF = $dirF . $req['nome'] . $fotoExtesao;

                if (is_writable($dirF)) {
                    if (move_uploaded_file($fle->getPathname(), $arquivoF)) {
                        $sql = <<<DML
                    insert into idioma (nome, imagem) values ('{$req['nome']}','{$arquivo}')
DML;
                        $app['db']->exec($sql);
                    } else {
                        throw new Exception('Erro ao salvar o arquivo.');
                    }
                } else {
                    throw new Exception('Diretório inválido');
                }
            } else {
                throw new Exception('Arquivo não informado');
            }
        } else {
            if(!empty($fle)){
                $nome = $fle->getClientOriginalName();
                $dir = RAIZ . 'web/images/idiomas';
                $dirF = __DIR__ . '/web/images/idiomas';
                $arquivo = $dir . $nome;
                $arquivoF = $dirF . $nome;

                if (is_writable($dirF)) {
                    if (move_uploaded_file($fle->getPathname(), $arquivoF)) {
                        $sql = <<<DML
                            update idioma set
                                nome = '{$req['nome']}',
                                imagem = '{$arquivo}',
                            where id = {$req['id']}
DML;
                        $app['db']->exec($sql);
                    } else {
                        throw new Exception('Erro ao salvar o arquivo.');
                    }
                } else {
                    throw new Exception('Diretório inválido');
                }
            } else {
                $sql = <<<DML
                    update idioma set
                        nome = '{$req['nome']}'
                    where id = {$req['id']}
DML;
                $app['db']->exec($sql);
            }
        }

        $app['session']->set('ERRO', array(
            'titulo' => 'Idioma salvo com sucesso',
            'msg' => '',
            'tipo' => 'success'
        ));

        return new RedirectResponse(RAIZ."admin/idiomas");
    } catch (Exception $e){
        $app['session']->set('ERRO', array(
            'titulo' => 'Erro',
            'msg' => $e->getMessage(),
            'tipo' => 'error'
        ));
        return new RedirectResponse(RAIZ."admin/idiomas");
    }
});

$app->get('login', function(Request $request) use ($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    return $app['twig']->render('admin/pages/login.twig', array(
        'bgcolor' => '#F8F8F8',
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
        'loged'         => $loged
    ));
});

$app->get('admin/perfil', function() use($app){
    $userid = $app['session']->get('userid');
    $sql = "select * from usuario where id = ".$userid;
    $dados = $app['db']->fetchAll($sql)[0];
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    return $app['twig']->render('admin/pages/cadastro.twig', array(
        'bgcolor' => '#F8F8F8',
        'last_username' => $app['session']->get('_security.last_username'),
        'loged'         => $loged,
        'MENU'          => 'perfil',
        'usuario'       => $dados
    ));
});

$app->post('admin/usuario', function(Request $request) use($app, $encoder){
    try {
        $req = $request->request->all();

        if(!empty($req['senha'])){
            $req['senha'] = $encoder->encodePassword($req['senha'], '');
        } else {
            unset($req['senha']);
        }

        if(empty($req['id'])){
            $app['db']->insert('usuario', $req);
        } else {
            $app['db']->update('usuario', $req, array('id' => $req['id']));
        }

        $app['session']->set('ERRO', array(
            'titulo' => 'Sucesso',
            'msg'    => 'Dados salvos com sucesso',
            'tipo'   => 'success'
        ));
    } catch (Exception $e) {
        $app['session']->set('ERRO', array(
            'titulo' => 'Erro',
            'msg' => $e->getMessage(),
            'tipo' => 'error'
        ));
    }
    return new RedirectResponse(RAIZ."admin/perfil");
});

$app->post('admin/foto', function(Request $request) use($app){
    // Report all PHP errors (see changelog)
    error_reporting(E_ALL);
    try {
        $req = $request->request->all();
        $foto = $request->files->get('file');
        var_dump($foto);
        die();
        $dir = __DIR__ . '/web/images/usuarios/';
        $upFile = $dir . 'foto' . $req['id'] . '.jpg';
        $bdFile = 'web/images/usuarios/' . 'foto' . $req['id'] . '.jpg';
        if (is_writable($dir)) {
            if (move_uploaded_file($foto->getPathname(), $dir . 'foto' . $req['id'] . '.jpg')) {
                $sql = <<<DML
                update usuario set
                    foto = '$bdFile'
                where id = {$req['id']}
DML;
                $app['db']->exec($sql);
                $app['session']->set('ERRO', array(
                    'titulo' => 'Sucesso',
                    'msg' => 'Foto salva com sucesso',
                    'tipo' => 'success'
                ));
            } else {
                $app['session']->set('ERRO', array(
                    'titulo' => 'Erro',
                    'msg' => 'Erro ao salvar a foto',
                    'tipo' => 'error'
                ));
            }
        } else {
            $app['session']->set('ERRO', array(
                'titulo' => 'Erro',
                'msg' => 'Diretório não pode ser acessado.',
                'tipo' => 'error'
            ));
        }
        return '';
    } catch (Exception $e){
        $app['session']->set('ERRO', array(
            'titulo' => 'Erro',
            'msg' => $e->getMessage(),
            'tipo' => 'error'
        ));
    }
});

$app->get('admin/usuarios', function() use($app){
    $sql = <<<DML
        select 
            usu.id,
            per.nome as perfil,
            usu.nome, 
            usu.email, 
            usu.idperfil, 
            usu.cargo, 
            usu.descricao, 
            usu.foto, 
            usu.facebook, 
            usu.linkedin, 
            usu.googlep, 
            usu.twitter, 
            usu.advogado 
        from usuario usu 
        inner join perfil per on usu.idperfil = per.id
DML;

    $usuarios = $app['db']->fetchAll($sql);
    $sql = "select id, nome from perfil";
    $perfis = $app['db']->fetchAll($sql);
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    return $app['twig']->render('admin/pages/usuarios.twig', array(
        'bgcolor' => '#F8F8F8',
        'loged'         => $loged,
        'usuarios'      => $usuarios,
        'usuario'       => '',
        'perfis'        => $perfis,
        'MENU'          => 'usuarios',
    ));
});

$app->get('admin/usuarios/{id}', function($id) use($app){
    $sql = <<<DML
        select 
            usu.id,
            per.nome as perfil,
            usu.nome, 
            usu.email, 
            usu.idperfil, 
            usu.cargo, 
            usu.descricao, 
            usu.foto, 
            usu.facebook, 
            usu.linkedin, 
            usu.googlep, 
            usu.twitter, 
            usu.advogado 
        from usuario usu 
        inner join perfil per on usu.idperfil = per.id
        where usu.id = {$id}
DML;

    $usuario = $app['db']->fetchAll($sql)[0];
    $sql = "select id, nome from perfil";
    $perfis = $app['db']->fetchAll($sql);
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    return $app['twig']->render('admin/pages/usuarios.twig', array(
        'bgcolor' => '#F8F8F8',
        'loged'         => $loged,
        'usuario'       => $usuario,
        'usuarios'      => '',
        'perfis'        => $perfis,
        'MENU'          => 'usuarios',
    ));
});

$app->post('admin/usuarios/publicar',function(Request $request) use($app){
    $req = $request->request->all();
    $sql = <<<DML
        update usuario set
            advogado = {$req['advogado']}
        where id = {$req['id']}
DML;
    $app['db']->exec($sql);
});

$app->post('admin/usuarios', function(Request $request) use($app, $encoder){
    try{
        $req = $request->request->all();


        if(empty($req['id'])) {
            $senha = $encoder->encodePassword($req['senha'], '');
            $advogado = array("campo" => '', "valor" => '');
            if(!empty($req['advogado'])){
                $advogado['campo'] = 'advogado,';
                $advogado['valor'] = 'true,';
            }
            $sql = <<<DML
                insert into usuario (
                    idperfil,
                    nome,
                    email,
                    senha,
                    cargo,
                    descricao,
                    facebook,
                    linkedin,
                    googlep,
                    {$advogado['campo']}
                    twitter
                ) values (
                    {$req['idperfil']},
                    '{$req['nome']}',
                    '{$req['email']}',
                    '{$senha}',
                    '{$req['cargo']}',
                    '{$req['descricao']}',
                    '{$req['facebook']}',
                    '{$req['linkedin']}',
                    '{$req['googlep']}',
                    {$advogado['valor']}
                    '{$req['twitter']}'
                )
DML;
        } else {
            $upd = '';
            if(!empty($req['senha'])){
                $senha = $encoder->encodePassword($req['senha'], '');
                $upd = "senha = '{$senha}',";
            }
            $advogado = "";
            if(!empty($req['advogado'])){
                $advogado = "advogado = true,";
            }
            $sql = <<<DML
                update usuario set
                    idperfil = {$req['idperfil']},
                    nome = '{$req['nome']}',
                    email = '{$req['email']}',
                    {$upd}
                    cargo = '{$req['cargo']}',
                    descricao = '{$req['descricao']}', 
                    facebook = '{$req['facebook']}',
                    linkedin = '{$req['linkedin']}',
                    googlep = '{$req['googlep']}',
                    {$advogado}
                    twitter = '{$req['twitter']}'
                where id = {$req['id']}
DML;

        }

        try {
            $app['db']->exec($sql);
        } catch (SyntaxErrorException $e){
            $app['session']->set('ERRO', array(
                'titulo' => 'Erro',
                'msg' => $e->getMessage(),
                'tipo' => 'error'
            ));
        }

        $app['session']->set('ERRO', array(
            'titulo' => 'Sucesso',
            'msg' => 'Usuário salvo com sucesso',
            'tipo' => 'success'
        ));
    } catch (Exception $e){
        $app['session']->set('ERRO', array(
            'titulo' => 'Erro',
            'msg' => $e->getMessage(),
            'tipo' => 'error'
        ));
    }
    return new RedirectResponse(RAIZ."admin/usuarios");
});

$app->get('teste', function() use($app, $encoder){
    $nome = 'Victor Martins Machado';
    $email = 'victormachado90@gmail.com';
    //$senha = $app['security.encoder.digest']->encodePassword('v1ct0rm4rt1ns', '');
    $senha = $encoder->encodePassword('v1ct0rm4rt1ns', '');
    die($senha);
//    $sql = "insert into usuario (idperfil, nome, email, senha) values (1,'{$nome}', '{$email}', '{$senha}')";
//    $app['db']->exec($sql);
//    echo $encoder->encodePassword('foo', 'teste');
//    echo "<br>";
//    echo $app['security.encoder.digest']->encodePassword('v1ct0rm4rt1ns', '');
//    echo "<br>";
//    echo $encoder->encodePassword('foo', 'v1ct0rm4rt1ns');
//    echo "<br>";
//    echo $encoder->encodePassword('foo', 'senha');
});

$app->post('email', function(Request $request) use($app, $email){
    $req = $request->request->all();
//    $email->addEmailTo(array('Victor Martins' => $req['recipient']));
//    $email->addEmailTo(array('Victor Martins' => 'victor@bsvsolucoes.com.br'));

    $email->setEmailFrom();
    $email->setFormato(true);
    $email->addEmailTo(['Contato' => Email::sisEmail]);

    $assunto = $req['subject'];
    $corpo = <<<HTML
        <table>
            <tr>
                <td>Nome</td>
                <td>{$req['name']}</td>
            </tr>
            <tr>
                <td>Email</td>
                <td>{$req['email']}</td>
            </tr>
            <tr>
                <td>Mensagem</td>
                <td>{$req['message']}</td>
            </tr>
        </table>
HTML;

    if(!$email->send($assunto, $corpo)){
        return $email->error;
    } else {
        return 'Mensagem enviada com sucesso';
    }
});

$app->get('esqueceu/senha', function() use($app){
    return $app['twig']->render('admin/pages/esqueceusenha.twig');
});

$app->post('esqueceu/senha', function(Request $request) use($app, $email, $encoder){
    $req = $request->request->all();
    $sql = "select id, nome from usuario where email = '{$req['email']}'";
    $res = $app['db']->fetchAll($sql)[0];
    if(!empty($res['id'])){
        $senha = geraSenha(6,false,true);
        $pass = $encoder->encodePassword($senha, '');
        $sql = <<<DML
            update usuario set
                senha = '{$pass}'
            where id = {$res['id']}
DML;
        $app['db']->exec($sql);
        $email->addEmailTo(array($res['nome'] => $req['email']));
        $email->setFormato(true);
        $assunto = 'Amaury Nunes - Nova Senha';
        $corpo = <<<HTML
            <div>
                <p>
                    Sua nova senha de acesso é: {$senha}
                </p>
            </div>
HTML;
        try {
            if (!$email->send($assunto, $corpo)) {
                $app['session']->set('ERRO', array(
                    'titulo' => 'Erro',
                    'msg' => $email->mailer->ErrorInfo,
                    'tipo' => 'error'
                ));
            } else {
                $app['session']->set('ERRO', array(
                    'titulo' => 'Sucesso',
                    'msg' => "Uma nova senha foi enviada para o seu email",
                    'tipo' => 'success'
                ));
            }
        } catch (phpmailerException $e){
            $app['session']->set('ERRO', array(
                'titulo' => 'Erro',
                'msg' => $e->getMessage(),
                'tipo' => 'error'
            ));
        }
    } else {
        $app['session']->set('ERRO', array(
            'titulo' => 'Erro',
            'msg' => "Email não encontrado",
            'tipo' => 'error'
        ));
    }
    return new RedirectResponse(RAIZ."login");
});

$app->run();