<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 07/09/16
 * Time: 17:58
 */

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;
use App\Email;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use App\UserProvider;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

require_once 'vendor/autoload.php';
require_once 'app/class/Email.class.php';
require_once 'app/class/UserProvider.php';

$app = new Application();
$email = new Email();
$encoder = new BCryptPasswordEncoder(4);

$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'      => 'pdo_mysql',
        'host'        => "localhost",
        'dbname'      => "bsv",
        'user'        => "root",
        'password'    => "123456",
        'charset'     => 'utf8mb4'
    )
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
//            'users' => array(
//                // raw password is foo
//                'admin' => array('ROLE_ADMIN', '$2y$10$3i9/lVd8UOFIJ6PAMFt8gu3/r5g0qeCJvoSlLCsvMTythye19F77a'),
//            ),
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
$app['twig']->addGlobal('TITLE', 'Amaury Nunes');
$app['twig']->addGlobal('bgcolor', '#ffffff');
$app['twig']->addGlobal('username', $app['session']->get('_security.last_username'));

$app->get('/', function() use($app){
    $sql = "select bcrimg, bcrtitulo, bcrprincipal, bcrsubtitulo, bcrtembotao, bcrtxtbotao, bcrfuncbotao from bsv_carrossel";
    $bcr = $app['db']->fetchAll($sql);
    $sql = "select bpcdsc, bpcvalor from bsv_portfolio_categoria";
    $bpc = $app['db']->fetchAll($sql);
    $sql = "select bpcvalor, bpturl, bpttitulo, bptsubtitulo, bptlink From bsv_portfolio bpt inner join bsv_portfolio_categoria bpc on bpc.bpcid = bpt.bpcid";
    $bpt = $app['db']->fetchAll($sql);
    return $app['twig']->render('pages/home/home.twig', array(
        'carrossel' => $bcr,
        'categoria' => $bpc,
        'portfolio' => $bpt
    ));
});

$app->get('admin', function() use ($app){
    return new RedirectResponse("/amaurynunes/admin/artigos");
});

$app->get('admin/artigos', function() use ($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    $sql = "select id, titulo, resumo, arquivo, publico from bsv_artigo";
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
        'artigos' => $artigos
    ));
});

$app->get('admin/artigos/{id}', function($id) use($app){
    $token = $app['security.token_storage']->getToken();
    $loged = empty($token) ? false : true;
    $sql = "select id, titulo, resumo, arquivo, publico from bsv_artigo where id = {$id}";
    $artigo = $app['db']->fetchAll($sql)[0];
    return $app['twig']->render('admin/pages/artigos.twig', array(
        'token' => $token,
        'loged'         => $loged,
        'last_username' => $app['session']->get('_security.last_username'),
        'artigo' => $artigo,
        'artigos' => ''
    ));
});

$app->post('admin/artigos', function(Request $request) use($app){
    $req = $request->request->all();
    $file = $request->files->get('arquivo');
    $publico = empty($req['publico']) ? 'false' : 'true';

    if(!empty($file)) {
        $arquivo = $file->getClientOriginalName();
        $url = "/amaurynunes/web/arquivos/artigos/" . $file->getClientOriginalName();
        $file->move('web/arquivos/artigos/', $file->getClientOriginalName());
    }
    if(empty($req['id'])){
        $sql = "insert into bsv_artigo (titulo, resumo, arquivo, url, publico) values ('{$req['titulo']}', '{$req['resumo']}', '{$arquivo}', '{$url}', {$publico})";
    } else {
        if(empty($file)) {
            $sql = "update bsv_artigo set titulo = '{$req['titulo']}', resumo = '{$req['resumo']}', publico = {$publico} where id = {$req['id']}";
        } else {
            $sql = "select url from bsv_artigo where id = {$req['id']}";
            $urlL = $app['db']->fetchAll($sql)[0]['url'];
            unlink($urlL);
            $sql = <<<DML
                update bsv_artigo set
                    titulo = '{$req['titulo']}',
                    resumo = '{$req['resumo']}'
                    publico = {$publico},
                    arquivo = '{$arquivo}',
                    url = '{$url}'
                where id = {$req['id']}
DML;

        }
    }
    $res = $app['db']->exec($sql);

    return new RedirectResponse("/amaurynunes/admin/artigos");
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
            update bsv_artigo set
                publico = {$req['publico']}
            where id = {$req['id']}
DML;
        $app['db']->exec($sql);
        return "Estado alterado com sucesso";
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
        $sql = "delete from bsv_artigo where id = {$req['id']}";
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

$app->get('admin/teste', function() use($app, $encoder){
    $nome = 'Victor Martins Machado';
    $email = 'victormachado90@gmail.com';
    //$senha = $app['security.encoder.digest']->encodePassword('v1ct0rm4rt1ns', '');
    $senha = $encoder->encodePassword('v1ct0rm4rt1ns', '');
    $sql = "insert into bsv_user (nome, email, senha) values ('{$nome}', '{$email}', '{$senha}')";
//    $app['db']->exec($sql);
//    echo $encoder->encodePassword('foo', 'teste');
//    echo "<br>";
//    echo $app['security.encoder.digest']->encodePassword('v1ct0rm4rt1ns', '');
//    echo "<br>";
//    echo $encoder->encodePassword('foo', 'v1ct0rm4rt1ns');
//    echo "<br>";
//    echo $encoder->encodePassword('foo', 'senha');
});

//$app->get('email', function() use($app, $email){
//    $email->addEmailTo(array('Victor Martins' => 'victormachado90@gmail.com'));
//    $assunto = 'Teste de email';
//    $corpo = 'Bora ver se vai';
//
//    if(!$email->send($assunto, $corpo)){
//        return $email->error;
//    } else {
//        return 'FOI CARAI!';
//    }
//});

$app->run();