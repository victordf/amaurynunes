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

require_once 'vendor/autoload.php';
require_once 'app/class/Email.class.php';
require_once 'app/class/UserProvider.php';
require_once 'web/_funcoes.php';

$app = new Application();
$email = new Email();
$encoder = new BCryptPasswordEncoder(4);

$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
//    'db.options' => array(
//        'driver'      => 'pdo_mysql',
//        'host'        => "an_sistema.mysql.dbaas.com.br",
//        'dbname'      => "an_sistema",
//        'user'        => "an_sistema",
//        'password'    => "novocpc01"
////        'charset'     => 'utf8mb4'
//    )
    'db.options' => array(
        'driver'      => 'pdo_mysql',
        'host'        => "localhost",
        'dbname'      => "amaury",
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

define('RAIZ', '/amaurynunes/');
//define('RAIZ', '/');

define('CONTRUCAO', false);

$app['twig']->addGlobal('TITLE', 'Amaury Nunes');
$app['twig']->addGlobal('bgcolor', '#ffffff');
$app['twig']->addGlobal('username', $app['session']->get('_security.last_username'));
$app['twig']->addGlobal('ERRO', $app['session']->get('ERRO'));

$app['twig']->addGlobal('userid', $app['session']->get('userid'));
$app['twig']->addGlobal('username', $app['session']->get('username'));

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
            art.resumo, 
            art.url,
            date_format(art.datacriacao, '%d/%m/%Y') as datacriacao,
            usr.nome as username,
            art.tipoartigo,
            art.link
        from artigo art
        inner join usuario usr on art.userid = usr.id
        where publico = true
DML;

    $art = $app['db']->fetchAll($sql);
    if(!CONTRUCAO) {
        return $app['twig']->render('pages/home/home.twig', array(
            'carrossel' => $bcr,
            'categoria' => $bpc,
            'portfolio' => $bpt,
            'artigos'   => $art,
            'advogados' => $adv,
            'cont'      => 5
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
        'MENU' => 'artigos'
    ]);
});

$app->post('admin/artigo/cadastro', function(Request $request) use($app){
    $req = $request->request->all();
    $publico = empty($req['publico']) ? 'false' : 'true';
    $userid = $app['session']->get('userid');
    $sql = <<<DML
        insert into artigo (titulo, resumo, texto, publico, arquivo, url, userid) values (
            '{$req['titulo']}',
            '{$req['resumo']}',
            '{$req['texto']}',
            {$publico},
            '',
            '',
            {$userid}
        )
DML;
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

    $arquivo = $file->getClientOriginalName();
    $url = RAIZ."web/images/editor/" . $arquivo;
    $file->move(__DIR__.'/web/images/editor/', $arquivo);

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
    $file = $request->files->get('arquivo');
    $publico = empty($req['publico']) ? 'false' : 'true';
    $userid = $app['session']->get('userid');
    $link = $req['link'];
    $tipoartigo = $req['tipo'];

    if(!empty($file)) {
        $arquivo = $file->getClientOriginalName();
        $url = "/web/arquivos/artigos/" . $file->getClientOriginalName();
        $file->move('web/arquivos/artigos/', $file->getClientOriginalName());
    }
    if(empty($req['id'])){
        $dataatual = date('Y-m-d H:i:s');
        $sql = "insert into artigo (titulo, resumo, arquivo, url, publico, userid, datacriacao, link, tipoartigo) values ('{$req['titulo']}', '{$req['resumo']}', '{$arquivo}', '{$url}', {$publico}, {$userid}, '{$dataatual}', '{$link}', '{$tipoartigo}')";
    } else {
        if(empty($file)) {
            $sql = "update artigo set titulo = '{$req['titulo']}', resumo = '{$req['resumo']}', publico = {$publico}, link = '{$link}', tipoartigo = '{$tipoartigo}' where id = {$req['id']}";
        } else {
            $sql = "select url from artigo where id = {$req['id']}";
            $urlL = $_SERVER['DOCUMENT_ROOT']. $app['db']->fetchAll($sql)[0]['url'];
            unlink($urlL);
            $sql = <<<DML
                update artigo set
                    titulo = '{$req['titulo']}',
                    resumo = '{$req['resumo']}',
                    publico = {$publico},
                    arquivo = '{$arquivo}',
                    url = '{$url}',
                    link = '{$link}',
                    tipoartigo = '{$tipoartigo}'
                where id = {$req['id']}
DML;

        }
    }
    $res = $app['db']->exec($sql);

    return new RedirectResponse(RAIZ."admin/artigos");
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
    $email->addEmailTo(array('Victor Martins' => 'victor@bsvsolucoes.com.br'));
    $assunto = $req['subject'];
    $corpo = $req['mensagem'];

    if(!$email->send($assunto, $corpo)){
        return $email->error;
    } else {
        return 'FOI CARAI!';
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